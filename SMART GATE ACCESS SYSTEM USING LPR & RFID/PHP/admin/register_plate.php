
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

date_default_timezone_set("Asia/Kuala_Lumpur");

$message = "";
$messageType = "";

if (empty($_SESSION["plate_csrf"])) {
    $_SESSION["plate_csrf"] = bin2hex(random_bytes(32));
}

if (isset($_GET["updated"])) {
    $message = "Vehicle record updated successfully.";
    $messageType = "success";
} elseif (isset($_GET["deleted"])) {
    $message = "Vehicle record deleted successfully.";
    $messageType = "success";
} elseif (isset($_GET["notfound"])) {
    $message = "Vehicle record was not found.";
    $messageType = "error";
}

// Register resident and vehicle information

/* Register a vehicle for an approved resident */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrf = $_POST["csrf_token"] ?? "";
    $resident_id = filter_input(
        INPUT_POST,
        "resident_id",
        FILTER_VALIDATE_INT
    );
    $plate_no = strtoupper(
        preg_replace('/\s+/', '', trim($_POST["plate_no"] ?? ""))
    );

    if (!hash_equals($_SESSION["plate_csrf"], $csrf)) {
        $message = "Invalid request. Please refresh the page.";
        $messageType = "error";

    } elseif (!$resident_id || !preg_match('/^[A-Z0-9-]{1,20}$/', $plate_no)) {
        $message = "Please select a resident and enter a valid plate number.";
        $messageType = "error";

    } else {
        $stmt = $conn->prepare(
            "SELECT id, full_name, house_no
             FROM residents
             WHERE id = ? AND status = 'approved'
             LIMIT 1"
        );
        $stmt->bind_param("i", $resident_id);
        $stmt->execute();
        $resident = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$resident) {
            $message = "Resident not found or not approved.";
            $messageType = "error";

        } else {
            $stmt = $conn->prepare(
                "INSERT INTO registered_plates
                    (plate_no, resident_name, house_no, resident_id)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssi",
                $plate_no,
                $resident["full_name"],
                $resident["house_no"],
                $resident["id"]
            );

            if ($stmt->execute()) {
                $message = "Vehicle registered successfully.";
                $messageType = "success";
            } elseif ($stmt->errno === 1062) {
                $message = "This plate number is already registered.";
                $messageType = "error";
            } else {
                $message = "Unable to register vehicle. Please try again.";
                $messageType = "error";
            }

            $stmt->close();
        }
    }
}

/* Load approved residents for the dropdown */
$approvedResidents = $conn->query(
    "SELECT id, full_name, house_no
     FROM residents
     WHERE status = 'approved'
     ORDER BY full_name ASC"
);


// Display registered vehicle information
$plates = $conn->query(
    "SELECT id, plate_no, resident_name, house_no
     FROM registered_plates
     ORDER BY id DESC"
);

if (!$plates) {
    die("Unable to load registered plates.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Register Resident Vehicle | Smart Gate</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    color: #1e293b;
}

header {
    background: #0f172a;
    color: white;
    padding: 22px 5%;
}

header h1 {
    margin: 0;
}

header p {
    color: #cbd5e1;
    margin-bottom: 0;
}

main {
    width: 90%;
    max-width: 1000px;
    margin: 30px auto;
}

.panel {
    background: white;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 24px;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
}

label {
    display: block;
    margin: 16px 0 7px;
    font-weight: bold;
}

input,
select {
    width: 100%;
    padding: 13px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 15px;
    background: white;
}

input:focus,
select:focus {
    outline: 2px solid #93c5fd;
    border-color: #2563eb;
}

button,
.back {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 18px;
    border: none;
    border-radius: 8px;
    background: #2563eb;
    color: white;
    text-decoration: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
}

button:hover,
.back:hover {
    background: #1d4ed8;
}

.message {
    padding: 12px;
    border-radius: 8px;
    margin: 16px 0;
    line-height: 1.5;
}

.success {
    background: #dcfce7;
    color: #166534;
}

.error {
    background: #fee2e2;
    color: #991b1b;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 14px 12px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

th {
    background: #f8fafc;
    white-space: nowrap;
}

.empty {
    text-align: center;
    color: #64748b;
}

@media (max-width: 600px) {
    main {
        width: 94%;
    }

    .panel {
        padding: 18px;
    }
}
</style>
</head>

<body>

<header>
    <h1>Smart Gate — Register Resident Vehicle</h1>
    <p>Admin Portal</p>
</header>

<main>

    <p>
        <a class="back" href="index.php">
            Back to Admin Dashboard
        </a>
    </p>

    <section class="panel">

        <h2>Register Resident Vehicle</h2>
        <p>Enter the resident's details and vehicle plate number.</p>

        <?php if ($message !== ""): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </div>
        <?php endif; ?>

       
        <form method="POST" action="">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION["plate_csrf"], ENT_QUOTES, "UTF-8") ?>"
            >

            <label for="resident_id">Select Approved Resident</label>
            <select id="resident_id" name="resident_id" required>
                <option value="">-- Choose Resident --</option>

                <?php if ($approvedResidents): ?>
                    <?php while ($resident = $approvedResidents->fetch_assoc()): ?>
                        <option value="<?= (int)$resident["id"] ?>">
                            <?= htmlspecialchars(
                                $resident["full_name"] . " — " . $resident["house_no"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>

            <label for="plate_no">Vehicle Plate Number</label>
            <input
                type="text"
                id="plate_no"
                name="plate_no"
                maxlength="20"
                placeholder="e.g. AMY1234"
                style="text-transform: uppercase"
                required
            >

            <button type="submit">Register Plate</button>
        </form>


    </section>

    <section class="panel">

        <h2>Registered Resident Vehicles</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Resident Name</th>
                        <th>House Number</th>
                        <th>Plate Number</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                
                <tbody>
                    <?php if ($plates->num_rows > 0): ?>

                        <?php while ($plate = $plates->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?= (int)$plate["id"] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $plate["resident_name"] ?? "Not recorded",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $plate["house_no"] ?? "Not recorded",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $plate["plate_no"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <a href="edit_plate.php?id=<?= (int)$plate["id"] ?>"
                                    style="display:inline-block;padding:8px 12px;
                                            background:#2563eb;color:white;
                                            text-decoration:none;border-radius:6px;">
                                        Edit
                                    </a>

                                    <form method="POST"
                                        action="delete_plate.php"
                                        style="display:inline-block;"
                                        onsubmit="return confirm('Delete this vehicle record? This cannot be undone.');">

                                        <input type="hidden" name="id"
                                            value="<?= (int)$plate["id"] ?>">

                                        <input type="hidden" name="csrf_token"
                                            value="<?= htmlspecialchars(
                                                $_SESSION["plate_csrf"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>">

                                        <button type="submit"
                                                style="margin-top:0;background:#dc2626;">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty">
                                No registered vehicles found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>
        </div>

    </section>

</main>

</body>
</html>

<?php
$conn->close();
?>
