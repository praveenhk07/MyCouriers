<?php
require_once '../includes/auth_check.php';
requireRole('staff');
require_once '../config.php';

$staff_id = $_SESSION['user_id'];

// Get staff branch info
$branch_sql = "SELECT s.branch_id, b.branch_name 
               FROM staff s 
               JOIN branch b ON s.branch_id = b.branch_id 
               WHERE s.staff_id = ?";
$stmt = $conn->prepare($branch_sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$staff_branch = $stmt->get_result()->fetch_assoc();
$branch_id = $staff_branch['branch_id'] ?? 0;

// Get parcel_id from GET
$parcel_id = $_GET['parcel_id'] ?? 0;

// Fetch parcel details
$parcel_sql = "SELECT p.parcel_id, p.parcel_tracking_number, 
                      CONCAT(p.recipient_first_name,' ',COALESCE(p.recipient_last_name,'')) AS recipient_name, 
                      p.from_branch, p.to_branch,
                      su.parcel_status
               FROM parcel p
               LEFT JOIN (
                   SELECT su1.parcel_id, su1.parcel_status
                   FROM status_update su1
                   WHERE su1.status_sequence = (
                       SELECT MAX(su2.status_sequence) 
                       FROM status_update su2 
                       WHERE su2.parcel_id = su1.parcel_id
                   )
               ) su ON su.parcel_id = p.parcel_id
               WHERE p.parcel_id = ? 
                 AND (p.from_branch = ? OR p.to_branch = ?)";
$stmt = $conn->prepare($parcel_sql);
$stmt->bind_param("iii", $parcel_id, $branch_id, $branch_id);
$stmt->execute();
$parcel = $stmt->get_result()->fetch_assoc();

if (!$parcel) {
    die("Parcel not found or you do not have permission to update it.");
}

// Fetch all branches for forwarding
$branches_res = $conn->query("SELECT branch_id, branch_name FROM branch ORDER BY branch_name ASC");
$branches = $branches_res->fetch_all(MYSQLI_ASSOC);

// Process update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';
    $next_branch = $_POST['to_branch'] ?? null;

    if ($new_status === 'In Transit' && !empty($next_branch)) {
        $update_sql = "UPDATE parcel SET to_branch = ? WHERE parcel_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ii", $next_branch, $parcel_id);
        $stmt->execute();
    }

    $seq_sql = "SELECT MAX(status_sequence) as max_seq FROM status_update WHERE parcel_id = ?";
    $stmt_seq = $conn->prepare($seq_sql);
    $stmt_seq->bind_param("i", $parcel_id);
    $stmt_seq->execute();
    $res_seq = $stmt_seq->get_result()->fetch_assoc();
    $status_sequence = ($res_seq['max_seq'] ?? 0) + 1;

    $history_sql = "INSERT INTO status_update 
        (parcel_id, status_sequence, parcel_status, updated_by, status_update_location, status_update_notes) 
        VALUES (?, ?, ?, ?, ?, ?)";
    $stmt2 = $conn->prepare($history_sql);
    $location = $staff_branch['branch_name'] ?? 'Unknown Branch';
    $stmt2->bind_param("iisiss", $parcel_id, $status_sequence, $new_status, $staff_id, $location, $notes);

    if ($stmt2->execute()) {
        $success = "Status updated successfully!";
        $parcel['parcel_status'] = $new_status;
        if ($new_status === 'In Transit' && $next_branch) {
            $parcel['to_branch'] = $next_branch;
        }
    } else {
        $error = "Failed to update status! Error: " . $stmt2->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Parcel Status</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {
    background-color: #f4f6f9;
    font-family: 'Segoe UI', sans-serif;
}
.card {
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(0,0,0,0.05);
}
.card-header {
    background-color: #fff;
    font-weight: 500;
    font-size: 1.1rem;
    border-bottom: 1px solid #eee;
}
</style>
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-edit me-2 text-primary"></i>Update Parcel Status</h2>
        <a href="parcels.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Parcels</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success d-flex align-items-center"><i class="fas fa-check-circle me-2"></i><?= $success ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tracking Number</label>
                        <input type="text" class="form-control" value="<?= $parcel['parcel_tracking_number'] ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Recipient Name</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($parcel['recipient_name']) ?>" readonly>
                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Current Status</label>
                                        <input type="text" class="form-control" value="<?= $parcel['parcel_status'] ?? 'Pending' ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">New Status</label>
                                        <select name="status" class="form-select" id="statusSelect" required>
                                            <option value="Pending" <?= ($parcel['parcel_status'] ?? '')=='Pending'?'selected':'' ?>>Pending</option>
                                            <option value="Booked" <?= ($parcel['parcel_status'] ?? '')=='Booked'?'selected':'' ?>>Booked</option>
                                            <option value="In Transit" <?= ($parcel['parcel_status'] ?? '')=='In Transit'?'selected':'' ?>>In Transit</option>
                                            <option value="Delivered" <?= ($parcel['parcel_status'] ?? '')=='Delivered'?'selected':'' ?>>Delivered</option>
                                            <option value="Cancelled" <?= ($parcel['parcel_status'] ?? '')=='Cancelled'?'selected':'' ?>>Cancelled</option>
                                        </select>
                                    </div>
                </div>

                <div class="row g-3 mt-3" id="toBranchSelect" style="display: <?= ($parcel['parcel_status']=='In Transit')?'flex':'none' ?>;">
                    <div class="col-md-6">
                        <label class="form-label">Forward To Branch</label>
                        <select name="to_branch" class="form-select">
                            <option value="">-- Select Branch --</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['branch_id'] ?>" <?= ($parcel['to_branch']==$b['branch_id'])?'selected':'' ?>>
                                    <?= htmlspecialchars($b['branch_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Notes (optional)</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Add remarks..."></textarea>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" name="update_status" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('statusSelect').addEventListener('change', function() {
    const toBranch = document.getElementById('toBranchSelect');
    toBranch.style.display = this.value === 'In Transit' ? 'flex' : 'none';
});
</script>
</body>
</html>