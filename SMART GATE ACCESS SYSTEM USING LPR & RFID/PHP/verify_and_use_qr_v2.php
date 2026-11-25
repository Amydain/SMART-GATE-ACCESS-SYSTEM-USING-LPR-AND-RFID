
<?php
session_start();
require_once "db.php";

date_default_timezone_set("Asia/Kuala_Lumpur");

header("Content-Type: application/json; charset=utf-8");

// Hanya menerima POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "ok" => false,
        "reason" => "method_not_allowed"
    ]);
    exit;
}

// Pengesahan Python QR Scanner
$scannerKey = "SmartGateScanner_2026_Aimi_X9p7K2mQ";
$providedKey = $_SERVER["HTTP_X_SCANNER_KEY"] ?? "";

if (!is_string($providedKey) ||
    !hash_equals($scannerKey, $providedKey)) {

    http_response_code(401);
    echo json_encode([
        "ok" => false,
        "reason" => "scanner_unauthorized"
    ]);
    exit;
}

// Baca QR
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

    // Cari dan kunci rekod lawatan
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
        throw new Exception("unknown_qr");
    }

    // QR yang sudah digunakan atau dibatalkan tidak boleh digunakan
    if ($visit["status"] !== "unused") {
        throw new Exception("already_used_or_revoked");
    }

    // Semak tempoh lawatan
    $now = new DateTimeImmutable("now");
    $start = new DateTimeImmutable($visit["visit_start"]);
    $expiry = new DateTimeImmutable($visit["expires_at"]);

    if ($now < $start) {
        throw new Exception("not_yet_valid");
    }

    if ($now >= $expiry) {
        throw new Exception("expired");
    }

    $visitId = (int)$visit["id"];

    // Semak sama ada arahan QR aktif sudah wujud
    $check = $conn->prepare(
        "SELECT id
         FROM qr_gate_commands
         WHERE visit_id = ?
           AND status IN ('pending', 'claimed')
           AND expires_at > NOW()
         ORDER BY id DESC
         LIMIT 1
         FOR UPDATE"
    );

    $check->bind_param("i", $visitId);
    $check->execute();

    $existing = $check->get_result()->fetch_assoc();
    $check->close();

    if ($existing) {

        // Guna arahan sedia ada, elakkan duplicate command
        $commandId = (int)$existing["id"];

    } else {

        // Cipta arahan QR baharu
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
            $visitId,
            $commandToken,
            $commandExpiry
        );

        $command->execute();
        $commandId = $conn->insert_id;
        $command->close();
    }

    // PENTING:
    // resident_visits.status masih 'unused'.
    // QR belum digunakan sehingga RFID disahkan.

    $conn->commit();

    echo json_encode([
        "ok" => true,
        "visit_id" => $visitId,
        "command_id" => $commandId,
        "decision" => "qr_pending_rfid",
        "message" => "QR valid. Waiting for RFID verification."
    ]);

} catch (Throwable $error) {

    $conn->rollback();

    $reason = $error->getMessage();

    $httpCode = 403;

    if ($reason === "unknown_qr") {
        $httpCode = 403;
    } elseif ($reason === "not_yet_valid") {
        $httpCode = 403;
    } elseif ($reason === "expired") {
        $httpCode = 403;
    } elseif ($reason === "already_used_or_revoked") {
        $httpCode = 403;
    } else {
        $httpCode = 500;
        $reason = "server_error";
    }

    http_response_code($httpCode);

    echo json_encode([
        "ok" => false,
        "reason" => $reason
    ]);
}

$conn->close();
?>
