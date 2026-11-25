
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

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// Verify that the resident account is still approved
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

if (empty($_SESSION["event_csrf"])) {
    $_SESSION["event_csrf"] = bin2hex(random_bytes(32));
}

$message = "";

// Process approval or rejection
$message = "";
$generatedToken = "";
$generatedQR = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrf = $_POST["csrf_token"] ?? "";
    $guestId = filter_input(INPUT_POST, "guest_id", FILTER_VALIDATE_INT);
    $action = $_POST["action"] ?? "";

    if (
        !is_string($csrf) ||
        !hash_equals($_SESSION["event_csrf"], $csrf)
    ) {
        $message = "Invalid request. Please refresh the page.";

    } elseif (
        !$guestId ||
        !in_array($action, ["approve", "reject"], true)
    ) {
        $message = "Invalid guest information.";

    } else {

        try {

            $conn->begin_transaction();

            // Get guest and event information
            $stmt = $conn->prepare(
                "SELECT
                    g.id,
                    g.guest_name,
                    g.plate_no,
                    g.status AS guest_status,
                    ev.id AS event_id,
                    ev.event_name,
                    ev.event_date,
                    ev.start_time,
                    ev.end_time,
                    ev.status AS event_status,
                    ev.invite_token
                 FROM event_guests g
                 INNER JOIN events ev ON ev.id = g.event_id
                 WHERE g.id = ?
                   AND ev.resident_id = ?
                 LIMIT 1
                 FOR UPDATE"
            );

            $stmt->bind_param("ii", $guestId, $residentId);
            $stmt->execute();

            $guest = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$guest || $guest["guest_status"] !== "pending") {
                throw new Exception("Guest not found or already processed.");
            }

            if ($action === "approve") {

                $eventEnd = $guest["event_date"] . " " . $guest["end_time"];

                if (
                    $guest["event_status"] !== "open" ||
                    strtotime($eventEnd) < time()
                ) {
                    throw new Exception("This event is no longer available for approval.");
                }

                // Derive a stable QR token from the guest's private access token
                // and the event invitation secret. Only hashes are stored.
                $stmt = $conn->prepare("SELECT guest_access_hash FROM event_guests WHERE id = ? LIMIT 1");
                $stmt->bind_param("i", $guestId);
                $stmt->execute();
                $accessRow = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$accessRow || empty($accessRow["guest_access_hash"])) {
                    throw new Exception("Guest status link is missing. Please ask the guest to register again.");
                }
                // Deterministic token: approval and guest status page can independently derive it.
                $generatedToken = hash("sha256", "SG1-EVENT:" . $accessRow["guest_access_hash"] . ":" . $guest["invite_token"]);
                $generatedQR = "SG1:" . $generatedToken;
                $tokenHash = hash("sha256", $generatedToken);

                $visitStart = $guest["event_date"] . " " . $guest["start_time"];
                $expiresAt = $guest["event_date"] . " " . $guest["end_time"];

                $visitorName = $guest["guest_name"];
                $plateNo = $guest["plate_no"];
                $visitorType = "Event Guest";
                $notes = "Event: " . $guest["event_name"];
                $status = "unused";

                // Insert approved guest into resident_visits
                $stmt = $conn->prepare(
                    "INSERT INTO resident_visits
                    (
                        resident_id,
                        visitor_name,
                        visitor_type,
                        plate_no,
                        visit_start,
                        expires_at,
                        notes,
                        qr_token_hash,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "issssssss",
                    $residentId,
                    $visitorName,
                    $visitorType,
                    $plateNo,
                    $visitStart,
                    $expiresAt,
                    $notes,
                    $tokenHash,
                    $status
                );

                $stmt->execute();
                $visitId = (int)$conn->insert_id;
                $stmt->close();

                // Link this visit to its event guest and mark approved atomically.
                $stmt = $conn->prepare(
                    "UPDATE event_guests
                     SET status = 'approved', qr_visit_id = ?
                     WHERE id = ? AND status = 'pending'"
                );

                $stmt->bind_param("ii", $visitId, $guestId);
                $stmt->execute();

                if ($stmt->affected_rows !== 1) {
                    throw new Exception("Unable to approve guest.");
                }

                $stmt->close();

                $message = "Guest approved. QR token generated successfully.";

            } else {

                // Reject guest
                $stmt = $conn->prepare(
                    "UPDATE event_guests
                     SET status = 'rejected'
                     WHERE id = ? AND status = 'pending'"
                );

                $stmt->bind_param("i", $guestId);
                $stmt->execute();

                if ($stmt->affected_rows !== 1) {
                    throw new Exception("Unable to reject guest.");
                }

                $stmt->close();

                $message = "Guest registration rejected.";
            }

            $conn->commit();

        } catch (Throwable $e) {

            $conn->rollback();

            $generatedToken = "";
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Retrieve events and guests belonging to the logged-in resident
$stmt = $conn->prepare(
    "SELECT
        ev.id AS event_id,
        ev.event_name,
        ev.event_date,
        ev.start_time,
        ev.end_time,
        ev.status AS event_status,
        g.id AS guest_id,
        g.guest_name,
        g.plate_no,
        g.status AS guest_status,
        g.created_at
     FROM events ev
     LEFT JOIN event_guests g ON g.event_id = ev.id
     WHERE ev.resident_id = ?
     ORDER BY ev.id DESC, g.id DESC"
);
$stmt->bind_param("i", $residentId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Guest Approval | Smart Gate</title>
<style>
* { box-sizing: border-box; }

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
    max-width: 1150px;
    width: 92%;
    margin: 30px auto;
}

.panel {
    background: white;
    padding: 22px;
    margin-bottom: 20px;
    border-radius: 14px;
    box-shadow: 0 4px 15px rgba(0,0,0,.05);
}

.notice {
    padding: 12px;
    background: #eff6ff;
    border-radius: 8px;
}

.table-wrap { overflow-x: auto; }

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

th, td {
    text-align: left;
    padding: 12px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 14px;
}

th {
    background: #f8fafc;
    color: #475569;
}

button, .button {
    display: inline-block;
    padding: 9px 12px;
    border: 0;
    border-radius: 6px;
    color: white;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
}

.approve { background: #15803d; }
.reject { background: #dc2626; }
.back { background: #2563eb; }

.badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    background: #e2e8f0;
    white-space: nowrap;
}

.pending { background: #fef3c7; color: #92400e; }
.approved { background: #dcfce7; color: #166534; }
.rejected { background: #fee2e2; color: #991b1b; }

form { display: inline-block; margin: 2px; }
.muted { color: #64748b; }
.qr-card { text-align: center; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin-top: 16px; }
#qrCode { display: flex; justify-content: center; align-items: center; min-height: 230px; margin: 15px auto; }
#qrCode img { max-width: 100%; height: auto; }
.qr-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }
</style>
</head>
<body>

<header>
    <h1>🏠 SMART GATE</h1>
    <p>Event Guest Approval</p>
</header>

<main>
    <section class="panel">
        <h2>Event Guest List</h2>
        <p class="muted">
            Review vehicle plate numbers and approve registrations for your own events.
        </p>

        <?php if ($message !== ""): ?>
            <p class="notice"><?= e($message) ?></p>
        <?php endif; ?>

        <?php if ($generatedToken !== ""): ?>
            <section class="panel">
                <h2>QR Token Generated</h2>

                <p>Guest has been approved. Scan or share this QR code:</p>

                <div class="qr-card">
                    <div id="qrCode" aria-label="Generated guest QR code"></div>
                    <div class="qr-actions">
                        <button type="button" onclick="downloadQRCode()" class="approve">
                            Download QR Code
                        </button>
                        <button type="button" onclick="copyQRToken()" class="back">
                            Copy QR Token
                        </button>
                    </div>
                </div>

                <p style="margin-top:16px;">QR token:</p>
                <input
                    type="text"
                    id="qrToken"
                    value="<?= e($generatedQR) ?>"
                    readonly
                    style="width:100%; padding:12px;"
                >

                <p class="muted">
                    Keep this QR code private. It is valid only during the event time and can be used once.
                </p>
            </section>
        <?php endif; ?>

        <a class="button back" href="resident_dashboard.php">
            ← Back to Dashboard
        </a>
    </section>

    <?php if ($result->num_rows > 0): ?>
        <section class="panel">
        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Guest Name</th>
                    <th>Vehicle Plate</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= e($row["event_name"]) ?></td>
                    <td><?= e($row["event_date"]) ?></td>
                    <td><?= e($row["guest_name"] ?? "-") ?></td>
                    <td><?= e($row["plate_no"] ?? "-") ?></td>
                    <td>
                        <?php if ($row["guest_id"] === null): ?>
                            <span class="muted">No guests registered yet</span>
                        <?php else: ?>
                            <span class="badge <?= e($row["guest_status"]) ?>">
                                <?= e(ucfirst($row["guest_status"])) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                    <?php if (
                        $row["guest_id"] !== null &&
                        $row["guest_status"] === "pending" &&
                        $row["event_status"] === "open" &&
                        strtotime($row["event_date"] . " " . $row["end_time"]) >= time()
                    ): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token"
                                value="<?= e($_SESSION["event_csrf"]) ?>">
                            <input type="hidden" name="guest_id"
                                value="<?= (int)$row["guest_id"] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button class="approve" type="submit">Approve</button>
                        </form>

                        <form method="POST">
                            <input type="hidden" name="csrf_token"
                                value="<?= e($_SESSION["event_csrf"]) ?>">
                            <input type="hidden" name="guest_id"
                                value="<?= (int)$row["guest_id"] ?>">
                            <input type="hidden" name="action" value="reject">
                            <button class="reject" type="submit">Reject</button>
                        </form>
                    <?php else: ?>
                        <span class="muted">No actions available</span>
                    <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
        </section>
    <?php else: ?>
        <section class="panel">
            <p>No events found. Create an event first to receive guest registrations.</p>
        </section>
    <?php endif; ?>

<?php if ($generatedToken !== ""): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    const qrPayload = <?= json_encode($generatedQR, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    // QR content follows the exact SG1:<64-character-token> format used by the scanner.
    new QRCode(document.getElementById("qrCode"), {
        text: qrPayload,
        width: 220,
        height: 220,
        correctLevel: QRCode.CorrectLevel.M
    });

    function downloadQRCode() {
        const qrImage = document.querySelector("#qrCode img");
        if (!qrImage || !qrImage.src) {
            alert("QR code is not ready yet. Please try again.");
            return;
        }

        const link = document.createElement("a");
        link.href = qrImage.src;
        link.download = "SmartGate_Event_QR.png";
        document.body.appendChild(link);
        link.click();
        link.remove();
    }

    function copyQRToken() {
        const token = document.getElementById("qrToken");

        navigator.clipboard.writeText(token.value)
            .then(() => alert("QR token copied successfully!"))
            .catch(() => alert("Unable to copy token."));
    }
</script>
<?php endif; ?>
</main>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
