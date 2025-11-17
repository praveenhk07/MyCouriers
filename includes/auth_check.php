<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not authenticated
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

// Check if user has specific role
function requireRole($role) {
    requireAuth();
    if ($_SESSION['user_type'] != $role) {
        header("Location: ../unauthorized.php");
        exit();
    }
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin';
}

// Check if user is staff
function isStaff() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'staff';
}

// Check if user is customer
function isCustomer() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'customer';
}
?>