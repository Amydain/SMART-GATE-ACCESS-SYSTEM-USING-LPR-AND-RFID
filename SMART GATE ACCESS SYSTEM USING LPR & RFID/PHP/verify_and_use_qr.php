
<?php
session_start();
require_once "db.php";

date_default_timezone_set("Asia/Kuala_Lumpur");

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "ok" => false,
        "reason" => "method_not_allowed"
    ]);
    exit;
}


    /*
    * Scanner authentication.
    * Keep the same secret in the Python scanner.
    * For deployment, store the secret outside the web root.
    */
    $scannerKey = "SmartGateScanner_2026_Aimi_X9p7K2mQ";

    $providedKey = $_SERVER["HTTP_X_SCANNER_KEY"] ?? "";

    if (!is_string($providedKey) || !hash_equals($scannerKey, $providedKey)) {
        http_response_code(401);
        echo json_encode([
            "ok" => false,
            "reason" => "scanner_unauthorized"
        ]);
        exit;
    }

    $body = json_decode(file_get_contents("php://input"), true);
    $qr = is_array($body) ? (string)($body["qr"] ?? "") : "";

if (!preg_match('/^SG1:([a-f0-9]{64})$/', $qr, $matches)) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "reason" => "invalid_qr"
    ]);
    exit;
}

$tokenHash = hash("sha256", $matches[1]);

$conn->begin_transaction();

try {
    // Kunci rekod supaya dua scan serentak tidak meluluskan QR yang sama.
    $stmt = $conn->prepare(
        "SELECT id, status, visit_start, expires_at
         FROM resident_visits
         WHERE qr_token_hash = ?
         FOR UPDATE"
    );

    $stmt->bind_param("s", $tokenHash);
    $stmt->execute();
    $visit = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$visit) {
        $conn->rollback();
        http_response_code(403);
        echo json_encode([
            "ok" => false,
            "reason" => "unknown_qr"
        ]);
        exit;
    }

    if ($visit["status"] !== "unused") {
        $conn->rollback();
        http_response_code(403);
        echo json_encode([
            "ok" => false,
            "reason" => "already_used_or_revoked"
        ]);
        exit;
    }



    $now = new DateTimeImmutable("now");
    $start = new DateTimeImmutable($visit["visit_start"]);
    $expiry = new DateTimeImmutable($visit["expires_at"]);

    if ($now < $start) {
        $conn->rollback();
        http_response_code(403);
        echo json_encode([
            "ok" => false,
            "reason" => "not_yet_valid"
        ]);
        exit;
    }

    if ($now >= $expiry) {
        $conn->rollback();
        http_response_code(403);
        echo json_encode([
            "ok" => false,
            "reason" => "expired"
        ]);
        exit;
    }

    $id = (int)$visit["id"];

    $update = $conn->prepare(
        "UPDATE resident_visits
         SET status = 'used', used_at = NOW()
         WHERE id = ? AND status = 'unused'"
    );

    $update->bind_param("i", $id);
    $update->execute();
    $changed = $update->affected_rows;
    $update->close();

    if ($changed !== 1) {
        $conn->rollback();
        http_response_code(403);
        echo json_encode([
            "ok" => false,
            "reason" => "already_used"
        ]);
        exit;
    }


    // Create a one-time gate command.
    $commandToken = bin2hex(random_bytes(32));
    $commandExpiry = (new DateTimeImmutable("+30 seconds"))
        ->format("Y-m-d H:i:s");

    $command = $conn->prepare(
        "INSERT INTO qr_gate_commands
         (visit_id, command_token, status, expires_at)
         VALUES (?, ?, 'pending', ?)"
    );

    $command->bind_param(
        "iss",
        $id,
        $commandToken,
        $commandExpiry
    );

    $command->execute();
    $commandId = $conn->insert_id;
    $command->close();

    $conn->commit();

    echo json_encode([
        "ok" => true,
        "visit_id" => $id,
        "decision" => "valid_single_use_qr",
        "command_id" => $commandId
    ]);


} catch (Throwable $error) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "reason" => "server_error"
    ]);
}

$conn->close();
?>
