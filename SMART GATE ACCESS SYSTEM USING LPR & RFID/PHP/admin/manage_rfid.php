
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
$messageType = "success";

// CSRF protection
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($csrfToken, $_POST["csrf_token"])
    ) {
        $message = "Invalid security token.";
        $messageType = "error";

    } else {

        $action = $_POST["action"] ?? "";

        // ADD NEW RFID CARD
        if ($action === "add") {

            $uid = strtoupper(
                preg_replace('/\s+/', '', trim($_POST["rfid_uid"] ?? ""))
            );

            $cardName = trim($_POST["card_name"] ?? "");
            $cardType = $_POST["card_type"] ?? "";

            if (
                !preg_match('/^[A-F0-9]{8,20}$/', $uid) ||
                $cardName === "" ||
                !in_array($cardType, ["security", "visitor"], true)
            ) {
                $message = "Please enter valid card information.";
                $messageType = "error";

            } else {

                $check = $conn->prepare(
                    "SELECT id FROM rfid_cards WHERE rfid_uid = ? LIMIT 1"
                );

                $check->bind_param("s", $uid);
                $check->execute();

                $existing = $check->get_result()->fetch_assoc();
                $check->close();

                if ($existing) {

                    $message = "This RFID UID is already registered.";
                    $messageType = "error";

                } else {

                    $stmt = $conn->prepare(
                        "INSERT INTO rfid_cards
                         (rfid_uid, card_name, card_type, status)
                         VALUES (?, ?, ?, 'active')"
                    );

                    $stmt->bind_param(
                        "sss",
                        $uid,
                        $cardName,
                        $cardType
                    );

                    if ($stmt->execute()) {
                        $message = "RFID card added successfully.";
                    } else {
                        $message = "Unable to add RFID card.";
                        $messageType = "error";
                    }

                    $stmt->close();
                }
            }
        }

        // UPDATE CARD INFORMATION
        elseif ($action === "edit") {

            $cardId = filter_var(
                $_POST["card_id"] ?? null,
                FILTER_VALIDATE_INT
            );

            $cardName = trim($_POST["card_name"] ?? "");
            $cardType = $_POST["card_type"] ?? "";

            if (
                !$cardId ||
                $cardName === "" ||
                !in_array($cardType, ["security", "visitor"], true)
            ) {
                $message = "Invalid card information.";
                $messageType = "error";

            } else {

                $stmt = $conn->prepare(
                    "UPDATE rfid_cards
                     SET card_name = ?, card_type = ?
                     WHERE id = ?
                     AND status IN ('active', 'inactive')"
                );

                $stmt->bind_param(
                    "ssi",
                    $cardName,
                    $cardType,
                    $cardId
                );

                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "Card information updated.";
                } else {
                    $message = "No changes made or card cannot be edited.";
                    $messageType = "error";
                }

                $stmt->close();
            }
        }

        // ACTIVATE / DEACTIVATE CARD
        elseif ($action === "status") {

            $cardId = filter_var(
                $_POST["card_id"] ?? null,
                FILTER_VALIDATE_INT
            );

            $newStatus = $_POST["new_status"] ?? "";

            if (
                !$cardId ||
                !in_array($newStatus, ["active", "inactive"], true)
            ) {
                $message = "Invalid card status.";
                $messageType = "error";

            } else {

                $stmt = $conn->prepare(
                    "UPDATE rfid_cards
                     SET status = ?
                     WHERE id = ?
                     AND status IN ('active', 'inactive')"
                );

                $stmt->bind_param(
                    "si",
                    $newStatus,
                    $cardId
                );

                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "Card status updated.";
                } else {
                    $message = "Card cannot be changed.";
                    $messageType = "error";
                }

                $stmt->close();
            }
        }

        else {
            $message = "Invalid action.";
            $messageType = "error";
        }
    }
}

// Fetch RFID cards
$result = $conn->query(
    "SELECT id, rfid_uid, card_name, card_type,
            status, visit_id, created_at
     FROM rfid_cards
     ORDER BY id DESC"
);

$totalCards = $conn->query(
    "SELECT COUNT(*) AS total FROM rfid_cards"
)->fetch_assoc()["total"];

$activeCards = $conn->query(
    "SELECT COUNT(*) AS total
     FROM rfid_cards
     WHERE status = 'active'"
)->fetch_assoc()["total"];

