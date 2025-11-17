<?php
require_once 'config.php';

$tracking_info = null;
$status_updates = [];

if (isset($_GET['tracking_number'])) {
    $tracking_number = $_GET['tracking_number'];

    // Get parcel info with sender/recipient and branch names
    $sql = "SELECT p.*,
                   CONCAT(c.customer_first_name, ' ', c.customer_last_name) AS sender_name,
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
        $tracking_info = $result->fetch_assoc();

        // Get status updates with staff names
        $updates_sql = "SELECT su.*, 
                               CONCAT(s.staff_first_name, ' ', s.staff_last_name) AS updated_by_name
                        FROM status_update su
                        LEFT JOIN staff s ON su.updated_by = s.staff_id
                        WHERE su.parcel_id = ?
                        ORDER BY su.status_sequence DESC";

        $updates_stmt = $conn->prepare($updates_sql);
        $updates_stmt->bind_param("i", $tracking_info['parcel_id']);
        $updates_stmt->execute();
        $updates_result = $updates_stmt->get_result();

        while ($row = $updates_result->fetch_assoc()) {
            $status_updates[] = $row;
        }

        // Get latest status for the badge
        if (!empty($status_updates)) {
            $tracking_info['latest_status'] = $status_updates[0]['parcel_status'];
        } else {
            $tracking_info['latest_status'] = 'Unknown';
        }
    } else {
        $error = "No parcel found with tracking number: $tracking_number";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track Parcel - Courier Management System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.tracking-container { max-width: 900px; margin: 50px auto; padding: 20px; }
.status-timeline { position: relative; padding-left: 3rem; margin: 2rem 0; }
.status-timeline::before { content: ''; position: absolute; left: 1.5rem; top: 0; bottom: 0; width: 2px; background-color: #dee2e6; }
.status-item { position: relative; margin-bottom: 1.5rem; }
.status-item::before { content: ''; position: absolute; left: -2.1rem; top: 0.3rem; width: 1rem; height: 1rem; border-radius: 50%; background-color: #0d6efd; border: 3px solid #fff; box-shadow: 0 0 0 2px #0d6efd; }
.status-item.completed::before { background-color: #198754; box-shadow: 0 0 0 2px #198754; }
.status-item.cancelled::before { background-color: #dc3545; box-shadow: 0 0 0 2px #dc3545; }
@media print { .no-print { display: none; } }
</style>
</head>
<body>
<div class="container tracking-container">
    <div class="text-center mb-4">
        <h2><i class="fas fa-location-dot"></i> Track Your Parcel</h2>
        <p class="text-muted">Enter your tracking number to check the status of your shipment</p>
    </div>

    <div class="card mb-4 no-print">
        <div class="card-body">
            <form method="GET" action="">
                <div class="input-group">
                    <input type="text" name="tracking_number" class="form-control form-control-lg"
                        placeholder="Enter tracking number"
                        value="<?php echo isset($_GET['tracking_number']) ? htmlspecialchars($_GET['tracking_number']) : ''; ?>"
                        required>
                    <button class="btn btn-primary" type="submit">Track</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($tracking_info): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Parcel Details</h4>
            <div class="mb-3 no-print">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
            <button class="btn btn-outline-primary no-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Tracking Number:</strong> <?php echo htmlspecialchars($tracking_info['parcel_tracking_number']); ?></p>
                        <p><strong>Sender:</strong> <?php echo htmlspecialchars($tracking_info['sender_name']); ?></p>
                        <p><strong>Recipient:</strong> <?php echo htmlspecialchars($tracking_info['recipient_first_name'] . ' ' . ($tracking_info['recipient_last_name'] ?? '')); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong>
                            <span class="badge 
                                <?php
                                    switch($tracking_info['latest_status']) {
                                        case 'Booked': echo 'bg-info'; break;
                                        case 'In Transit': echo 'bg-warning'; break;
                                        case 'Delivered': echo 'bg-success'; break;
                                        case 'Cancelled': echo 'bg-danger'; break;
                                        default: echo 'bg-secondary';
                                    }
                                ?>">
                                <?php echo htmlspecialchars($tracking_info['latest_status']); ?>
                            </span>
                        </p>
                        <p><strong>From Branch:</strong> <?php echo htmlspecialchars($tracking_info['from_branch_name']); ?></p>
                        <p><strong>To Branch:</strong> <?php echo htmlspecialchars($tracking_info['to_branch_name']); ?></p>
                        <p><strong>Booking Date:</strong> <?php echo date('M j, Y g:i A', strtotime($tracking_info['booking_date'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Shipment History</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($status_updates)): ?>
                    <div class="status-timeline">
                        <?php foreach ($status_updates as $update): ?>
                            <div class="status-item <?php
                                if ($update['parcel_status'] == 'Delivered') echo 'completed';
                                if ($update['parcel_status'] == 'Cancelled') echo 'cancelled';
                            ?>">
                                <h6><?php echo htmlspecialchars($update['parcel_status']); ?></h6>
                                <p class="text-muted mb-1"><?php echo date('M j, Y g:i A', strtotime($update['status_update_time'])); ?></p>
                                <?php if (!empty($update['status_update_location'])): ?>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($update['status_update_location']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($update['status_update_notes'])): ?>
                                    <p><?php echo htmlspecialchars($update['status_update_notes']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($update['updated_by_name'])): ?>
                                    <p class="text-muted"><small>Updated by: <?php echo htmlspecialchars($update['updated_by_name']); ?></small></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No status updates available.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
