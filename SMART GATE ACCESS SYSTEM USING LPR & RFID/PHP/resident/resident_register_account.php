
<?php
session_start();
require_once __DIR__ . "/../db.php";

date_default_timezone_set("Asia/Kuala_Lumpur");

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $house_no = trim($_POST["house_no"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        $full_name === "" ||
        $house_no === "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 8
    ) {
        $message = "Please enter all information correctly. Passwords must be at least 8 characters long.";

    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";

    } else {

        $check = $conn->prepare(
            "SELECT id FROM residents WHERE email = ?"
        );
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "This email address is already registered.";

        } else {

            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO residents
                (full_name, house_no, email, password_hash, status)
                VALUES (?, ?, ?, ?, 'pending')"
            );

            $stmt->bind_param(
                "ssss",
                $full_name,
                $house_no,
                $email,
                $password_hash
            );

            if ($stmt->execute()) {
                $message = "Registration successful! Your account is awaiting Admin approval.";
                $success = true;
            } else {
                $message = "Registration failed. Please try again.";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resident Registration | Smart Gate</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    color: #1e293b;
}

.container {
    width: 90%;
    max-width: 480px;
    margin: 50px auto;
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

h1 {
    margin-top: 0;
    font-size: 25px;
}

.subtitle {
    color: #64748b;
    font-size: 14px;
    line-height: 1.5;
}

label {
    display: block;
    margin: 17px 0 7px;
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
    padding: 12px;
    margin: 15px 0;
    border-radius: 8px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 14px;
    line-height: 1.5;
}

a {
    color: #2563eb;
    text-decoration: none;
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

    <h1>🏠 Resident Registration</h1>

    <p class="subtitle">
        Create an account to use the Smart Gate Visitor Management System.
    </p>

    <?php if ($message !== ""): ?>
        <div class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="">

        <label for="full_name">Full Name</label>
        <input
            type="text"
            id="full_name"
            name="full_name"
            maxlength="100"
            autocomplete="name"
            required
        >

        <label for="house_no">House Number</label>
        <input
            type="text"
            id="house_no"
            name="house_no"
            maxlength="30"
            placeholder="e.g. A-12"
            required
        >

        <label for="email">Email Address</label>
        <input
            type="email"
            id="email"
            name="email"
            maxlength="150"
            autocomplete="email"
            required
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            minlength="8"
            autocomplete="new-password"
            required
        >

        <label for="confirm_password">Confirm Password</label>
        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            minlength="8"
            autocomplete="new-password"
            required
        >

        <button type="submit">
            Create Account
        </button>

    </form>
    <?php endif; ?>

    <div class="footer">
        Smart Gate Access System
    </div>

</div>
</body>
</html>
