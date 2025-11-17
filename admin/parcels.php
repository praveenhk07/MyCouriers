<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../config.php';

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$branch_filter = $_GET['branch'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Base query
$sql = "SELECT p.*, 
               b1.branch_name AS from_branch_name, 
               b2.branch_name AS to_branch_name,
               (SELECT su.parcel_status 
                FROM status_update su 
                WHERE su.parcel_id = p.parcel_id 
                ORDER BY su.status_update_time DESC LIMIT 1) AS latest_status
        FROM parcel p
        JOIN branch b1 ON p.from_branch = b1.branch_id
        JOIN branch b2 ON p.to_branch = b2.branch_id
        WHERE 1=1";

$params = [];
$types = "";

// Apply filters
if (!empty($status_filter)) {
    $sql .= " AND (SELECT su.parcel_status 
                   FROM status_update su 
                   WHERE su.parcel_id = p.parcel_id 
                   ORDER BY su.status_update_time DESC LIMIT 1) = ?";
    $types .= "s";
    $params[] = $status_filter;
}

if (!empty($branch_filter)) {
    $sql .= " AND (p.from_branch = ? OR p.to_branch = ?)";
    $types .= "ii";
    $params[] = (int)$branch_filter;
    $params[] = (int)$branch_filter;
}

if (!empty($date_from)) {
    $sql .= " AND p.booking_date >= ?";
    $types .= "s";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND p.booking_date <= ?";
    $types .= "s";
    $params[] = $date_to . ' 23:59:59';
}

$sql .= " ORDER BY p.booking_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$parcels = [];
while ($row = $result->fetch_assoc()) {
    $parcels[] = $row;
}

// Get branches
$branches_result = $conn->query("SELECT * FROM branch ORDER BY branch_name");
$branches = [];
while ($row = $branches_result->fetch_assoc()) {
    $branches[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Parcels - Courier Management System</title>
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
        border: none;
    }
    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #eee;
        font-weight: 500;
        font-size: 1.1rem;
    }
    .status-pill {
        display: inline-block;
        padding: 6px 12px;
        font-size: 0.85rem;
        border-radius: 50px;
        font-weight: 500;
        color: #fff;
        text-align: center;
        white-space: nowrap;
    }
    .status-Pending { background-color: #6c757d; }
    .status-Booked { background-color: #0d6efd; }
    .status-In-Transit { background-color: #ffc107; color: #212529; }
    .status-Delivered { background-color: #28a745; }
    .action-group {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
    }
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-box me-2 text-primary"></i>Manage Parcels</h2>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All</option>
                        <option value="Pending" <?= $status_filter=="Pending" ? "selected" : ""; ?>>Pending</option>
                        <option value="Booked" <?= $status_filter=="Booked" ? "selected" : ""; ?>>Booked</option>
                        <option value="In Transit" <?= $status_filter=="In Transit" ? "selected" : ""; ?>>In Transit</option>
                        <option value="Delivered" <?= $status_filter=="Delivered" ? "selected" : ""; ?>>Delivered</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="branch" class="form-label">Branch</label>
                    <select name="branch" id="branch" class="form-select">
                        <option value="">All</option>
                        <?php foreach($branches as $branch): ?>
                            <option value="<?= $branch['branch_id']; ?>" <?= $branch_filter==$branch['branch_id'] ? "selected" : ""; ?>>
                                <?= htmlspecialchars($branch['branch_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From</label>
                    <input type="date" name="date_from" id="date_from" value="<?= $date_from; ?>" class="form-control">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To</label>
                    <input type="date" name="date_to" id="date_to" value="<?= $date_to; ?>" class="form-control">
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Parcel List</h5></div>
        <div class="card-body table-responsive">
            <table class="table table-hover table-bordered align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Recipient</th>
                        <th>From Branch</th>
                        <th>To Branch</th>
                        <th>Status</th>
                        <th>Booking Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($parcels)): ?>
                        <tr><td colspan="7" class="text-center">No parcels found.</td></tr>
                    <?php else: ?>
                        <?php foreach($parcels as $parcel): ?>
                            <tr>
                                <td><?= $parcel['parcel_id']; ?></td>
                                <td><?= htmlspecialchars($parcel['recipient_first_name'] . ' ' . $parcel['recipient_last_name']); ?></td>
                                <td><?= htmlspecialchars($parcel['from_branch_name']); ?></td>
                                <td><?= htmlspecialchars($parcel['to_branch_name']); ?></td>
                                <td class="text-center">
                                    <?php 
                                        $status = $parcel['latest_status'] ?? 'Pending';
                                        $status_class = 'status-' . str_replace(' ', '-', $status);
                                    ?>
                                    <span class="status-pill <?= $status_class; ?>"><?= htmlspecialchars($status); ?></span>
                                </td>
                                <td><?= date('M j, Y H:i', strtotime($parcel['booking_date'])); ?></td>
                                <td>
                                    <div class="action-group">
                                        <a href="parcel_details.php?id=<?= $parcel['parcel_id']; ?>" class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div