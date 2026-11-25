<?php

include "db.php";

date_default_timezone_set("Asia/Kuala_Lumpur");

$sql = "SELECT * FROM visitor ORDER BY id DESC";
$result = $conn->query($sql);

$records = [];

while ($row = $result->fetch_assoc()) {

    $records[] = [
        "id" => $row["id"],
        "rfid_uid" => $row["rfid_uid"],
        "plate_no" => $row["plate_no"],
        "date" => $row["date"],
        "time_in" => $row["time_in"],
        "time_out" => $row["time_out"]
    ];
}

$insideSql = "SELECT COUNT(*) AS total 
              FROM visitor 
              WHERE time_out IS NULL";

$insideResult = $conn->query($insideSql);
$insideRow = $insideResult->fetch_assoc();

$currentInside = $insideRow["total"];
$totalRecords = count($records);
$completed = $totalRecords - $currentInside;

echo json_encode([
    "totalRecords" => $totalRecords,
    "currentInside" => $currentInside,
    "completed" => $completed,
    "records" => $records
]);

$conn->close();

?>