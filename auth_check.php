<?php
// auth_check.php
require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function requireRole($role) {
    if (!isset($_SESSION['user_type'])) {
        // Not logged in
        header("Location: ../login.php");
        exit();
    }

    // Make role check case-insensitive
    if (strtolower($_SESSION['user_type']) !== strtolower($role)) {
        header("Location: ../unauthorized.php");
        exit();
    }
}
?>
