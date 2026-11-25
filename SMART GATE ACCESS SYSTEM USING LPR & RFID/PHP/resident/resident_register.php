
<?php
session_start();
require_once __DIR__ . "/../db.php";

date_default_timezone_set("Asia/Kuala_Lumpur");

if (
    !isset($_SESSION["resident_id"]) ||
    ($_SESSION["resident_role"] ?? "") !== "resident"
) {
    header("Location: resident_login.php");
    exit;
}

$residentId = (int) $_SESSION["resident_id"];

$stmt = $conn->prepare(
    "SELECT status FROM residents WHERE id = ? LIMIT 1"
);
$stmt->bind_param("i", $residentId);
$stmt->execute();
$resident = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$resident || $resident["status"] !== "approved") {
    session_unset();
    session_destroy();
    header("Location: resident_login.php");
    exit;
}

if (!isset($_SESSION["visit_csrf"])) {
    $_SESSION["visit_csrf"] = bin2hex(random_bytes(32));
}

$today = date("Y-m-d");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Register Visitor | Smart Gate</title>

<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    color: #1e293b;
}

.container {
    max-width: 720px;
    width: 92%;
    margin: 30px auto;
    background: white;
    padding: 28px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,.06);
}

h1 { margin-top: 0; }

.subtitle {
    color: #64748b;
    line-height: 1.5;
}

label {
    display: block;
    margin: 17px 0 7px;
    font-weight: bold;
    font-size: 14px;
}

input, select, textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font: inherit;
}

.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

button, .back {
    display: inline-block;
    margin-top: 22px;
    padding: 13px 18px;
    border: 0;
    border-radius: 8px;
    background: #2563eb;
    color: white;
    text-decoration: none;
    font-weight: bold;
    cursor: pointer;
}

button { width: 100%; }

.back {
    background: #475569;
}

.note {
    background: #eff6ff;
    color: #1d4ed8;
    padding: 13px;
    border-radius: 9px;
    font-size: 13px;
    line-height: 1.5;
}

@media(max-width: 560px) {
    .grid { grid-template-columns: 1fr; }
    .container { padding: 20px; }
}
</style>
</head>

<body>
<main class="container">

    <h1>🚗 Register Visitor / Delivery</h1>

    <p class="subtitle">
        Register a visitor to manage access to your residence.
    </p>

    <div class="note">
        Each QR Code has a validity period and is designed for single use.
        The vehicle plate number may be left blank if it is not yet known.
    </div>

    <form action="save_resident_visit.php" method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION["visit_csrf"], ENT_QUOTES, "UTF-8") ?>"
        >

        <label for="visitor_name">Visitor / Delivery Rider Name</label>
        <input
            type="text"
            id="visitor_name"
            name="visitor_name"
            maxlength="100"
            placeholder="e.g. Ali"
            required
        >

        <label for="visitor_type">Visitor Type</label>
        <select id="visitor_type" name="visitor_type" required>
            <option value="Grab / Food Delivery">Grab / Food Delivery</option>
            <option value="Parcel / Courier">Parcel / Courier</option>
            <option value="Regular Visitor">Regular Visitor</option>
        </select>

        <label for="plate_no">Vehicle Plate Number (if known)</label>
        <input
            type="text"
            id="plate_no"
            name="plate_no"
            maxlength="20"
            placeholder="e.g. ABC1234"
        >

        <div class="grid">
            <div>
                <label for="visit_date">Visit Date</label>
                <input
                    type="date"
                    id="visit_date"
                    name="visit_date"
                    min="<?= htmlspecialchars($today, ENT_QUOTES, "UTF-8") ?>"
                    value="<?= htmlspecialchars($today, ENT_QUOTES, "UTF-8") ?>"
                    required
                >
            </div>

            <div>
                <label for="visit_time">Visit Time</label>
                <input
                    type="time"
                    id="visit_time"
                    name="visit_time"
                    value="12:00"
                    required
                >
            </div>
        </div>

        <label for="duration_minutes">QR Code Validity Period</label>
        <select id="duration_minutes" name="duration_minutes" required>
            <option value="15">15 minutes</option>
            <option value="30" selected>30 minutes</option>
            <option value="60">1 hour</option>
            <option value="240">4 hours - more</option>
        </select>

        <label for="notes">Notes (Optional)</label>
        <textarea
            id="notes"
            name="notes"
            rows="3"
            maxlength="500"
            placeholder="e.g. Food delivery to house A-12"
        ></textarea>

        <button type="submit">Register Visitor & Generate QR</button>
    </form>

    <a class="back" href="resident_dashboard.php">
        Back to Dashboard
    </a>

</main>
</body>
</html>
