<?php
require_once '../includes/auth_check.php';
requireRole('customer');
require_once '../config.php';

$customer_id = $_SESSION['user_id'];
$parcel_id = $_GET['id'] ?? 0;

// Step 1: Verify parcel ownership and status
$sql = "SELECT p.parcel_id, 
               (SELECT su.parcel_status 
                FROM status_update su 
                WHERE su.parcel_id = p.parcel_id 
                ORDER BY su.status_sequence DESC LIMIT 1) AS latest_status
        FROM parcel p
        WHERE p.parcel_id = ? AND p.sender_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $parcel_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Parcel not found or access denied.");
}

$parcel = $result->fetch_assoc();
if ($parcel['latest_status'] !== 'Pending') {
    die("Parcel cannot be cancelled at this stage.");
}

// Step 2: Delete status updates
$delete_status = $conn->prepare("DELETE FROM status_update WHERE parcel_id = ?");
$delete_status->bind_param("i", $parcel_id);
$delete_status->execute();

// Step 3: Delete parcel record
$delete_parcel = $conn->prepare("DELETE FROM parcel WHERE parcel_id = ?");
$delete_parcel->bind_param("i", $parcel_id);
$delete_parcel->execute();

// Step 4: Redirect back to parcel list
header("Location: my_parcels.php?cancelled=1");
exit;
?>