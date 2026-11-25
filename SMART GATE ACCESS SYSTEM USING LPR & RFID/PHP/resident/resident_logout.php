
<?php
session_start();

// Clear all session data
$_SESSION = [];

// Delete the session cookie if cookies are enabled
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to the resident login page
header("Location: resident_login.php");
exit;
?>
