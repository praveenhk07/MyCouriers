<?php
require_once '../includes/auth_check.php';
requireRole('customer');
require_once '../config.php';

$tracking_info = null;
$status_updates = [];
$error = null;

if (isset($_GET['tracking_number'])) {
    $tracking_number = $_GET['tracking_number'];

    $sql = "SELECT p.*, 
                   CONCAT(p.recipient_first_name, ' ', p.recipient_last_name) AS recipient_name,
                   b1.branch_name AS from_branch_name, 
                   b2.branch_name AS to_branch_name
            FROM parcel p
            LEFT JOIN branch b1 ON p.from_branch = b1.branch_id
            LEFT JOIN branch b2 ON p.to_branch = b2.branch_id
            WHERE p.parcel_tracking_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $tracking_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $tracking_info = $result->fetch_assoc();

        $updates_sql = "SELECT su.*, CONCAT(s.staff_first_name, ' ', s.staff_last_name) AS updated_by_name
                        FROM status_update su
                        LEFT JOIN staff s ON su.updated_by = s.staff_id
                        WHERE su.parcel_id = ?
                        ORDER BY su.status_sequence ASC";
        $updates_stmt = $conn->prepare($updates_sql);
        $updates_stmt->bind_param("i", $tracking_info['parcel_id']);
        $updates_stmt->execute();
        $updates_result = $updates_stmt->get_result();

        while ($row = $updates_result->fetch_assoc()) {
            $status_updates[] = $row;
        }
        $updates_stmt->close();
    } else {
        $error = "No parcel found with tracking number: " . htmlspecialchars($tracking_number);
    }
    $stmt->close();
}

$latest_status = !empty($status_updates) ? end($status_updates)['parcel_status'] : 'Unknown';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track Parcel - MyCouriers</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.tracking-container { max-width: 800px; margin: 30px auto; padding: 20px; }
.status-timeline { position: relative; padding-left: 3rem; margin: 2rem 0; }
.status-timeline::before { content: ''; position: absolute; left: 1.5rem; top: 0; bottom: 0; width: 2px; background-color: #dee2e6; }
.status-item { position: relative; margin-bottom: 1.5rem; }
.status-item::before { content: ''; position: absolute; left: -2.1rem; top: 0.3rem; width: 1rem; height: 1rem; border-radius: 50%; background-color: #0d6efd; border: 3px solid #fff; box-shadow: 0 0 0 2px #0d6efd; }
.status-item.completed::before { background-color: #198754; box-shadow: 0 0 0 2px #198754; }
.status-item.cancelled::before { background-color: #dc3545; box-shadow: 0 0 0 2px #dc3545; }
@media print { body * { visibility: hidden; } .printable-area, .printable-area * { visibility: visible; } .printable-area { position: absolute; left: 0; top: 0; width: 100%; } }
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container tracking-container">
    <h2 class="mb-4"><i class="fas fa-location-dot"></i> Track Your Parcel</h2>

    <form method="GET" action="" class="mb-4">
        <div class="input-group">
            <input type="text" name="tracking_number" class="form-control" placeholder="Enter tracking number"
                   value="<?= isset($_GET['tracking_number']) ? htmlspecialchars($_GET['tracking_number']) : ''; ?>" required>
            <button class="btn btn-primary" type="submit">Track</button>
        </div>
    </form>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error; ?></div>
    <?php endif; ?>

    <?php if ($tracking_info): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Parcel Tracking Details</h4>
            <div class="d-flex gap-2">
                <a href="javascript:history.back()" class="btn btn-secondary" title="Go Back">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <button onclick="window.print()" class="btn btn-success" title="Print">
                    <i class="fas fa-print"></i>
                </button>
                <?php if ($latest_status === 'Pending'): ?>
                    <button onclick="confirmCancel(<?= $tracking_info['parcel_id']; ?>)" class="btn btn-danger" title="Cancel Parcel">
                        <i class="fas fa-times"></i>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="printable-area">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Parcel Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Tracking Number:</strong> <?= htmlspecialchars($tracking_info['parcel_tracking_number']); ?></p>
                    <p><strong>Recipient:</strong> <?= htmlspecialchars($tracking_info['recipient_name']); ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($tracking_info['recipient_street'] . ', ' . $tracking_info['recipient_city'] . ', ' . $tracking_info['recipient_state'] . ' ' . $tracking_info['recipient_zip'] . ', ' . $tracking_info['recipient_country']); ?></p>
                    <p><strong>From:</strong> <?= htmlspecialchars($tracking_info['from_branch_name']); ?></p>
                    <p><strong>To:</strong> <?= htmlspecialchars($tracking_info['to_branch_name']); ?></p>
                    <p><strong>Weight:</strong> <?= number_format($tracking_info['parcel_weight'], 2); ?> kg</p>
                    <p><strong>Price:</strong> ₹<?= number_format($tracking_info['parcel_price'], 2); ?></p>
                    <p><strong>Status:</strong>
                        <span class="badge
                            <?php switch($latest_status) {
                                case 'Booked': case 'Pending': echo 'bg-info'; break;
                                case 'In Transit': echo 'bg-warning'; break;
                                case 'Delivered': echo 'bg-success'; break;
                                case 'Cancelled': echo 'bg-danger'; break;
                                default: echo 'bg-secondary';
                            } ?>">
                            <?= htmlspecialchars($latest_status); ?>
                        </span>
                    </p>
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
                                <div class="status-item
                                    <?= $update['parcel_status'] === 'Delivered' ? 'completed' : '' ?>
                                    <?= $update['parcel_status'] === 'Cancelled' ? 'cancelled' : '' ?>">
                                    <h6><?= htmlspecialchars($update['parcel_status']); ?></h6>
                                    <p class="text-muted mb-1"><?= date('M j, Y g:i A', strtotime($update['status_update_time'])); ?></p>
                                    <?php if (!empty($update['status_update_notes'])): ?>
                                        <p><?= htmlspecialchars($update['status_update_notes']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($update['updated_by_name'])): ?>
                                        <p class="text-muted"><small>Updated by: <?= htmlspecialchars($update['updated_by_name']); ?></small></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">No status updates available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmCancel(parcelId) {
  if (confirm("Are you sure you want to cancel this parcel?")) {
    window.location.href = "cancel_parcel.php?id=" + parcelId;
  }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.