
<?php
include "db.php";

date_default_timezone_set("Asia/Kuala_Lumpur");

$sql = "SELECT id, plate_no, status, confidence, detected_at
        FROM lpr_logs
        ORDER BY id DESC
        LIMIT 20";

$result = $conn->query($sql);

$logs = [];

while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

header("Content-Type: application/json");
echo json_encode($logs);

$conn->close();
?>
