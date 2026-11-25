
<?php
require_once "db.php";

date_default_timezone_set("Asia/Kuala_Lumpur");
header("Content-Type: application/json; charset=utf-8");

$esp32Key = "SG_QR_GATE_7xP9mK2vR8nL4wT6aB3cD5";
$providedKey = $_SERVER["HTTP_X_ESP32_KEY"] ?? "";

if (!is_string($providedKey) ||
    !hash_equals($esp32Key, $providedKey)) {
    http_response_code(401);
    echo json_encode([
        "ok" => false,
        "reason" => "unauthorized"
    ]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode([
        "ok" => false,
        "reason" => "method_not_allowed"
    ]);
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare(
        "SELECT id, visit_id, command_token
         FROM qr_gate_commands
         WHERE status = 'pending'
           AND expires_at > NOW()
         ORDER BY id ASC
         LIMIT 1
         FOR UPDATE"
    );

    $stmt->execute();
    $command = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$command) {
        $conn->commit();
        echo json_encode([
            "ok" => true,
            "command" => null
        ]);
        exit;
    }

    $commandId = (int)$command["id"];

    $update = $conn->prepare(
        "UPDATE qr_gate_commands
         SET status = 'claimed', claimed_at = NOW()
         WHERE id = ?
           AND status = 'pending'
           AND expires_at > NOW()"
    );

    $update->bind_param("i", $commandId);
    $update->execute();
    $changed = $update->affected_rows;
    $update->close();

    if ($changed !== 1) {
        $conn->rollback();
        http_response_code(409);
        echo json_encode([
            "ok" => false,
            "reason" => "command_unavailable"
        ]);
        exit;
    }

    $conn->commit();

    echo json_encode([
        "ok" => true,
        "command" => [
            "id" => $commandId,
            "visit_id" => (int)$command["visit_id"],
            "token" => $command["command_token"]
        ]
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
