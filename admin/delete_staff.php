<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../config.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: staff.php?error=No staff ID provided");
    exit;
}

$staff_id = intval($_GET['id']);

// Optional: Prevent deleting yourself (if admin)
if (isset($_SESSION['staff_id']) && $_SESSION['staff_id'] == $staff_id) {
    header("Location: staff.php?error=You cannot delete your own account");
    exit;
}

// Prepare and execute delete query safely
$stmt = $conn->prepare("DELETE FROM staff WHERE staff_id = ?");
$stmt->bind_param("i", $staff_id);

if ($stmt->execute()) {
    header("Location: staff.php?success=Staff member deleted successfully");
} else {
    header("Location: staff.php?error=Failed to delete staff member");
}

$stmt->close();
$conn->close();
exit;
?>
