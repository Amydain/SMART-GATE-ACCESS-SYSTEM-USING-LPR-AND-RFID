
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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: resident_register.php");
    exit;
}

if (
    !isset($_SESSION["visit_csrf"], $_POST["csrf_token"]) ||
    !hash_equals($_SESSION["visit_csrf"], (string)$_POST["csrf_token"])
) {
    http_response_code(403);
    exit("Invalid request. Please return and try again.");
}

$residentId = (int)$_SESSION["resident_id"];

// Verify resident account status.
$stmt = $conn->prepare(
    "SELECT status FROM residents WHERE id = ? LIMIT 1"
);
$stmt->bind_param("i", $residentId);
$stmt->execute();
$resident = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$resident || $resident["status"] !== "approved") {
    http_response_code(403);
    exit("Your resident account has not been approved or has been disabled.");
}

// Retrieve and sanitize form input.
$name = trim($_POST["visitor_name"] ?? "");
$type = trim($_POST["visitor_type"] ?? "");

$plate = strtoupper(
    preg_replace('/\s+/', '', trim($_POST["plate_no"] ?? ""))
);

$date = trim($_POST["visit_date"] ?? "");
$time = trim($_POST["visit_time"] ?? "");

$duration = filter_var(
    $_POST["duration_minutes"] ?? null,
    FILTER_VALIDATE_INT
);

$notes = trim($_POST["notes"] ?? "");

$allowedTypes = [
    "Grab / Food Delivery",
    "Parcel / Courier",
    "Guest / Event",
    "Regular Visitor"
];

$allowedDurations = [15, 30, 60, 240];

if (
    $name === "" ||
    mb_strlen($name) > 100 ||
    mb_strlen($plate) > 20 ||
    mb_strlen($notes) > 500 ||
    !in_array($type, $allowedTypes, true) ||
    !in_array($duration, $allowedDurations, true) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ||
    !preg_match('/^\d{2}:\d{2}$/', $time)
) {
    http_response_code(400);
    exit("Invalid information. Please return and check the form.");
}

$start = DateTimeImmutable::createFromFormat(
    '!Y-m-d H:i',
    "$date $time"
);

$errors = DateTimeImmutable::getLastErrors();

if (
    !$start ||
    ($errors !== false &&
        ($errors["warning_count"] > 0 || $errors["error_count"] > 0)) ||
    $start->format("Y-m-d H:i") !== "$date $time"
) {
    http_response_code(400);
    exit("Invalid date or time.");
}

$now = new DateTimeImmutable("now");

if ($start < $now) {
    http_response_code(400);
    exit("The visit start time cannot be in the past.");
}

$expiry = $start->modify("+$duration minutes");

// Generate a random 256-bit token.
// Only the token hash is stored in the database.
$token = bin2hex(random_bytes(32));
$tokenHash = hash("sha256", $token);

$startSql = $start->format("Y-m-d H:i:s");
$expirySql = $expiry->format("Y-m-d H:i:s");

$sql = "INSERT INTO resident_visits
(
    resident_id, visitor_name, visitor_type, plate_no,
    visit_start, expires_at, notes, qr_token_hash, status
)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'unused')";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    exit("Unable to prepare the database query. Please check the resident_visits table.");
}

$stmt->bind_param(
    "isssssss",
    $residentId,
    $name,
    $type,
    $plate,
    $startSql,
    $expirySql,
    $notes,
    $tokenHash
);

if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    exit("Failed to save the visitor registration.");
}

$visitId = $stmt->insert_id;
$stmt->close();

// The QR contains only the random token, not visitor details.
$qrPayload = "SG1:" . $token;

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Visitor QR Code | Smart Gate</title>

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

.container {
    width: 92%;
    max-width: 600px;
    margin: 30px auto;
    background: white;
    padding: 25px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0,0,0,.06);
}

#qrcode {
    display: inline-block;
    padding: 12px;
    margin: 15px auto;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
}

.details {
    line-height: 1.9;
    text-align: left;
    background: #f8fafc;
    padding: 15px;
    border-radius: 10px;
    overflow-wrap: anywhere;
}

.note {
    color: #475569;
    font-size: 13px;
    line-height: 1.6;
}

.back-button {
    display: inline-block;
    margin-top: 18px;
    padding: 12px 16px;
    background: #2563eb;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
}

.actions {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 20px;
}

.action-button {
    display: inline-block;
    padding: 12px 18px;
    border: none;
    border-radius: 8px;
    background: #2563eb;
    color: white;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
}

.action-button.print {
    background: #0f766e;
}

@media print {
    body {
        background: white;
    }

    .container {
        box-shadow: none;
        margin: 0 auto;
    }

    .actions,
    .back-button {
        display: none;
    }
}
</style>
</head>

<body>
<main class="container">

    <h1>✅ Visitor QR Code Generated</h1>
    <p>Visit Reference #<?= (int)$visitId ?></p>

    <div id="qrcode"></div>

    <div class="actions">
        <button type="button" class="action-button" id="downloadQR">
            Download QR
        </button>

        <button
            type="button"
            class="action-button print"
            onclick="window.print()"
        >
            Print QR
        </button>
    </div>

    <div class="details">
        <b>Visitor Name:</b> <?= e($name) ?><br>
        <b>Visitor Type:</b> <?= e($type) ?><br>
        <b>Plate Number:</b> <?= e($plate !== "" ? $plate : "Unknown") ?><br>
        <b>Visit Start:</b> <?= e($start->format("d/m/Y H:i")) ?><br>
        <b>Expires At:</b> <?= e($expiry->format("d/m/Y H:i")) ?><br>
        <b>QR Status:</b> Unused
    </div>

    <p class="note">
        This QR Code is intended for single use and has an expiry time.
        Download or print it to share with your visitor.
        Keep the QR Code private to prevent unauthorized access.
    </p>

    <a class="back-button" href="resident_dashboard.php">
        Back to Dashboard
    </a>

</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const qrPayload = <?= json_encode(
    $qrPayload,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;

new QRCode(document.getElementById("qrcode"), {
    text: qrPayload,
    width: 240,
    height: 240,
    correctLevel: QRCode.CorrectLevel.H
});


document.getElementById("downloadQR").addEventListener("click", function () {
    const qrContainer = document.getElementById("qrcode");
    const sourceCanvas = qrContainer.querySelector("canvas");
    const sourceImage = qrContainer.querySelector("img");

    if (!sourceCanvas && !sourceImage) {
        alert("QR Code is not ready yet. Please try again.");
        return;
    }

    // Create a new canvas with a white quiet zone.
    const qrSize = 240;
    const quietZone = 24;

    const downloadCanvas = document.createElement("canvas");
    downloadCanvas.width = qrSize + (quietZone * 2);
    downloadCanvas.height = qrSize + (quietZone * 2);

    const ctx = downloadCanvas.getContext("2d");

    // White background.
    ctx.fillStyle = "#FFFFFF";
    ctx.fillRect(0, 0, downloadCanvas.width, downloadCanvas.height);

    // Draw the original QR with white margins.
    if (sourceCanvas) {
        ctx.drawImage(
            sourceCanvas,
            quietZone,
            quietZone,
            qrSize,
            qrSize
        );
    } else {
        ctx.drawImage(
            sourceImage,
            quietZone,
            quietZone,
            qrSize,
            qrSize
        );
    }

    // Download the new QR image.
    const link = document.createElement("a");
    link.href = downloadCanvas.toDataURL("image/png");
    link.download = "smart-gate-visitor-qr.png";

    document.body.appendChild(link);
    link.click();
    link.remove();
});

</script>

</body>
</html>
<?php $conn->close(); ?>
