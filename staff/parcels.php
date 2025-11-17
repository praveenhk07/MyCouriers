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

// Filter
$status_filter = $_GET['status'] ?? '';

// Fetch parcels with latest status
$sql = "SELECT p.parcel_id, p.parcel_tracking_number,
               CONCAT(c.customer_first_name,' ', COALESCE(c.customer_last_name,'')) AS sender_name,
               CONCAT(p.recipient_first_name,' ', COALESCE(p.recipient_last_name,'')) AS recipient_name,
               p.recipient_phone, p.recipient_street, p.recipient_city, p.recipient_state, p.recipient_zip, p.recipient_country,
               p.from_branch, p.to_branch,
               COALESCE(su.parcel_status,'Booked') AS parcel_status,
               p.booking_date
        FROM parcel p
        JOIN customer c ON p.sender_id = c.customer_id
        LEFT JOIN (
            SELECT su1.parcel_id, su1.parcel_status
            FROM status_update su1
            WHERE su1.status_sequence = (
                SELECT MAX(su2.status_sequence) FROM status_update su2 WHERE su2.parcel_id = su1.parcel_id
            )
        ) su ON su.parcel_id = p.parcel_id
        WHERE (p.from_branch=? OR p.to_branch=?)";

$params = [$branch_id, $branch_id];
$types = "ii";

if (!empty($status_filter)) {
    $sql .= " AND COALESCE(su.parcel_status,'Booked') = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY p.booking_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$parcels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Parcels - Courier Management</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body {
    margin: 0;
    padding: 0;
    background-color: #f4f6f9;
    font-family: 'Segoe UI', sans-serif;
}

.layout {
    display: flex;
    min-height: 100vh;
}

/* Sidebar */
.sidebar {
    width: 250px;
    background-color: #0d6efd;
    color: white;
    flex-shrink: 0;
    padding-top: 20px;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
}

.sidebar a {
    color: white;
    display: block;
    padding: 12px 20px;
    text-decoration: none;
    transition: all 0.3s;
    border-radius: 8px;
    margin: 4px 10px;
}

.sidebar a:hover {
    background-color: rgba(255,255,255,0.15);
}

.main-content {
    margin-left: 250px; /* space for sidebar */
    padding: 30px;
    flex: 1;
}

/* Status badges */
.badge-pill {
    padding: 6px 12px;
    font-size: 0.85rem;
    border-radius: 50px;
    font-weight: 500;
}
.status-Booked { background-color: #007bff; color: #fff; box-shadow: 0 0 6px rgba(0,123,255,0.4); }
.status-In-Transit { background-color: #ffc107; color: #212529; box-shadow: 0 0 6px rgba(255,193,7,0.4); }
.status-Delivered { background-color: #28a745; color: #fff; box-shadow: 0 0 6px rgba(40,167,69,0.4); }
.status-Cancelled { background-color: #dc3545; color: #fff; box-shadow: 0 0 6px rgba(220,53,69,0.4); }

.type-Incoming { color: #198754; font-weight: 600; }
.type-Outgoing { color: #0d6efd; font-weight: 600; }

.action-group {
    display: flex;
    gap: 8px;
    justify-content: center;
    flex-wrap: wrap;
}
</style>
</head>
<body>

<div class="layout">
    <!-- Sidebar -->
    <div class="sidebar">
        <?php include 'navigation.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><i class="fas fa-box me-2 text-primary"></i>Parcels at <?= htmlspecialchars($staff_branch['branch_name'] ?? 'Your Branch'); ?></h3>
            <a href="book_parcel.php" class="btn btn-success shadow-sm"><i class="fas fa-plus-circle me-1"></i> Book Parcel</a>
        </div>

        <!-- Filters -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-6">
                <select name="status" class="form-select shadow-sm">
                    <option value="">All Statuses</option>
                    <?php
                    $statuses = ['Booked','In Transit','Delivered','Cancelled'];
                    foreach($statuses as $s){
                        $selected = ($status_filter==$s)?'selected':'';
                        echo "<option value='$s' $selected>$s</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary shadow-sm"><i class="fas fa-filter"></i> Apply</button>
                <a href="parcels.php" class="btn btn-secondary shadow-sm"><i class="fas fa-times"></i> Clear</a>
            </div>
        </form>

        <!-- Parcel Table -->
        <div class="card shadow-sm border-0">
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tracking #</th>
                            <th>Sender</th>
                            <th>Recipient</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Booking Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(!empty($parcels)):
                        foreach($parcels as $p):
                            $status_class = 'status-' . str_replace(' ', '-', $p['parcel_status']);
                            $parcel_type = ($p['from_branch']==$branch_id)?'Outgoing':'Incoming';
                            $type_class = 'type-' . $parcel_type;
                            $recipient_full_address = trim($p['recipient_street'].', '.$p['recipient_city'].', '.$p['recipient_state'].' - '.$p['recipient_zip'].', '.$p['recipient_country']);
                    ?>
                        <tr>
                            <td><strong><?= $p['parcel_tracking_number'] ?></strong></td>
                            <td><?= htmlspecialchars($p['sender_name']) ?></td>
                            <td>
                                <?= htmlspecialchars($p['recipient_name']) ?><br>
                                <small class="text-muted">
                                    <i class="fas fa-phone me-1"></i><?= $p['recipient_phone'] ?><br>
                                    <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($recipient_full_address) ?>
                                </small>
                            </td>
                            <td><span class="<?= $type_class ?>"><i class="fas fa-arrow-<?= $parcel_type=='Outgoing' ? 'up' : 'down' ?>"></i> <?= $parcel_type ?></span></td>
                            <td><span class="badge-pill <?= $status_class ?>"><?= $p['parcel_status'] ?></span></td>
                            <td><?= date('M j, Y', strtotime($p['booking_date'])) ?></td>
                            <td class="text-center">
                                <div class="action-group">
                                    <a href="update_status.php?parcel_id=<?= $p['parcel_id'] ?>" class="btn btn-sm btn-outline-warning" title="Update Status">
                                        <i class="fas fa-sync-alt"></i>
                                    </a>
                                    <a href="track_parcel.php?tracking_number=<?= $p['parcel_tracking_number'] ?>" class="btn btn-sm btn-outline-info" title="Track Parcel">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="7" class="text-center text-muted">No parcels found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
