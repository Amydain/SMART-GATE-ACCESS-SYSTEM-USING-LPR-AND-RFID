<?php

include "db.php";

date_default_timezone_set("Asia/Kuala_Lumpur");

$rfid_uid = $_GET['rfid_uid'] ?? '';
$plate_no = $_GET['plate_no'] ?? '';
$status = $_GET['status'] ?? '';

$date = date("Y-m-d");
$time = date("H:i:s");

// Only record access when access is granted
if ($status == "Granted") {

    // Check if there is an active Time In without Time Out
    $check = "SELECT id FROM visitor
              WHERE rfid_uid = '$rfid_uid'
              AND time_out IS NULL
              ORDER BY id DESC
              LIMIT 1";

    $result = $conn->query($check);

    if ($result->num_rows > 0) {

        // Update Time Out
        $row = $result->fetch_assoc();
        $id = $row['id'];

        $sql = "UPDATE visitor
                SET time_out = '$time'
                WHERE id = '$id'";

        if ($conn->query($sql) === TRUE) {
            echo "Time Out recorded successfully";
        } else {
            echo "Error updating Time Out: " . $conn->error;
        }

    } else {

        // Create new Time In
        $sql = "INSERT INTO visitor
                (rfid_uid, plate_no, date, time_in, time_out)
                VALUES
                ('$rfid_uid', '$plate_no', '$date', '$time', NULL)";

        if ($conn->query($sql) === TRUE) {
            echo "Time In recorded successfully";
        } else {
            echo "Error inserting Time In: " . $conn->error;
        }
    }

} else {

    // Access denied - do not create a visitor record
    echo "Access Denied - No record inserted";
}

$conn->close();

?>