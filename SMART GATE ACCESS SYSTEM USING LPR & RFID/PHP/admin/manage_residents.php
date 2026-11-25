
<?php
session_start();
require_once __DIR__ . "/../db.php";

if (
    !isset($_SESSION["admin_id"]) ||
    ($_SESSION["admin_role"] ?? "") !== "admin"
) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION["resident_admin_csrf"])) {
    $_SESSION["resident_admin_csrf"] = bin2hex(random_bytes(32));
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrf = $_POST["csrf_token"] ?? "";
    $residentId = filter_input(INPUT_POST, "resident_id", FILTER_VALIDATE_INT);
    $action = $_POST["action"] ?? "";

    if (!hash_equals($_SESSION["resident_admin_csrf"], $csrf)) {
        $message = "Invalid request. Please refresh the page.";
    } elseif (!$residentId || !in_array($action, ["approve", "reject"], true)) {
        $message = "Invalid resident or action.";
    } else {
        $newStatus = $action === "approve" ? "approved" : "rejected";

        $stmt = $conn->prepare(
            "UPDATE residents
             SET status = ?
             WHERE id = ? AND status = 'pending'"
        );
        $stmt->bind_param("si", $newStatus, $residentId);

        if ($stmt->execute() && $stmt->affected_rows === 1) {
            $message = $action === "approve"
                ? "Resident account approved successfully."
                : "Resident application rejected.";
        } else {
            $message = "No pending application was changed.";
        }

        $stmt->close();
    }
}

$result = $conn->query(
    "SELECT id, full_name, house_no, email, status
     FROM residents
     ORDER BY CASE WHEN status = 'pending' THEN 0 ELSE 1 END, id DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Residents | Smart Gate</title>
<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 32px 20px;
    font-family: Inter, "Segoe UI", Arial, sans-serif;
    background: #f1f5f9;
    color: #0f172a;
}

.container {
    max-width: 1180px;
    width: 100%;
    margin: 0 auto;
    padding: 32px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    box-shadow: 0 10px 35px rgba(15, 23, 42, 0.06);
    overflow-x: auto;
}

.back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 24px;
    color: #2563eb;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}

.back:hover {
    color: #1d4ed8;
}

h1 {
    margin: 0 0 10px;
    font-size: clamp(25px, 3vw, 34px);
    letter-spacing: -0.8px;
}

.container > p {
    margin: 0 0 28px;
    color: #64748b;
    line-height: 1.6;
}

.notice {
    padding: 14px 16px;
    margin: 0 0 24px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1d4ed8;
    border-radius: 10px;
    font-size: 14px;
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 14px;
}

thead th {
    padding: 16px;
    background: #f8fafc;
    color: #475569;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    text-align: left;
    border-top: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}

thead th:first-child {
    border-radius: 10px 0 0 0;
}

thead th:last-child {
    border-radius: 0 10px 0 0;
}

tbody td {
    padding: 18px 16px;
    border-bottom: 1px solid #edf2f7;
    vertical-align: middle;
}

tbody tr:hover {
    background: #f8fafc;
}

tbody tr:last-child td {
    border-bottom: 0;
}

.status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.status.pending {
    background: #fef3c7;
    color: #92400e;
}

.status.approved {
    background: #dcfce7;
    color: #166534;
}

.status.rejected,
.status.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.actions form {
    margin: 0;
}

button {
    padding: 10px 14px;
    border: 0;
    border-radius: 8px;
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.15s, opacity 0.15s;
}

button:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.approve {
    background: #059669;
}

.reject {
    background: #dc2626;
}

.back:focus-visible,
button:focus-visible {
    outline: 3px solid #93c5fd;
    outline-offset: 3px;
}

@media (max-width: 700px) {
    body {
        padding: 14px;
    }

    .container {
        padding: 20px 16px;
        border-radius: 14px;
    }

    table {
        min-width: 760px;
    }
}

</style>
</head>
<body>
<div class="container">
    <a class="back" href="index.php">← Back to Admin Dashboard</a>
    <h1>Manage Resident Accounts</h1>
    <p>Review resident registrations and approve or reject pending applications.</p>

    <?php if ($message !== ""): ?>
        <div class="notice">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Resident Name</th>
                <th>House Number</th>
                <th>Email</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($resident = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$resident["id"] ?></td>
                    <td><?= htmlspecialchars($resident["full_name"], ENT_QUOTES, "UTF-8") ?></td>
                    <td><?= htmlspecialchars($resident["house_no"], ENT_QUOTES, "UTF-8") ?></td>
                    <td><?= htmlspecialchars($resident["email"], ENT_QUOTES, "UTF-8") ?></td>
                    <td class="status <?= htmlspecialchars($resident["status"], ENT_QUOTES, "UTF-8") ?>">
                        <?= htmlspecialchars(ucfirst($resident["status"]), ENT_QUOTES, "UTF-8") ?>
                    </td>
                    <td>
                        <?php if ($resident["status"] === "pending"): ?>
                            <div class="actions">
                                <?php foreach (["approve" => "Approve", "reject" => "Reject"] as $action => $label): ?>
                                    <form method="POST" onsubmit="return confirm('<?= $label ?> this resident application?');">
                                        <input type="hidden" name="csrf_token"
                                            value="<?= htmlspecialchars($_SESSION["resident_admin_csrf"], ENT_QUOTES, "UTF-8") ?>">
                                        <input type="hidden" name="resident_id" value="<?= (int)$resident["id"] ?>">
                                        <input type="hidden" name="action" value="<?= $action ?>">
                                        <button class="<?= $action ?>" type="submit"><?= $label ?></button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No resident accounts found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>
