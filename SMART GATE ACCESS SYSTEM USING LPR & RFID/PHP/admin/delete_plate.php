
<?php
session_start();

if (
    !isset($_SESSION["admin_id"]) ||
    ($_SESSION["admin_role"] ?? "") !== "admin"
) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register_plate.php");
    exit;
}

$csrf = $_POST["csrf_token"] ?? "";
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (
    !isset($_SESSION["plate_csrf"]) ||
    !hash_equals($_SESSION["plate_csrf"], $csrf) ||
    !$id
) {
    http_response_code(400);
    exit("Invalid request.");
}

$stmt = $conn->prepare(
    "DELETE FROM registered_plates WHERE id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();

$deleted = $stmt->affected_rows;
$stmt->close();
$conn->close();

header(
    "Location: register_plate.php?" .
    ($deleted === 1 ? "deleted=1" : "notfound=1")
);
exit;
?>
