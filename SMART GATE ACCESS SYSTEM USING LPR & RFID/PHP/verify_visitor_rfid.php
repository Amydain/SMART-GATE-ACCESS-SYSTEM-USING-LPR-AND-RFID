
<?php
require_once "db.php";

date_default_timezone_set("Asia/Kuala_Lumpur");
header("Content-Type: application/json; charset=utf-8");

function respond($code, $data) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// 1. Check request method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(405, [
        "ok" => false,
        "reason" => "method_not_allowed"
    ]);
}

// 2. Authenticate Main ESP32
$esp32Key = "SG_QR_GATE_7xP9mK2vR8nL4wT6aB3cD5";
$providedKey = $_SERVER["HTTP_X_ESP32_KEY"] ?? "";

if (!is_string($providedKey) ||
    !hash_equals($esp32Key, $providedKey)) {
    respond(401, [
        "ok" => false,
        "reason" => "unauthorized"
    ]);
}

// 3. Read JSON request
$body = json_decode(file_get_contents("php://input"), true);

if (!is_array($body)) {
    respond(400, [
        "ok" => false,
        "reason" => "invalid_json"
    ]);
}

$uid = strtoupper(
    preg_replace('/\s+/', '', (string)($body["uid"] ?? ""))
);

if (!preg_match('/^[A-F0-9]{8,20}$/', $uid)) {
    respond(400, [
        "ok" => false,
        "reason" => "invalid_rfid_uid"
    ]);
}

// 4. Start database transaction
$conn->begin_transaction();

