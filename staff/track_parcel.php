<?php
require_once '../includes/auth_check.php';
requireRole('staff');
require_once '../config.php';

$parcel_info = null;
$status_updates = [];

if (isset($_GET['tracking_number'])) {
    $tracking_number = $_GET['tracking_number'];

    $sql = "SELECT p.*, 
                   CONCAT(c.customer_first_name,' ',COALESCE(c.customer_last_name,'')) AS sender_name, 
                   c.customer_phone AS sender_phone,
                   b1.branch_name AS from_branch_name, 
                   b2.branch_name AS to_branch_name
            FROM parcel p
            JOIN customer c ON p.sender_id = c.customer_id
            JOIN branch b1 ON p.from_branch = b1.branch_id
            JOIN branch b2 ON p.to_branch = b2.branch_id
            WHERE p.parcel_tracking_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $tracking_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $parcel_info = $result->fetch_assoc();

        $updates_sql = "SELECT su.*, 
                               CONCAT(s.staff_first_name,' ',COALESCE(s.staff_last_name,'')) AS updated_by_name
                        FROM status_update su
                        LEFT JOIN staff s ON su.updated_by = s.staff_id
                        WHERE su.parcel_id = ?
                        ORDER BY su.status_sequence DESC";
        $updates_stmt = $conn->prepare($updates_sql);
        $updates_stmt->bind_param("i", $parcel_info['parcel_id']);
        $updates_stmt->execute();
        $updates_result = $updates_stmt->get_result();
        $status_updates = $updates_result->fetch_all(MYSQLI_ASSOC);

        $current_status = !empty($status_updates) ? $status_updates[0]['parcel_status'] : 'Booked';
    } else {
        $error = "Parcel not found with tracking number: $tracking_number";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Track Parcel - Staff | Courier Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.status-timeline {
    position: relative;
    padding-left: 3rem;
    margin: 2rem 0;
}
.status-timeline::before {
    content: '';
    position: absolute;
    left: 1.5rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #dee2e6;
}
.status-item {
    position: relative;
    margin-bottom: 1.5rem;
}
.status-item::before {
    content: '';
    position: absolute;
    left: -2.1rem;
    top: 0.3rem;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background-color: #0d6efd;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #0d6efd;
}
.status-item.completed::before {
    background-color: #198754;
    box-shadow: 0 0 0 2px #198754;
}
.status-item.cancelled::before {
    background-color: #dc3545;
    box-shadow: 0 0 0 2px #dc3545;
}
.badge-pill {
    padding: 6px 12px;
    font-size: 0.85rem;
    border-radius: 50px;
    font-weight: 500;
}
@media print {
    .no-print { display: none; }
}
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-search me-2 text-primary"></i>Track Parcel</h2>
                <a href="dashboard.php" class="btn btn-secondary no-print"><i class="fas fa-arrow-left"></i> Back</a>
            </div>

            <!-- Tracking Form -->
            <div class="card mb-4 no-print">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="input-group">
                            <input type="text" name="tracking_number" class="form-control form-control-lg" placeholder="Enter tracking number" value="<?= isset($_GET['tracking_number']) ? htmlspecialchars($_GET['tracking_number']) : ''; ?>" required>
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Track</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($parcel_info): ?>
                <!-- Parcel Details -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Parcel Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Tracking Number:</strong> <?= $parcel_info['parcel_tracking_number'] ?></p>
                                <p><strong>Sender:</strong> <?= htmlspecialchars($parcel_info['sender_name']) ?></p>
                                <p><strong>Sender Phone:</strong> <?= htmlspecialchars($parcel_info['sender_phone']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Recipient:</strong> <?= htmlspecialchars($parcel_info['recipient_first_name'].' '.$parcel_info['recipient_last_name']) ?></p>
                                <p><strong>Recipient Phone:</strong> <?= htmlspecialchars($parcel_info['recipient_phone']) ?></p>
                                <p><strong>Recipient Address:</strong> <?= htmlspecialchars(trim($parcel_info['recipient_street'].', '.$parcel_info['recipient_city'].', '.$parcel_info['recipient_state'].' - '.$parcel_info['recipient_zip'].', '.$parcel_info['recipient_country'])) ?></p>
                                <?php
                                $status_class = match($current_status) {
                                    'Booked' => 'bg-info',
                                    'In Transit' => 'bg-warning text-dark',
                                    'Delivered' => 'bg-success',
                                    'Cancelled' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                                ?>
                                <p><strong>Status:</strong> <span class="badge <?= $status_class ?> badge-pill"><?= htmlspecialchars($current_status) ?></span></p>
                                <p><strong>From Branch:</strong> <?= htmlspecialchars($parcel_info['from_branch_name']) ?></p>
                                <p><strong>To Branch:</strong> <?= htmlspecialchars($parcel_info['to_branch_name']) ?></p>
                                <p><strong>Booking Date:</strong> <?= date('M j, Y g:i A', strtotime($parcel_info['booking_date'])) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipment History -->
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Shipment History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($status_updates)): ?>
                            <div class="status-timeline">
                                <?php foreach ($status_updates as $update): ?>
                                    <div class="status-item 
                                        <?= $update['parcel_status'] == 'Delivered' ? 'completed' : '' ?>
                                        <?= $update['parcel_status'] == 'Cancelled' ? 'cancelled' : '' ?>">
                                        <h6><?= htmlspecialchars($update['parcel_status']) ?></h6>
                                        <p class="text-muted mb-1"><?= date('M j, Y g:i A', strtotime($update['status_update_time'])) ?></p>
                                        <?php if (!empty($update['status_update_location'])): ?>
                                            <p><strong>Location:</strong> <?= htmlspecialchars($update['status_update_location']) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($update['status_update_notes'])): ?>
                                            <p><?= htmlspecialchars($update['status_update_notes']) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($update['updated_by_name'])): ?>
                                            <p class="text-muted"><small>Updated by: <?= htmlspecialchars($update['updated_by_name']) ?></small></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">No shipment history available.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <button class="btn btn-outline-primary no-print mb-4" onclick="window.print()"><i class="fas fa-print>
                                <button class="btn btn-outline-primary no-print mb-4" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>