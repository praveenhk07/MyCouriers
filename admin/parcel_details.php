<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../config.php';

if (!isset($_GET['id'])) {
    header("Location: parcels.php?error=Parcel ID missing");
    exit();
}

$parcel_id = (int)$_GET['id'];

// Fetch parcel details with branch info
$sql = "SELECT p.*, 
               b1.branch_name AS from_branch_name, 
               b2.branch_name AS to_branch_name
        FROM parcel p
        JOIN branch b1 ON p.from_branch = b1.branch_id
        LEFT JOIN branch b2 ON p.to_branch = b2.branch_id
        WHERE p.parcel_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $parcel_id);
$stmt->execute();
$result = $stmt->get_result();
$parcel = $result->fetch_assoc();

if (!$parcel) {
    header("Location: parcels.php?error=Parcel not found");
    exit();
}

// Fetch status updates with staff info
$status_sql = "SELECT su.*, CONCAT(s.staff_first_name, ' ', s.staff_last_name) AS updated_by_name
               FROM status_update su
               JOIN staff s ON su.updated_by = s.staff_id
               WHERE su.parcel_id = ?
               ORDER BY su.status_update_time ASC";
$status_stmt = $conn->prepare($status_sql);
$status_stmt->bind_param("i", $parcel_id);
$status_stmt->execute();
$status_result = $status_stmt->get_result();
$status_updates = [];
while ($row = $status_result->fetch_assoc()) {
    $status_updates[] = $row;
}

// Determine delivered date
$delivered_date = '-';
foreach ($status_updates as $update) {
    if ($update['parcel_status'] === 'Delivered') {
        $delivered_date = date('M j, Y H:i', strtotime($update['status_update_time']));
        break;
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 py-4">
            <h2>Parcel Details</h2>
            <a href="parcels.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left me-1"></i> Back to Parcels</a>

            <!-- Parcel Info -->
            <div class="card mb-4">
                <div class="card-header"><h5>Parcel Information</h5></div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th>Parcel ID</th><td><?= $parcel['parcel_id']; ?></td></tr>
                        <tr><th>Tracking Number</th><td><?= htmlspecialchars($parcel['parcel_tracking_number']); ?></td></tr>
                        <tr><th>From Branch</th><td><?= htmlspecialchars($parcel['from_branch_name']); ?></td></tr>
                        <tr><th>To Branch</th><td><?= htmlspecialchars($parcel['to_branch_name'] ?: '-'); ?></td></tr>
                        <tr><th>Booking Date</th><td><?= date('M j, Y H:i', strtotime($parcel['booking_date'])); ?></td></tr>
                        <tr><th>Delivered Date</th><td><?= $delivered_date; ?></td></tr>
                        <tr><th>Recipient Name</th><td><?= htmlspecialchars($parcel['recipient_first_name'] . ' ' . $parcel['recipient_last_name']); ?></td></tr>
                        <tr><th>Recipient Phone</th><td><?= htmlspecialchars($parcel['recipient_phone']); ?></td></tr>
                        <tr><th>Recipient Address</th><td>
                            <?= htmlspecialchars($parcel['recipient_street'] . ', ' . $parcel['recipient_city'] . ', ' . $parcel['recipient_state'] . ' - ' . $parcel['recipient_zip'] . ', ' . $parcel['recipient_country']); ?>
                        </td></tr>
                        <tr><th>Weight</th><td><?= $parcel['parcel_weight']; ?> kg</td></tr>
                        <tr><th>Price</th><td><?= $parcel['parcel_price']; ?></td></tr>
                        <tr><th>Details</th><td><?= htmlspecialchars($parcel['parcel_details'] ?? '-'); ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- Status Updates -->
            <div class="card">
                <div class="card-header"><h5>Status Updates</h5></div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Location</th>
                                <th>Updated By</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($status_updates)): ?>
                                <tr><td colspan="5" class="text-center">No status updates available.</td></tr>
                            <?php else: ?>
                                <?php foreach ($status_updates as $update): ?>
                                    <tr>
                                        <td><?= date('M j, Y H:i', strtotime($update['status_update_time'])); ?></td>
                                        <td><?= htmlspecialchars($update['parcel_status']); ?></td>
                                        <td><?= htmlspecialchars($update['status_update_location'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($update['updated_by_name']); ?></td>
                                        <td><?= htmlspecialchars($update['status_update_notes'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
