
<?php
session_start();
require_once __DIR__ . "/../db.php";

date_default_timezone_set("Asia/Kuala_Lumpur");

$message = "";

if (!isset($_SESSION["login_csrf"])) {
    $_SESSION["login_csrf"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $csrf = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["login_csrf"], $csrf)) {
        $message = "Invalid request. Please refresh the page.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, full_name, password_hash, status
             FROM residents
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $resident = $result->fetch_assoc();
        $stmt->close();

        if ($resident && password_verify($password, $resident["password_hash"])) {

            if ($resident["status"] === "approved") {

                session_regenerate_id(true);

                $_SESSION["resident_id"] = (int)$resident["id"];
                $_SESSION["resident_name"] = $resident["full_name"];
                $_SESSION["resident_role"] = "resident";

                header("Location: resident_dashboard.php");
                exit;

            } elseif ($resident["status"] === "pending") {
                $message = "Your account is awaiting Admin approval.";

            } else {
                $message = "Your account has been deactivated.";
            }

        } else {
            $message = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resident Login | Smart Gate</title>

<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    color: #1e293b;
}

.container {
    width: 90%;
    max-width: 440px;
    margin: 70px auto;
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

h1 { margin-top: 0; }

.subtitle {
    color: #64748b;
    line-height: 1.5;
}

label {
    display: block;
    margin: 18px 0 7px;
    font-weight: bold;
    font-size: 14px;
}

input {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 15px;
}

button {
    width: 100%;
    padding: 13px;
    margin-top: 22px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
}

.message {
    margin-top: 18px;
    padding: 12px;
    background: #eff6ff;
    color: #1d4ed8;
    border-radius: 8px;
    line-height: 1.5;
    font-size: 14px;
}

.footer {
    margin-top: 20px;
    text-align: center;
    font-size: 14px;
}
</style>
</head>

<body>
<div class="container">

    <h1>🏠 Resident Login</h1>
    <p class="subtitle">
        Log in to manage your visitors and QR codes.
    </p>

    <?php if ($message !== ""): ?>
        <div class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION["login_csrf"], ENT_QUOTES, "UTF-8") ?>"
        >

        <label for="email">Email Address</label>
        <input
            type="email"
            id="email"
            name="email"
            autocomplete="username"
            required
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            required
        >

        <button type="submit">Log In</button>
    </form>

    <div class="footer">
        Don't have an account yet?
        <a href="resident_register_account.php">Register here</a>
    </div>

</div>
</body>
</html>
