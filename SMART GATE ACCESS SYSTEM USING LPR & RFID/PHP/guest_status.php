<?php
require_once __DIR__ . "/db.php";
date_default_timezone_set("Asia/Kuala_Lumpur");
function e($v){return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");}

$access = trim($_GET["access"] ?? "");
$guest = null;
$error = "";
$qrPayload = "";
$visitStatus = "";
$now = time();

if (!preg_match('/^[a-f0-9]{64}$/', $access)) {
    $error = "Invalid or incomplete status link.";
} else {
    $accessHash = hash("sha256", $access);
    $stmt = $conn->prepare(
        "SELECT g.id, g.guest_name, g.plate_no, g.status AS guest_status,
                ev.event_name, ev.event_date, ev.start_time, ev.end_time,
                ev.invite_token, rv.status AS visit_status
         FROM event_guests g
         INNER JOIN events ev ON ev.id = g.event_id
         LEFT JOIN resident_visits rv ON rv.id = g.qr_visit_id
         WHERE g.guest_access_hash = ?
         LIMIT 1"
    );
    $stmt->bind_param("s", $accessHash);
    $stmt->execute();
    $guest = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$guest) {
        $error = "Registration not found. Please check your status link.";
    } elseif ($guest["guest_status"] === "approved") {
        $start = strtotime($guest["event_date"] . " " . $guest["start_time"]);
        $end = strtotime($guest["event_date"] . " " . $guest["end_time"]);
        $visitStatus = $guest["visit_status"] ?? "unused";
        // Must match the token derivation in resident_event_guests.php.
        $qrToken = hash("sha256", "SG1-EVENT:" . $accessHash . ":" . $guest["invite_token"]);
        $qrPayload = "SG1:" . $qrToken;
        if ($visitStatus === "used") {
            $visitStatus = "used";
        } elseif ($now < $start) {
            $visitStatus = "not_yet_valid";
        } elseif ($now > $end) {
            $visitStatus = "expired";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Guest Status | Smart Gate</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#f1f5f9;color:#1e293b}header{background:#172554;color:white;padding:24px 6%}
main{max-width:650px;width:92%;margin:30px auto}.panel{background:white;padding:26px;border-radius:14px;box-shadow:0 4px 15px rgba(0,0,0,.06)}
.notice{padding:14px;border-radius:8px;background:#eff6ff;overflow-wrap:anywhere}.status{display:inline-block;padding:7px 12px;border-radius:20px;background:#e2e8f0;font-weight:bold}
.pending{background:#fef3c7;color:#92400e}.approved{background:#dcfce7;color:#166534}.rejected,.expired{background:#fee2e2;color:#991b1b}.muted{color:#64748b}
#qr{display:flex;justify-content:center;margin:20px auto;min-height:220px}#qr img{max-width:100%;height:auto}button{padding:11px 16px;border:0;border-radius:7px;background:#2563eb;color:white;font-weight:bold;cursor:pointer;margin:5px}
</style></head><body>
<header><h1>🏠 SMART GATE</h1><p>Event Guest Status</p></header><main><section class="panel">
<?php if($error !== ""): ?><h2>Guest Status</h2><p class="notice"><?=e($error)?></p>
<?php else: ?>
<h2><?=e($guest["event_name"])?></h2><p><b>Guest:</b> <?=e($guest["guest_name"])?></p><p><b>Vehicle:</b> <?=e($guest["plate_no"])?></p>
<p><b>Event date:</b> <?=e($guest["event_date"])?></p><p><b>Event time:</b> <?=e(substr($guest["start_time"],0,5))?> – <?=e(substr($guest["end_time"],0,5))?></p>
<p><b>Registration:</b> <span class="status <?=e($guest["guest_status"])?>"><?=e(ucfirst($guest["guest_status"]))?></span></p>
<?php if($guest["guest_status"] === "pending"): ?><p class="notice">Your registration is waiting for resident approval. Refresh this page later to check again.</p>
<?php elseif($guest["guest_status"] === "rejected"): ?><p class="notice">Your registration was rejected. Please contact the event resident if you need more information.</p>
<?php elseif($guest["guest_status"] === "approved"): ?>
<?php if($visitStatus === "used"): ?><p class="notice">This QR has already been used. It cannot be used again.</p>
<?php elseif($visitStatus === "not_yet_valid"): ?><p class="notice">Your registration is approved. Your QR is not valid yet. It becomes valid at the event start time.</p>
<?php elseif($visitStatus === "expired"): ?><p class="notice">The event time has ended. This QR is no longer valid.</p>
<?php else: ?><p class="notice">Your registration is approved. Show this QR code at the gate during the event time, together with your RFID credential.</p>
<div id="qr"></div><button type="button" onclick="downloadQR()">Download QR Code</button>
<input id="qrText" type="hidden" value="<?=e($qrPayload)?>">
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
</section></main>
<?php if($guest && $guest["guest_status"] === "approved" && $visitStatus !== "used" && $visitStatus !== "not_yet_valid" && $visitStatus !== "expired" && $error === ""): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const qrValue = document.getElementById('qrText').value;
new QRCode(document.getElementById('qr'), {text: qrValue, width: 220, height: 220, correctLevel: QRCode.CorrectLevel.M});
function downloadQR(){const img=document.querySelector('#qr img');if(!img){alert('QR is not ready yet.');return;}const a=document.createElement('a');a.href=img.src;a.download='SmartGate_Event_QR.png';a.click();}
</script><?php endif; ?>
</body></html>
<?php $conn->close(); ?>
