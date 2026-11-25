<?php

include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_POST["id"] ?? "";
    $plate_no = $_POST["plate_no"] ?? "";

    $id = intval($id);
    $plate_no = strtoupper(trim($plate_no));

    if ($id > 0 && $plate_no != "") {

        $stmt = $conn->prepare(
            "UPDATE visitor SET plate_no = ? WHERE id = ?"
        );

        $stmt->bind_param("si", $plate_no, $id);

        if ($stmt->execute()) {

            header("Location: index.php");
            exit;

        } else {

            echo "Error updating plate number.";

        }

        $stmt->close();

    } else {

        echo "Invalid plate number.";

    }

}

$conn->close();

?>