try {

    // Find and lock RFID card
    $stmt = $conn->prepare(
        "SELECT id, card_name, card_type, status, visit_id
        FROM rfid_cards
        WHERE rfid_uid = ?
        FOR UPDATE"
            );

    $stmt->bind_param("s", $uid);
    $stmt->execute();

    $card = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$card) {
        throw new Exception("unknown_rfid");
    }

    $cardId = (int)$card["id"];
    $cardType = $card["card_type"];
    $cardStatus = $card["status"];

    if (!in_array($cardType, ["security", "visitor"], true)) {
    throw new Exception("invalid_card_type");
    }

    // =========================================
    // A. SECURITY CARD - RFID ONLY
    // =========================================

    if ($cardType === "security") {

        if ($cardStatus !== "active") {
            throw new Exception("security_card_inactive");
        }

        $conn->commit();

        respond(200, [
            "ok" => true,
            "decision" => "GRANTED",
            "source" => "SECURITY",
            "message" => "Security access granted"
        ]);
    }

    // =========================================
    // B. VISITOR CARD - TIME OUT
    // =========================================

    // Visitor cards require vehicle detection
    $irDetected = $body["ir_detected"] ?? false;

    if ($irDetected !== true && $irDetected !== 1) {
        throw new Exception("no_vehicle_detected");
    }

    if ($cardStatus === "assigned") {

        $visitId = (int)$card["visit_id"];

        if ($visitId <= 0) {
            throw new Exception("invalid_card_assignment");
        }

        // Find active visitor record
        $stmt = $conn->prepare(
            "SELECT id
             FROM visitor
             WHERE rfid_uid = ?
               AND visit_id = ?
               AND time_out IS NULL
             ORDER BY id DESC
             LIMIT 1
             FOR UPDATE"
        );

        $stmt->bind_param("si", $uid, $visitId);
        $stmt->execute();

        $record = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$record) {
            throw new Exception("active_visit_not_found");
        }

        $recordId = (int)$record["id"];

        // Record Time Out
        $stmt = $conn->prepare(
            "UPDATE visitor
             SET time_out = CURTIME()
             WHERE id = ? AND time_out IS NULL"
        );

        $stmt->bind_param("i", $recordId);
        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            $stmt->close();
            throw new Exception("timeout_update_failed");
        }

        $stmt->close();

        // Return visitor card to available status after Time Out
        $stmt = $conn->prepare(
            "UPDATE rfid_cards
            SET status = 'active',
                visit_id = NULL,
                assigned_at = NULL,
                completed_at = NOW()
            WHERE id = ? AND status = 'assigned'"
        );

        $stmt->bind_param("i", $cardId);
        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            $stmt->close();
            throw new Exception("card_update_failed");
        }

        $stmt->close();

        $conn->commit();

        respond(200, [
            "ok" => true,
            "decision" => "GRANTED",
            "action" => "TIME_OUT",
            "uid" => $uid,
            "message" => "Time Out recorded successfully"
        ]);
    }

    // Completed visitor cards cannot be reused
    if ($cardStatus === "completed") {
        throw new Exception("visitor_card_already_completed");
    }

    if ($cardStatus !== "active") {
        throw new Exception("visitor_card_unavailable");
    }

    // =========================================
    // C. VISITOR CARD - TIME IN
    // QR + RFID REQUIRED
    // =========================================

    $visitId = filter_var(
        $body["visit_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    $commandId = filter_var(
        $body["command_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    $commandToken = $body["command_token"] ?? "";

    if (!$visitId || !$commandId ||
        !is_string($commandToken) ||
        !preg_match('/^[a-f0-9]{64}$/', $commandToken)) {

        throw new Exception("qr_verification_required");
    }

    // Lock resident visit
    $stmt = $conn->prepare(
        "SELECT id, plate_no, status, visit_start, expires_at
         FROM resident_visits
         WHERE id = ?
         FOR UPDATE"
    );

    $stmt->bind_param("i", $visitId);
    $stmt->execute();

    $visit = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$visit) {
        throw new Exception("visit_not_found");
    }

    if ($visit["status"] !== "unused") {
        throw new Exception("qr_already_used_or_revoked");
    }

    // Check visit time
    $now = new DateTimeImmutable("now");
    $start = new DateTimeImmutable($visit["visit_start"]);
    $expiry = new DateTimeImmutable($visit["expires_at"]);

    if ($now < $start) {
        throw new Exception("not_yet_valid");
    }

    if ($now >= $expiry) {
        throw new Exception("visit_expired");
    }

    // Verify claimed QR command
    $stmt = $conn->prepare(
        "SELECT id
         FROM qr_gate_commands
         WHERE id = ?
           AND visit_id = ?
           AND command_token = ?
           AND status = 'claimed'
           AND expires_at > NOW()
         FOR UPDATE"
    );

    $stmt->bind_param(
        "iis",
        $commandId,
        $visitId,
        $commandToken
    );

    $stmt->execute();

    $command = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$command) {
        throw new Exception("qr_command_unavailable");
    }

    // Prevent duplicate active visitor records
    $stmt = $conn->prepare(
        "SELECT id
         FROM visitor
         WHERE rfid_uid = ?
           AND time_out IS NULL
         LIMIT 1
         FOR UPDATE"
    );

    $stmt->bind_param("s", $uid);
    $stmt->execute();

    $activeRecord = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($activeRecord) {
        throw new Exception("card_has_active_visit");
    }

    // Create visitor Time In record
    $plateNo = $visit["plate_no"] ?? "";

    $stmt = $conn->prepare(
        "INSERT INTO visitor
         (rfid_uid, visit_id, plate_no, date, time_in, time_out)
         VALUES (?, ?, ?, CURDATE(), CURTIME(), NULL)"
    );

    $stmt->bind_param(
        "sis",
        $uid,
        $visitId,
        $plateNo
    );

    $stmt->execute();
    $stmt->close();

    // Assign visitor card permanently
    $stmt = $conn->prepare(
        "UPDATE rfid_cards
         SET status = 'assigned',
             visit_id = ?,
             assigned_at = NOW()
         WHERE id = ? AND status = 'active'"
    );

    $stmt->bind_param("ii", $visitId, $cardId);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        $stmt->close();
        throw new Exception("card_assignment_failed");
    }

    $stmt->close();

    // Mark QR as used only after RFID verification
    $stmt = $conn->prepare(
        "UPDATE resident_visits
         SET status = 'used', used_at = NOW()
         WHERE id = ? AND status = 'unused'"
    );

    $stmt->bind_param("i", $visitId);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        $stmt->close();
        throw new Exception("qr_update_failed");
    }

    $stmt->close();

    // Complete QR command
    $stmt = $conn->prepare(
        "UPDATE qr_gate_commands
         SET status = 'completed',
             completed_at = NOW()
         WHERE id = ?
           AND command_token = ?
           AND status = 'claimed'"
    );

    $stmt->bind_param("is", $commandId, $commandToken);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        $stmt->close();
        throw new Exception("command_completion_failed");
    }

    $stmt->close();

    $conn->commit();

    respond(200, [
        "ok" => true,
        "decision" => "GRANTED",
        "action" => "TIME_IN",
        "source" => "VISITOR",
        "visit_id" => $visitId,
        "uid" => $uid,
        "message" => "QR and RFID verified. Time In recorded."
    ]);

} catch (Throwable $error) {

    $conn->rollback();

    $reason = $error->getMessage();

    $knownErrors = [
        "unknown_rfid",
        "security_card_inactive",
        "invalid_card_assignment",
        "active_visit_not_found",
        "timeout_update_failed",
        "card_update_failed",
        "visitor_card_already_completed",
        "visitor_card_unavailable",
        "qr_verification_required",
        "visit_not_found",
        "qr_already_used_or_revoked",
        "not_yet_valid",
        "visit_expired",
        "qr_command_unavailable",
        "card_has_active_visit",
        "card_assignment_failed",
        "qr_update_failed",
        "command_completion_failed",
        "no_vehicle_detected"
    ];

    if (!in_array($reason, $knownErrors, true)) {
        error_log("Visitor RFID error: " . $error->getMessage());
        $reason = "server_error";
        $httpCode = 500;
    } else {
        $httpCode = 403;
    }

    respond($httpCode, [
        "ok" => false,
        "decision" => "DENIED",
        "reason" => $reason
    ]);
}

$conn->close();
?>
