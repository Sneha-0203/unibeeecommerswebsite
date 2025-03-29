<?php
// admin_logout.php - Logout functionality for administrators

// Start session
session_start();

// Clear all session variables
$_SESSION = array();

// If session cookie is used, delete it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to login page
header("Location: admin_login.php");
exit;
?>