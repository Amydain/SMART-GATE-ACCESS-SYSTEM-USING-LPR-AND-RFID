
<?php
session_start();
require_once __DIR__ . "/../db.php";

$message = "";

if (!isset($_SESSION["admin_login_csrf"])) {
    $_SESSION["admin_login_csrf"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $csrf = $_POST["csrf_token"] ?? "";

    if (
        !isset($_SESSION["admin_login_csrf"]) ||
        !hash_equals($_SESSION["admin_login_csrf"], $csrf)
    ) {
        $message = "Invalid request. Please refresh the page.";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, full_name, email, password_hash, status
             FROM admins
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if (
            $admin &&
            $admin["status"] === "active" &&
            password_verify($password, $admin["password_hash"])
        ) {
            session_regenerate_id(true);

            $_SESSION["admin_id"] = (int) $admin["id"];
            $_SESSION["admin_name"] = $admin["full_name"];
            $_SESSION["admin_role"] = "admin";

            header("Location: index.php");
            exit;
        }

        $message = "Invalid email or password, or the account is inactive.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | Smart Gate</title>
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
    max-width: 420px;
    margin: 70px auto;
    padding: 30px;
    background: white;
    border-radius: 14px;
    box-shadow: 0 5px 20px rgba(0,0,0,.08);
}

h1 { margin-top: 0; }

label {
    display: block;
    margin: 18px 0 7px;
    font-weight: bold;
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
    margin-top: 22px;
    padding: 13px;
    background: #0f172a;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
}

.message {
    padding: 12px;
    background: #fee2e2;
    color: #991b1b;
    border-radius: 8px;
    font-size: 14px;
}
</style>
</head>
<body>
<div class="container">
    <h1>Smart Gate Admin</h1>
    <p>Sign in to access the Admin Portal.</p>

    <?php if ($message !== ""): ?>
        <p class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION["admin_login_csrf"], ENT_QUOTES, "UTF-8") ?>"
        >

        <label for="email">Email</label>
        <input
            id="email"
            type="email"
            name="email"
            autocomplete="username"
            required
        >

        <label for="password">Password</label>
        <input
            id="password"
            type="password"
            name="password"
            autocomplete="current-password"
            required
        >

        <button type="submit">Admin Login</button>
    </form>
</div>
</body>
</html>
