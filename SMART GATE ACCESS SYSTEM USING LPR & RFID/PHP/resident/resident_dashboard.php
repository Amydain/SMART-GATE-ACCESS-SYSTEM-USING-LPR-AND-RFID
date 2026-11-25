
<?php
session_start();
require_once __DIR__ . "/../db.php";

date_default_timezone_set("Asia/Kuala_Lumpur");

// Pastikan resident sudah login
if (
    !isset($_SESSION["resident_id"]) ||
    ($_SESSION["resident_role"] ?? "") !== "resident"
) {
    header("Location: resident_login.php");
    exit;
}

$residentId = (int) $_SESSION["resident_id"];

// Semak status akaun setiap kali dashboard dibuka
$stmt = $conn->prepare(
    "SELECT full_name, house_no, status
     FROM residents
     WHERE id = ?
     LIMIT 1"
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

// Ambil rekod lawatan milik resident ini sahaja
$stmt = $conn->prepare(
    "SELECT id, visitor_name, visitor_type, plate_no,
            visit_start, expires_at, status
     FROM resident_visits
     WHERE resident_id = ?
     ORDER BY id DESC
     LIMIT 10"
);
$stmt->bind_param("i", $residentId);
$stmt->execute();
$visits = $stmt->get_result();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resident Dashboard | Smart Gate</title>
<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    color: #1e293b;
}

.header {
    background: linear-gradient(135deg, #111827, #1e3a5f);
    color: white;
    padding: 24px 6%;
}

.header h1 { margin: 0; }
.header p { color: #cbd5e1; }

.container {
    width: 90%;
    max-width: 1100px;
    margin: 30px auto;
}

.welcome, .panel {
    background: white;
    padding: 24px;
    border-radius: 14px;
    margin-bottom: 22px;
    box-shadow: 0 4px 15px rgba(0,0,0,.05);
}




.actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 18px;
    width: 100%;
    align-items: stretch;
}

.actions .action {
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
    background: white;
    padding: 22px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
}

@media (max-width: 900px) {
    .actions {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 650px) {
    .actions {
        grid-template-columns: 1fr;
    }
}




.action {
    background: white;
    padding: 22px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
}

.action h3 { margin-top: 0; }

.button {
    display: inline-block;
    margin-top: 10px;
    padding: 12px 16px;
    border-radius: 8px;
    background: #2563eb;
    color: white;
    text-decoration: none;
    font-weight: bold;
}

.logout { background: #dc2626; }

.table-wrap { overflow-x: auto; }

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 700px;
}

th, td {
    padding: 12px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    font-size: 14px;
}

th {
    background: #f8fafc;
    color: #475569;
}

.muted { color: #64748b; }


.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    white-space: nowrap;
}

.status-badge.unused {
    background: #dcfce7;
    color: #166534;
}

.status-badge.used {
    background: #dbeafe;
    color: #1d4ed8;
}

.status-badge.expired {
    background: #fee2e2;
    color: #991b1b;
}

.status-badge.revoked {
    background: #f3e8ff;
    color: #6b21a8;
}


@media (max-width: 650px) {
    .actions { grid-template-columns: 1fr; }
    .welcome, .panel { padding: 17px; }
}
</style>
</head>

<body>

<header class="header">
    <h1>🏠 SMART GATE</h1>
    <p>Resident Visitor Management</p>
</header>

<main class="container">

    <section class="welcome">

        <h2>Welcome, <?= e($resident["full_name"]) ?>!</h2>
        <p class="muted">
            House No.: <?= e($resident["house_no"]) ?>
        </p>
        <p>Manage your visitor registrations and view your visit history here.</p>
        <a class="button logout" href="resident_logout.php">Log out</a>
    </section>




<section class="actions">

    <div class="action">
        <h3>🚗 Register Visitor</h3>
        <p class="muted">
            Register Grab drivers, parcel couriers, guests, or other visitors.
        </p>
        <a class="button" href="resident_register.php">
            Register Visitor
        </a>
    </div>

    <div class="action">
        <h3>🎉 Create Event / Kenduri</h3>
        <p class="muted">
            Create an event and share one registration link with all guests.
        </p>
        <a class="button" href="resident_create_event.php">
            Create Event
        </a>
    </div>

    <div class="action">
        <h3>👥 Event Guest Approval</h3>
        <p class="muted">
            Review guest registrations and approve or reject visitors for your events.
        </p>
        <a class="button" href="resident_event_guests.php">
            Manage Guest Approval
        </a>
    </div>

    <div class="action">
        <h3>📱 QR Code & History</h3>
        <p class="muted">
            Check QR Code status and review your registered visitor records.
        </p>
        <span class="muted">Visitor history is displayed below.</span>
    </div>

</section>




    <section class="panel">
        <h2>Visitor Registration History</h2>
         <p class="muted">Your 10 most recent visitor records.</p>

        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Visitor Name</th>
                    <th>Visitor Type</th>
                    <th>Plate No.</th>
                    <th>Visit Time</th>
                    <th>Expires At</th>
                    <th>QR Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($visits->num_rows > 0): ?>
                <?php while ($visit = $visits->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$visit["id"] ?></td>
                    <td><?= e($visit["visitor_name"] ?: "-") ?></td>
                    <td><?= e($visit["visitor_type"]) ?></td>
                    <td><?= e($visit["plate_no"] ?: "-") ?></td>
                    <td><?= e($visit["visit_start"]) ?></td>
                    <td><?= e($visit["expires_at"]) ?></td>
                    <td>
                        <?php
                        $qrStatus = $visit["status"] ?? "unused";
                        $expiryTime = strtotime($visit["expires_at"] ?? "");

                        if ($qrStatus === "used") {
                            $statusLabel = "Used";
                            $statusClass = "used";
                        } elseif ($qrStatus === "revoked") {
                            $statusLabel = "Revoked";
                            $statusClass = "revoked";
                        } elseif ($expiryTime !== false && time() >= $expiryTime) {
                            $statusLabel = "Expired";
                            $statusClass = "expired";
                        } else {
                            $statusLabel = "Unused";
                            $statusClass = "unused";
                        }
                        ?>
                        <span class="status-badge <?= e($statusClass) ?>">
                            <?= e($statusLabel) ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="muted">
                        No visitors registered yet.
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
$stmt->close();
$conn->close();
?>
