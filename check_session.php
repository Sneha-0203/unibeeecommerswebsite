<?php
// Start the session if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_name']) || !isset($_SESSION['admin_username'])) {
    // Not logged in, redirect to login page
    header("Location: admin_login.php");
    exit;
}

// Optional: Check for session timeout
if (isset($_SESSION['last_activity'])) {
    // Set timeout period in seconds (e.g., 30 minutes)
    $timeout_duration = 1800;
    
    // Calculate time difference
    if (time() - $_SESSION['last_activity'] > $timeout_duration) {
        // Session timed out
        session_unset();
        session_destroy();
        header("Location: admin_login.php?timeout=1");
        exit;
    }
}

// Update last activity time
$_SESSION['last_activity'] = time();