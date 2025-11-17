<?php
require_once '../includes/auth_check.php';
requireRole(['admin', 'staff']);

$parcel_id = $_GET['parcel_id'] ?? null;
$parcel = null;
$status_updates = [];

if ($parcel_id) {
    require_once '../config.php';
    // Get parcel information
    $sql = "SELECT p.*, c.full_name as sender_name, b1.branch_name as from_branch_name, b2.branch_name as to_branch_name 
            FROM parcel p 
            JOIN customer c ON p.sender_id = c.customer_id 
            JOIN branch b1 ON p.from_branch = b1.branch_id 
            JOIN branch b2 ON p.to_branch = b2.branch_id 
            WHERE p.parcel_id = :parcel_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':parcel_id', $parcel_id);
    $stmt->execute();
    $parcel = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($parcel) {
        // Get status updates history
        $updates_sql = "SELECT su.*, s.full_name as updated_by_name 
                        FROM status_update su 
                        LEFT JOIN staff s ON su.updated_by = s.staff_id 
                        WHERE su.parcel_id = :parcel_id 
                        ORDER BY su.update_time DESC";
        $updates_stmt = $conn->prepare($updates_sql);
        $updates_stmt->bindParam(':parcel_id', $parcel_id);
        $updates_stmt->execute();
        $status_updates = $updates_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Process status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $notes = trim($_POST['notes']);
    $location = trim($_POST['location']);
    $updated_by = $_SESSION['user_id'];
    
    require_once '../config.php';
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Update parcel status
        $update_sql = "UPDATE parcel SET status = :status";
        if ($new_status == 'Delivered') {
            $update_sql .= ", delivered_date = NOW()";
        }
        $update_sql .= " WHERE parcel_id = :parcel_id";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':parcel_id', $parcel_id);
        $stmt->execute();
        
        // Add status update record
        $status_sql = "INSERT INTO status_update (parcel_id, status, updated_by, location, notes) 
                       VALUES (:parcel_id, :status, :updated_by, :location, :notes)";
        $status_stmt = $conn->prepare($status_sql);
        $status_stmt->bindParam(':parcel_id', $parcel_id);
        $status_stmt->bindParam(':status', $new_status);
        $status_stmt->bindParam(':updated_by', $updated_by);
        $status_stmt->bindParam(':location', $location);
        $status_stmt->bindParam(':notes', $notes);
        $status_stmt->execute();
        
        $conn->commit();
        
        $success = "Status updated successfully to: $new_status";
        
        // Refresh parcel data
        $stmt->execute();
        $parcel = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Refresh status updates
        $updates_stmt->execute();
        $status_updates = $updates_stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Parcel Status - Courier Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <?php include 'navigation.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Update Parcel Status</h2>
                    <a href="parcels.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Parcels
                    </a>
                </div>

                <?php if (!$parcel): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i> Parcel not found.
                    </div>
                <?php else: ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <!-- Parcel Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Parcel Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Tracking Number:</strong> <?php echo $parcel['tracking_number']; ?></p>
                                    <p><strong>Sender:</strong> <?php echo htmlspecialchars($parcel['sender_name']); ?></p>
                                    <p><strong>Recipient:</strong> <?php echo htmlspecialchars($parcel['recipient_name']); ?></p>
                                    <p><strong>Recipient Phone:</strong> <?php echo htmlspecialchars($parcel['recipient_phone']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>From Branch:</strong> <?php echo htmlspecialchars($parcel['from_branch_name']); ?></p>
                                    <p><strong>To Branch:</strong> <?php echo htmlspecialchars($parcel['to_branch_name']); ?></p>
                                    <p><strong>Current Status:</strong> 
                                        <?php
                                        $status_class = '';
                                        switch($parcel['status']) {
                                            case 'Booked': $status_class = 'bg-info'; break;
                                            case 'In Transit': $status_class = 'bg-warning'; break;
                                            case 'Delivered': $status_class = 'bg-success'; break;
                                            case 'Cancelled': $status_class = 'bg-danger'; break;
                                            default: $status_class = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo $parcel['status']; ?>
                                        </span>
                                    </p>
                                    <p><strong>Booking Date:</strong> <?php echo date('M j, Y g:i A', strtotime($parcel['booking_date'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Update Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Update Status</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="status" class="form-label">New Status *</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="Booked" <?php echo $parcel['status'] == 'Booked' ? 'selected' : ''; ?>>Booked</option>
                                            <option value="In Transit" <?php echo $parcel['status'] == 'In Transit' ? 'selected' : ''; ?>>In Transit</option>
                                            <option value="Delivered" <?php echo $parcel['status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="Cancelled" <?php echo $parcel['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="location" class="form-label">Current Location</label>
                                        <input type="text" class="form-control" id="location" name="location" 
                                               placeholder="Enter current location">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Add any additional notes about this status update"></textarea>
                                </div>

                                <button type="submit" name="update_status" class="btn btn-primary">
                                    <i class="fas fa-sync-alt me-1"></i> Update Status
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Status History -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Status History</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($status_updates)): ?>
                                <div class="status-timeline">
                                    <?php foreach ($status_updates as $update): ?>
                                        <div class="status-item <?php 
                                            if ($update['status'] == 'Delivered') echo 'completed';
                                            if ($update['status'] == 'Cancelled') echo 'cancelled';
                                        ?>">
                                            <h6><?php echo $update['status']; ?></h6>
                                            <p class="text-muted mb-1"><?php echo date('M j, Y g:i A', strtotime($update['update_time'])); ?></p>
                                            <?php if (!empty($update['location'])): ?>
                                                <p><strong>Location:</strong> <?php echo htmlspecialchars($update['location']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($update['notes'])): ?>
                                                <p><?php echo htmlspecialchars($update['notes']); ?></p>
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
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>