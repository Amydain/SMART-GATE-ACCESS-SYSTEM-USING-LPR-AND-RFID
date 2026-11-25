
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

$message = "";
$createdLink = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $eventName = trim($_POST["event_name"] ?? "");
    $eventDate = $_POST["event_date"] ?? "";
    $startTime = $_POST["start_time"] ?? "";
    $endTime = $_POST["end_time"] ?? "";

    $dateValid = DateTime::createFromFormat("!Y-m-d", $eventDate);
    $startValid = DateTime::createFromFormat("!H:i", $startTime);
    $endValid = DateTime::createFromFormat("!H:i", $endTime);

    if (
        $eventName === "" ||
        !$dateValid || $dateValid->format("Y-m-d") !== $eventDate ||
        !$startValid || $startValid->format("H:i") !== $startTime ||
        !$endValid || $endValid->format("H:i") !== $endTime
    ) {
        $message = "Please enter all information correctly.";
    } elseif ($endTime <= $startTime) {
        $message = "The end time must be later than the start time on the same day.";
    } elseif (
        strtotime($eventDate . " " . $endTime) <= time()
    ) {
        $message = "The event end date and time must be in the future.";
    } else {
        try {
            $token = bin2hex(random_bytes(32));

            $stmt = $conn->prepare(
                "INSERT INTO events
                 (resident_id, event_name, event_date,
                  start_time, end_time, invite_token, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'open')"
            );

            $stmt->bind_param(
                "isssss",
                $residentId,
                $eventName,
                $eventDate,
                $startTime,
                $endTime,
                $token
            );

            $stmt->execute();
            $stmt->close();

            $scheme = (
                !empty($_SERVER["HTTPS"]) &&
                $_SERVER["HTTPS"] !== "off"
            ) ? "https" : "http";

            $host = $_SERVER["HTTP_HOST"] ?? "localhost";

            $createdLink = $scheme . "://" . $host .
                "/smart_gate/event_register.php?token=" .
                urlencode($token);

            $message = "Event created successfully!";
        } catch (Throwable $error) {
            error_log("Create event failed: " . $error->getMessage());
            $message = "Failed to create the event. Please try again.";
        }
    }
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
<title>Create Event | Smart Gate</title>
<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    color: #1e293b;
}
header {
    background: #172554;
    color: white;
    padding: 24px 6%;
}
main {
    max-width: 700px;
    width: 90%;
    margin: 30px auto;
}
.panel {
    background: white;
    padding: 24px;
    border-radius: 14px;
    box-shadow: 0 4px 15px rgba(0,0,0,.06);
}
label {
    display: block;
    margin: 16px 0 7px;
    font-weight: bold;
}
input {
    width: 100%;
    box-sizing: border-box;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
}
button, .button {
    display: inline-block;
    margin-top: 18px;
    padding: 12px 16px;
    border: 0;
    border-radius: 7px;
    background: #2563eb;
    color: white;
    text-decoration: none;
    font-weight: bold;
    cursor: pointer;
}
.notice {
    padding: 12px;
    background: #eff6ff;
    border-radius: 7px;
    overflow-wrap: anywhere;
}
.back {
    display: inline-block;
    margin-top: 18px;
}
</style>
</head>
<body>
<header>
    <h1>🏠 SMART GATE</h1>
    <p>Create an Event</p>
</header>

<main>
<section class="panel">
    <h2>Event Details</h2>
    <p>Enter the event details to generate a guest registration link.</p>

    <?php if ($message !== ""): ?>
        <p class="notice"><?= e($message) ?></p>
    <?php endif; ?>

    <?php if ($createdLink !== ""): ?>
        <h3>Invitation Link</h3>
        <p class="notice">
            <a href="<?= e($createdLink) ?>"><?= e($createdLink) ?></a>
        </p>
        <button type="button"
            onclick="navigator.clipboard.writeText(<?= json_encode($createdLink) ?>)">
            Copy Link
        </button>
        <p>Share this link with your guests. Each guest can enter their own name and vehicle plate number.</p>
    <?php endif; ?>

    <form method="POST">
        <label for="event_name">Event Name</label>
        <input id="event_name" name="event_name"
            maxlength="150" required
            placeholder="e.g. House A Family Gathering">

        <label for="event_date">Event Date</label>
        <input id="event_date" type="date"
            name="event_date" required>

        <label for="start_time">Start Time</label>
        <input id="start_time" type="time"
            name="start_time" required>

        <label for="end_time">End Time</label>
        <input id="end_time" type="time"
            name="end_time" required>

        <button type="submit">Create Event</button>
    </form>

    <a class="back" href="resident_dashboard.php">← Back to Dashboard</a>
</section>
</main>
</body>
</html>
