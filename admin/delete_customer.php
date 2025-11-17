<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../config.php';

// Make sure ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: customers.php?error=No customer ID provided");
    exit;
}

$customer_id = intval($_GET['id']);

// Begin transaction (so we can delete parcels first, then customer)
$conn->begin_transaction();

try {
    // 1️⃣ Delete all parcels belonging to this customer
    $parcel_stmt = $conn->prepare("DELETE FROM parcel WHERE sender_id = ?");
    $parcel_stmt->bind_param("i", $customer_id);
    $parcel_stmt->execute();
    $parcel_stmt->close();

    // 2️⃣ Delete the customer record
    $customer_stmt = $conn->prepare("DELETE FROM customer WHERE customer_id = ?");
    $customer_stmt->bind_param("i", $customer_id);
    $customer_stmt->execute();
    $customer_stmt->close();

    // 3️⃣ Commit transaction
    $conn->commit();

    header("Location: customers.php?success=Customer deleted successfully");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    header("Location: customers.php?error=Failed to delete customer");
    exit;
}
?>