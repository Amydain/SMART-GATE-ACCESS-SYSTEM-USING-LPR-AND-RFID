
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

if (empty($_SESSION["plate_csrf"])) {
    $_SESSION["plate_csrf"] = bin2hex(random_bytes(32));
}

$message = "";
$plateId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $plateId = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    $csrf = $_POST["csrf_token"] ?? "";

    if (
        !isset($_SESSION["plate_csrf"]) ||
        !hash_equals($_SESSION["plate_csrf"], $csrf)
    ) {
        die("Invalid request. Please return and try again.");
    }

    $residentName = trim($_POST["resident_name"] ?? "");
    $houseNo = trim($_POST["house_no"] ?? "");
    $plateNo = strtoupper(
        preg_replace('/\s+/', '', trim($_POST["plate_no"] ?? ""))
    );

    if (
        !$plateId ||
        $residentName === "" ||
        $houseNo === "" ||
        strlen($residentName) > 120 ||
        strlen($houseNo) > 100 ||
        !preg_match('/^[A-Z0-9-]{1,20}$/', $plateNo)
    ) {
        $message = "Please check all fields and try again.";
    } else {
        $stmt = $conn->prepare(
            "UPDATE registered_plates
             SET plate_no = ?, resident_name = ?, house_no = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "sssi",
            $plateNo,
            $residentName,
            $houseNo,
            $plateId
        );

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: register_plate.php?updated=1");
            exit;
        }

        $message = $stmt->errno === 1062
            ? "That plate number is already registered."
            : "Unable to update the record.";

        $stmt->close();
    }
}

$plate = null;

if ($plateId) {
    $stmt = $conn->prepare(
        "SELECT id, plate_no, resident_name, house_no
         FROM registered_plates
         WHERE id = ?
         LIMIT 1"
    );
    $stmt->bind_param("i", $plateId);
    $stmt->execute();
    $plate = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$plate) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Vehicle Plate | Smart Gate</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    color: #1e293b;
    margin: 0;
}
main {
    max-width: 600px;
    margin: 35px auto;
    padding: 25px;
    background: white;
    border-radius: 12px;
}
label {
    display: block;
    margin: 16px 0 7px;
    font-weight: bold;
}
input {
    box-sizing: border-box;
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
}
button, a {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 16px;
    border: 0;
    border-radius: 8px;
    background: #2563eb;
    color: white;
    text-decoration: none;
    cursor: pointer;
}
.message { color: #b91c1c; }
</style>
</head>
<body>
<main>
    <h1>Edit Vehicle Plate</h1>

    <?php if ($message !== ""): ?>
        <p class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <?php if ($plate): ?>
        <form method="POST">
            <input type="hidden" name="id"
                   value="<?= (int)$plate["id"] ?>">

            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION["plate_csrf"], ENT_QUOTES, "UTF-8") ?>">

            <label for="resident_name">Resident Name</label>
            <input id="resident_name" name="resident_name"
                   maxlength="120" required
                   value="<?= htmlspecialchars($plate["resident_name"] ?? "", ENT_QUOTES, "UTF-8") ?>">

            <label for="house_no">House Number</label>
            <input id="house_no" name="house_no"
                   maxlength="100" required
                   value="<?= htmlspecialchars($plate["house_no"] ?? "", ENT_QUOTES, "UTF-8") ?>">

            <label for="plate_no">Vehicle Plate Number</label>
            <input id="plate_no" name="plate_no"
                   maxlength="20" required
                   value="<?= htmlspecialchars($plate["plate_no"], ENT_QUOTES, "UTF-8") ?>">

            <button type="submit">Save Changes</button>
            <a href="register_plate.php">Cancel</a>
        </form>
    <?php else: ?>
        <p>Vehicle record not found.</p>
        <a href="register_plate.php">Back to Vehicle List</a>
    <?php endif; ?>
</main>
</body>
</html>
<?php $conn->close(); ?>
