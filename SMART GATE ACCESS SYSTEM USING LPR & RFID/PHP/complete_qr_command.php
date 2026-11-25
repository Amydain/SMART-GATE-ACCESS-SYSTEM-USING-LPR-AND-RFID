
<?php
require_once "db.php";

header("Content-Type: application/json; charset=utf-8");

$esp32Key = "SG_QR_GATE_7xP9mK2vR8nL4wT6aB3cD5";
$providedKey = $_SERVER["HTTP_X_ESP32_KEY"] ?? "";

if (!is_string($providedKey) ||
    !hash_equals($esp32Key, $providedKey)) {
    http_response_code(401);
    echo json_encode(["ok" => false, "reason" => "unauthorized"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["ok" => false, "reason" => "method_not_allowed"]);
    exit;
}

$body = json_decode(file_get_contents("php://input"), true);
$id = filter_var($body["id"] ?? null, FILTER_VALIDATE_INT);
$token = $body["token"] ?? "";

if (!$id || !is_string($token) ||
    !preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "reason" => "invalid_request"]);
    exit;
}

$stmt = $conn->prepare(
    "UPDATE qr_gate_commands
     SET status = 'completed', completed_at = NOW()
     WHERE id = ? AND command_token = ?
       AND status = 'claimed' AND expires_at > NOW()"
);

$stmt->bind_param("is", $id, $token);
$stmt->execute();
$changed = $stmt->affected_rows;
$stmt->close();
$conn->close();

if ($changed !== 1) {
    http_response_code(403);
    echo json_encode(["ok" => false, "reason" => "command_not_available"]);
    exit;
}

echo json_encode(["ok" => true, "decision" => "completed"]);
?>
