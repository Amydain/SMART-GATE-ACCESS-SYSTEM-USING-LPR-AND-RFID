<?php
session_start();
require_once __DIR__ . "/db.php";
date_default_timezone_set("Asia/Kuala_Lumpur");

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$message = "";
$event = null;
$success = false;
$statusUrl = "";
$token = trim($_GET["token"] ?? $_POST["token"] ?? "");

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    $message = "Invalid invitation link.";
} else {
    $stmt = $conn->prepare(
        "SELECT id, event_name, event_date, start_time, end_time, status
         FROM events WHERE invite_token = ? LIMIT 1"
    );
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$event) {
        $message = "Event not found or the invitation link is invalid.";
    } elseif ($event["status"] !== "open") {
        $message = "Registration for this event is closed.";
    } elseif (strtotime($event["event_date"] . " " . $event["end_time"]) < time()) {
        $message = "This event has ended.";
    } elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
        $guestName = trim($_POST["guest_name"] ?? "");
        $plateNo = strtoupper(preg_replace('/\s+/', '', trim($_POST["plate_no"] ?? "")));

        if ($guestName === "" || $plateNo === "") {
            $message = "Please enter your name and vehicle plate number.";
        } elseif (mb_strlen($guestName) > 120 || strlen($plateNo) > 20 || !preg_match('/^[A-Z0-9-]+$/', $plateNo)) {
            $message = "The name or vehicle plate number is invalid.";
        } else {
            $eventId = (int)$event["id"];
            $stmt = $conn->prepare(
                "SELECT id FROM event_guests WHERE event_id = ? AND plate_no = ? LIMIT 1"
            );
            $stmt->bind_param("is", $eventId, $plateNo);
            $stmt->execute();
            $duplicate = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($duplicate) {
                $message = "This vehicle plate number is already registered for this event.";
            } else {
                // A separate random bearer token lets the guest check their own application.
                $accessToken = bin2hex(random_bytes(32));
                $accessHash = hash("sha256", $accessToken);
                $stmt = $conn->prepare(
                    "INSERT INTO event_guests
                     (event_id, guest_name, plate_no, status, guest_access_hash)
                     VALUES (?, ?, ?, 'pending', ?)"
                );
                $stmt->bind_param("isss", $eventId, $guestName, $plateNo, $accessHash);

                if ($stmt->execute()) {
                    $success = true;
                    $message = "Your registration has been submitted for approval.";
                    $statusUrl = "guest_status.php?access=" . rawurlencode($accessToken);
                } else {
                    $message = "Registration failed. Please try again.";
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Guest Registration | Smart Gate</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#f1f5f9;color:#1e293b}
header{background:#172554;color:white;padding:24px 6%}main{max-width:650px;width:92%;margin:30px auto}
.panel{background:white;padding:26px;border-radius:14px;box-shadow:0 4px 15px rgba(0,0,0,.06)}
label{display:block;margin:16px 0 7px;font-weight:bold}input{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:7px;font-size:16px}
button,.button{display:inline-block;margin-top:20px;padding:12px 18px;border:0;border-radius:7px;background:#2563eb;color:white;font-weight:bold;text-decoration:none;cursor:pointer}
.notice{padding:14px;border-radius:8px;background:#eff6ff;overflow-wrap:anywhere}.muted{color:#64748b}.linkbox{display:block;padding:12px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;overflow-wrap:anywhere}
</style>
</head>
<body>
<header><h1>🏠 SMART GATE</h1><p>Event Guest Registration</p></header>
<main><section class="panel">
<?php if ($event): ?>
<h2><?= e($event["event_name"]) ?></h2>
<p class="muted">Date: <?= e($event["event_date"]) ?><br>Time: <?= e(substr($event["start_time"],0,5)) ?> – <?= e(substr($event["end_time"],0,5)) ?></p>
<?php else: ?><h2>Guest Registration</h2><?php endif; ?>
<p class="notice"><?= e($message) ?></p>
<?php if ($success): ?>
<h3>Thank You!</h3>
<p>Save this private status link. Open it later to check approval and view your QR code once approved.</p>
<a class="linkbox" href="<?= e($statusUrl) ?>"><?= e($statusUrl) ?></a>
<button type="button" onclick="copyStatusLink()">Copy Status Link</button>
<?php elseif ($event && $event["status"] === "open" && strtotime($event["event_date"] . " " . $event["end_time"]) >= time() && preg_match('/^[a-f0-9]{64}$/',$token)): ?>
<form method="POST">
<input type="hidden" name="token" value="<?= e($token) ?>">
<label for="guest_name">Guest Name</label><input id="guest_name" name="guest_name" maxlength="120" required autocomplete="name" placeholder="Enter your full name">
<label for="plate_no">Vehicle Plate Number</label><input id="plate_no" name="plate_no" maxlength="20" required placeholder="e.g. ABC1234" autocapitalize="characters">
<button type="submit">Submit Registration</button>
</form>
<p class="muted">Your registration will initially have Pending status. Gate access is only available after approval and during the event time.</p>
<?php endif; ?>
</section></main>
<?php if ($success): ?><script>
function copyStatusLink(){const link=new URL(<?= json_encode($statusUrl) ?>,window.location.href).href;navigator.clipboard.writeText(link).then(()=>alert('Status link copied!')).catch(()=>alert('Please copy the link manually.'));}
</script><?php endif; ?>
</body></html>
<?php $conn->close(); ?>
