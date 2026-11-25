
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

$residentId = (int)$_SESSION["resident_id"];
$visitId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$visitId || $visitId < 1) {
    http_response_code(400);
    exit("Invalid visitor record.");
}

// Verify that the resident account is still approved.
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

// Only retrieve a visitor record belonging to this resident.
$stmt = $conn->prepare(
    "SELECT id, visitor_name, visitor_type, plate_no,
            visit_start, expires_at, status, qr_token_hash
     FROM resident_visits
     WHERE id = ? AND resident_id = ?
     LIMIT 1"
);
$stmt->bind_param("ii", $visitId, $residentId);
$stmt->execute();
$visit = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$visit) {
    http_response_code(404);
    exit("Visitor record not found.");
}

$now = new DateTimeImmutable("now");
$start = new DateTimeImmutable($visit["visit_start"]);
$expiry = new DateTimeImmutable($visit["expires_at"]);

if ($visit["status"] === "used") {
    $message = "This QR Code has already been used.";
} elseif ($visit["status"] === "revoked") {
    $message = "This QR Code has been revoked.";
} elseif ($now >= $expiry) {
    $message = "This QR Code has expired.";
} elseif ($now < $start) {
    $message = "This QR Code is not valid yet.";
} else {
    $message = "";
}

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
* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    color: #1e293b;
}

.container {
    width: 92%;
    max-width: 620px;
    margin: 30px auto;
    padding: 25px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,.06);
    text-align: center;
}

.details {
    margin-top: 20px;
    padding: 15px;
    border-radius: 10px;
    background: #f8fafc;
    text-align: left;
    line-height: 1.9;
    overflow-wrap: anywhere;
}

#qrcode {
    display: inline-block;
    margin: 18px auto;
    padding: 12px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
}

.notice {
    padding: 12px;
    border-radius: 8px;
    background: #fee2e2;
    color: #991b1b;
    line-height: 1.5;
}

.button {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 17px;
    background: #2563eb;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
}
</style>
</head>
<body>
<main class="container">

    <h1>Visitor QR Code</h1>
    <p>Visitor Record #<?= (int)$visit["id"] ?></p>

    <?php if ($message === ""): ?>
        <div id="qrcode"></div>
        <p>Present this QR Code at the security checkpoint.</p>
    <?php else: ?>
        <p class="notice"><?= e($message) ?></p>
    <?php endif; ?>

    <div class="details">
        <b>Visitor:</b> <?= e($visit["visitor_name"] ?: "-") ?><br>
        <b>Type:</b> <?= e($visit["visitor_type"]) ?><br>
        <b>Plate No.:</b> <?= e($visit["plate_no"] ?: "Unknown") ?><br>
        <b>Visit Start:</b> <?= e($start->format("d/m/Y H:i")) ?><br>
        <b>Expires At:</b> <?= e($expiry->format("d/m/Y H:i")) ?>
    </div>

    <a class="button" href="resident_dashboard.php">
        Back to Dashboard
    </a>

</main>

<?php if ($message === ""): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById("qrcode"), {
    text: <?= json_encode(
        "SG1:" . bin2hex(random_bytes(32)),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?>,
    width: 240,
    height: 240
});
</script>
<?php endif; ?>

</body>
</html>
<?php $conn->close(); ?>