$assignedCards = $conn->query(
    "SELECT COUNT(*) AS total
     FROM rfid_cards
     WHERE status = 'assigned'"
)->fetch_assoc()["total"];

?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>RFID Card Management</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f6f9;
    color: #222;
}

.header {
    background: linear-gradient(135deg, #111827, #1f2937);
    color: white;
    padding: 25px 5%;
}

.header h1 {
    margin: 0;
    font-size: 25px;
}

.header p {
    color: #cbd5e1;
    margin-bottom: 0;
}

.container {
    width: 90%;
    max-width: 1200px;
    margin: 30px auto;
}

.back-btn {
    display: inline-block;
    padding: 11px 16px;
    background: #64748b;
    color: white;
    text-decoration: none;
    border-radius: 7px;
    margin-bottom: 20px;
    font-weight: bold;
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 25px;
}

.stat-card, .panel {
    background: white;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
}

.stat-card h3 {
    margin: 0;
    color: #64748b;
    font-size: 13px;
    text-transform: uppercase;
}

.stat-card .number {
    font-size: 30px;
    font-weight: bold;
    margin-top: 10px;
}

.panel {
    margin-bottom: 25px;
}

.panel h2 {
    margin-top: 0;
    font-size: 20px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

label {
    display: block;
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 7px;
}

input, select {
    width: 100%;
    padding: 11px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font-size: 14px;
}

input:focus, select:focus {
    outline: none;
    border-color: #2563eb;
}

button {
    border: none;
    padding: 10px 14px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
}

.add-btn {
    background: #16a34a;
    color: white;
    margin-top: 17px;
}

.edit-btn {
    background: #2563eb;
    color: white;
}

.deactivate-btn {
    background: #dc2626;
    color: white;
}

.activate-btn {
    background: #16a34a;
    color: white;
}

.message {
    padding: 13px;
    margin-bottom: 20px;
    border-radius: 7px;
    background: #dcfce7;
    color: #166534;
}

.message.error {
    background: #fee2e2;
    color: #991b1b;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

th {
    background: #f8fafc;
    color: #475569;
    text-align: left;
    font-size: 12px;
    padding: 14px;
    border-bottom: 2px solid #e2e8f0;
}

td {
    padding: 14px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 13px;
}

.rfid {
    font-family: monospace;
    font-weight: bold;
}

.badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
}

.active {
    background: #dcfce7;
    color: #166534;
}

.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.assigned {
    background: #dbeafe;
    color: #1e40af;
}

.completed {
    background: #e2e8f0;
    color: #475569;
}

.security {
    background: #fef3c7;
    color: #92400e;
}

.visitor {
    background: #ede9fe;
    color: #5b21b6;
}

.action-form {
    margin-bottom: 7px;
}

.edit-fields {
    display: flex;
    gap: 5px;
    min-width: 210px;
}

.edit-fields input {
    min-width: 110px;
}

.edit-fields select {
    min-width: 100px;
}

.small-text {
    color: #64748b;
    font-size: 12px;
    margin-top: 10px;
}

@media(max-width: 750px) {
    .stats, .form-grid {
        grid-template-columns: 1fr;
    }
}

</style>

</head>

<body>

<div class="header">
    <h1>RFID Card Management</h1>
    <p>Smart Gate Access Management System</p>
</div>

<div class="container">

<a href="index.php" class="back-btn">
    ← Back to Dashboard
</a>

<?php if ($message !== ""): ?>
    <div class="message <?= $messageType === "error" ? "error" : "" ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="stats">

    <div class="stat-card">
        <h3>Total RFID Cards</h3>
        <div class="number">
            <?= (int)$totalCards ?>
        </div>
    </div>

    <div class="stat-card">
        <h3>Active Cards</h3>
        <div class="number">
            <?= (int)$activeCards ?>
        </div>
    </div>

    <div class="stat-card">
        <h3>Assigned Cards</h3>
        <div class="number">
            <?= (int)$assignedCards ?>
        </div>
    </div>

</div>

<!-- ADD CARD -->

<div class="panel">

    <h2>+ Add New RFID Card</h2>

    <form method="POST">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($csrfToken) ?>">

        <input type="hidden" name="action" value="add">

        <div class="form-grid">

            <div>
                <label>RFID UID</label>
                <input type="text"
                       name="rfid_uid"
                       placeholder="Example: A1B2C3D4"
                       maxlength="20"
                       required>
            </div>

            <div>
                <label>Card Name</label>
                <input type="text"
                       name="card_name"
                       placeholder="Example: Visitor Card 3"
                       maxlength="100"
                       required>
            </div>

            <div>
                <label>Card Type</label>
                <select name="card_type" required>
                    <option value="visitor">Visitor</option>
                    <option value="security">Security</option>
                </select>
            </div>

        </div>

        <button type="submit" class="add-btn">
            Add RFID Card
        </button>

        <p class="small-text">
            New cards will automatically receive Active status.
            UID must be unique.
        </p>

    </form>

</div>

<!-- RFID CARD LIST -->

<div class="panel">

    <h2>Registered RFID Cards</h2>

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>ID</th>
                    <th>RFID UID</th>
                    <th>Card Information</th>
                    <th>Status</th>
                    <th>Visit ID</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($result && $result->num_rows > 0): ?>

                <?php while ($card = $result->fetch_assoc()): ?>

                    <tr>

                        <td><?= (int)$card["id"] ?></td>

                        <td class="rfid">
                            <?= htmlspecialchars($card["rfid_uid"]) ?>
                        </td>

                        <td>

                            <form method="POST">

                                <input type="hidden"
                                       name="csrf_token"
                                       value="<?= htmlspecialchars($csrfToken) ?>">

                                <input type="hidden"
                                       name="action"
                                       value="edit">

                                <input type="hidden"
                                       name="card_id"
                                       value="<?= (int)$card["id"] ?>">

                                <div class="edit-fields">

                                    <input type="text"
                                           name="card_name"
                                           value="<?= htmlspecialchars($card["card_name"]) ?>"
                                           maxlength="100"
                                           required
                                           <?= !in_array($card["status"], ["active", "inactive"], true) ? "disabled" : "" ?>>

                                    <select name="card_type"
                                            <?= !in_array($card["status"], ["active", "inactive"], true) ? "disabled" : "" ?>>

                                        <option value="visitor"
                                            <?= $card["card_type"] === "visitor" ? "selected" : "" ?>>
                                            Visitor
                                        </option>

                                        <option value="security"
                                            <?= $card["card_type"] === "security" ? "selected" : "" ?>>
                                            Security
                                        </option>

                                    </select>

                                    <?php if (in_array($card["status"], ["active", "inactive"], true)): ?>

                                        <button class="edit-btn" type="submit">
                                            Save
                                        </button>

                                    <?php endif; ?>

                                </div>

                            </form>

                        </td>

                        <td>
                            <span class="badge <?= htmlspecialchars($card["status"]) ?>">
                                <?= strtoupper(htmlspecialchars($card["status"])) ?>
                            </span>
                        </td>

                        <td>
                            <?= $card["visit_id"] !== null
                                ? (int)$card["visit_id"]
                                : "-" ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($card["created_at"]) ?>
                        </td>

                        <td>

                            <?php if ($card["status"] === "active" || $card["status"] === "inactive"): ?>

                                <form method="POST" class="action-form">

                                    <input type="hidden"
                                           name="csrf_token"
                                           value="<?= htmlspecialchars($csrfToken) ?>">

                                    <input type="hidden"
                                           name="action"
                                           value="status">

                                    <input type="hidden"
                                           name="card_id"
                                           value="<?= (int)$card["id"] ?>">

                                    <?php if ($card["status"] === "active"): ?>

                                        <input type="hidden"
                                               name="new_status"
                                               value="inactive">

                                        <button class="deactivate-btn"
                                                type="submit">
                                            Deactivate
                                        </button>

                                    <?php else: ?>

                                        <input type="hidden"
                                               name="new_status"
                                               value="active">

                                        <button class="activate-btn"
                                                type="submit">
                                            Activate
                                        </button>

                                    <?php endif; ?>

                                </form>

                            <?php else: ?>

                                <span class="small-text">
                                    Locked
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="7" style="text-align:center;">
                        No RFID cards registered.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

    <p class="small-text">
        Assigned and completed visitor cards are locked
        to prevent accidental reuse or modification.
    </p>

</div>

</div>

</body>
</html>

<?php
$conn->close();
?>
