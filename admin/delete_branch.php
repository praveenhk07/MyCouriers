<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../config.php';

// Check if ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: branches.php?error=Invalid+branch+ID");
    exit;
}

$branch_id = intval($_GET['id']);

try {
    // Prepare SQL delete statement
    $stmt = $conn->prepare("DELETE FROM branch WHERE branch_id = ?");
    $stmt->bind_param("i", $branch_id);

    if ($stmt->execute()) {
        header("Location: branches.php?success=1");
        exit;
    } else {
        throw new Exception("Failed to delete branch. It may be linked to other records.");
    }

} catch (Exception $e) {
    header("Location: branches.php?error=" . urlencode($e->getMessage()));
    exit;
}
?>
