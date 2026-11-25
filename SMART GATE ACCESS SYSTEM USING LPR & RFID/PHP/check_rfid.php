
<?php
require_once "db.php";

header("Content-Type: application/json; charset=utf-8");

date_default_timezone_set("Asia/Kuala_Lumpur");

$apiKey = "SG_RFID_CHECK_2026_K8mP4xR7";
$providedKey = $_SERVER["HTTP_X_RFID_KEY"] ?? "";

if (!is_string($providedKey) ||
    !hash_equals($apiKey, $providedKey)) {
    http_response_code(401);
    echo json_encode([
        "ok" => false,
        "reason" => "unauthorized"
    ]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "ok" => false,
        "reason" => "method_not_allowed"
    ]);
    exit;
}

$body = json_decode(file_get_contents("php://input"), true);
$uid = strtoupper(trim((string)($body["rfid_uid"] ?? "")));

if (!preg_match('/^[A-F0-9]{8,20}$/', $uid)) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "reason" => "invalid_uid"
    ]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT id, card_name
     FROM rfid_cards
     WHERE rfid_uid = ?
       AND status = 'active'
     LIMIT 1"
);

$stmt->bind_param("s", $uid);
$stmt->execute();

$card = $stmt->get_result()->fetch_assoc();

$stmt->close();
$conn->close();

if (!$card) {
    http_response_code(403);
    echo json_encode([
        "ok" => false,
        "reason" => "card_not_authorized"
    ]);
    exit;
}

echo json_encode([
    "ok" => true,
    "card_id" => (int)$card["id"],
    "card_name" => $card["card_name"],
    "decision" => "card_active"
]);
?>
