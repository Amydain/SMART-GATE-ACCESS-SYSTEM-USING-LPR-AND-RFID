
<?php
require_once __DIR__ . "/db.php";

date_default_timezone_set("Asia/Kuala_Lumpur");

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "ok" => false,
        "decision" => "DENIED",
        "reason" => "method_not_allowed"
    ]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$plate = strtoupper(
    preg_replace('/\s+/', '', trim($input["plate_no"] ?? ""))
);

if ($plate === "" || !preg_match('/^[A-Z0-9-]{1,20}$/', $plate)) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "decision" => "DENIED",
        "reason" => "invalid_plate"
    ]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT rp.plate_no
     FROM registered_plates rp
     INNER JOIN residents r ON r.id = rp.resident_id
     WHERE UPPER(rp.plate_no) = ?
       AND r.status = 'approved'
     LIMIT 1"
);

$stmt->bind_param("s", $plate);
$stmt->execute();
$approved = $stmt->get_result()->fetch_assoc();
$stmt->close();

$status = $approved ? "REGISTERED" : "DENIED";

$confidence = isset($input["confidence"])
    ? max(0, min(1, (float)$input["confidence"]))
    : null;

$log = $conn->prepare(
    "INSERT INTO lpr_logs (plate_no, status, confidence, detected_at)
     VALUES (?, ?, ?, NOW())"
);

$log->bind_param("ssd", $plate, $status, $confidence);
$logged = $log->execute();
$log->close();

if (!$logged) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "decision" => "DENIED",
        "reason" => "logging_failed"
    ]);
    $conn->close();
    exit;
}

echo json_encode([
    "ok" => true,
    "plate_no" => $plate,
    "decision" => $approved ? "GRANTED" : "DENIED",
    "reason" => $approved ? "approved_resident" : "plate_not_approved"
]);

$conn->close();
?>
