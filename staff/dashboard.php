<?php
// DEV: show errors while debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/auth_check.php';
requireRole('staff');
require_once '../config.php';

$staff_id = $_SESSION['user_id'] ?? 0;

// --- Get staff info and branch (safe) ---
$staff = ['staff_first_name' => '', 'staff_last_name' => '', 'branch_name' => ''];
if ($staff_id) {
    $sql = "SELECT s.*, b.branch_name
            FROM staff s
            JOIN branch b ON s.branch_id = b.branch_id
            WHERE s.staff_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $staff_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $staff = $res->fetch_assoc();
        }
        $stmt->close();
    }
}
$branch_id = (int)($staff['branch_id'] ?? 0);

// --- Parcel counts grouped by latest status ---
// We count status by taking the latest status_update per parcel using status_sequence
$parcel_counts = [];
$parcels_sql = "
SELECT su.parcel_status, COUNT(*) AS count
FROM parcel p
LEFT JOIN (
    SELECT su1.parcel_id, su1.parcel_status
    FROM status_update su1
    WHERE su1.status_sequence = (
        SELECT MAX(su2.status_sequence) FROM status_update su2 WHERE su2.parcel_id = su1.parcel_id
    )
) su ON su.parcel_id = p.parcel_id
WHERE p.from_branch = ?
GROUP BY su.parcel_status
";
if ($stmt = $conn->prepare($parcels_sql)) {
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) $parcel_counts = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// --- Recent parcels (latest status) ---
$recent_result = false;
$recent_sql = "
SELECT p.parcel_id, p.parcel_tracking_number,
       CONCAT(c.customer_first_name, ' ', COALESCE(c.customer_last_name,'')) AS sender_name,
       CONCAT(p.recipient_first_name, ' ', COALESCE(p.recipient_last_name,'')) AS recipient_name,
       COALESCE(su.parcel_status, 'Unknown') AS parcel_status, p.booking_date
FROM parcel p
LEFT JOIN customer c ON p.sender_id = c.customer_id
LEFT JOIN (
    SELECT su1.parcel_id, su1.parcel_status
    FROM status_update su1
    WHERE su1.status_sequence = (
        SELECT MAX(su2.status_sequence) FROM status_update su2 WHERE su2.parcel_id = su1.parcel_id
    )
) su ON su.parcel_id = p.parcel_id
WHERE p.from_branch = ?
ORDER BY p.booking_date DESC
LIMIT 5
";
if ($stmt = $conn->prepare($recent_sql)) {
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $recent_result = $stmt->get_result();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Staff Dashboard - Courier Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root {
  --primary-color: #004aad;
  --secondary-color: #f4f6f9;
  --text-dark: #333;
  --card-shadow: 0 4px 15px rgba(0,0,0,0.08);
  --border-radius: 12px;
}
body {
  background-color: var(--secondary-color);
  font-family: "Inter", "Poppins", sans-serif;
  color: var(--text-dark);
  margin: 0;
}
.wrapper { display:flex; min-height:100vh; }
.sidebar {
  width:250px; background:#fff; border-right:1px solid #e5e7eb;
  padding-top:1rem; position:fixed; top:0; bottom:0; overflow-y:auto;
}
.sidebar a { display:flex; align-items:center; padding:12px 20px; color:var(--text-dark); text-decoration:none; font-weight:500; transition:all .2s ease; }
.sidebar a i { margin-right:10px; }
.sidebar a.active, .sidebar a:hover { background-color:var(--primary-color); color:#fff; border-radius:var(--border-radius); margin:0 10px; }
.content { flex-grow:1; margin-left:250px; padding:2rem 3rem; }
.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
.page-header h3 { font-weight:700; color:var(--primary-color); }
.stat-card { background:white; border:none; border-radius:var(--border-radius); padding:25px; box-shadow:var(--card-shadow); text-align:center; transition:transform .2s ease; }
.stat-card:hover { transform:translateY(-4px); }
.table thead { background-color:var(--primary-color); color:white; }
.badge { font-size:.8rem; padding:6px 10px; border-radius:6px; }
.card { border-radius:12px; box-shadow:0 6px 18px rgba(0,0,0,0.06); }
.card-header.bg-white { background:#fff; border-bottom:1px solid #eee; }
</style>
</head>
<body>
<div class="wrapper">

  <!-- sidebar (kept separate file) -->
  <div class="sidebar">
    <?php include 'navigation.php'; ?>
  </div>

  <!-- main content -->
  <main class="content">
    <div class="page-header">
      <h3>Staff Dashboard</h3>
      <div class="text-secondary">Welcome, <?= htmlspecialchars(($staff['staff_first_name'] ?? 'Staff') . ' ' . ($staff['staff_last_name'] ?? '')) ?> 👋</div>
    </div>

    <!-- quick actions -->
    <div class="row quick-actions mb-4">
      <div class="col-md-4 mb-3">
        <a href="book_parcel.php" class="text-decoration-none text-dark">
          <div class="stat-card p-3 text-center">
            <i class="fas fa-plus-circle fa-2x text-primary"></i>
            <h6 class="mt-2 fw-semibold">Book Parcel</h6>
          </div>
        </a>
      </div>
      <div class="col-md-4 mb-3">
        <a href="track_parcel.php" class="text-decoration-none text-dark">
          <div class="stat-card p-3 text-center">
            <i class="fas fa-search fa-2x text-primary"></i>
            <h6 class="mt-2 fw-semibold">Track Parcel</h6>
          </div>
        </a>
      </div>
      <div class="col-md-4 mb-3">
        <a href="parcels.php" class="text-decoration-none text-dark">
          <div class="stat-card p-3 text-center">
            <i class="fas fa-box fa-2x text-primary"></i>
            <h6 class="mt-2 fw-semibold">My Branch Parcels</h6>
          </div>
        </a>
      </div>
    </div>

    <!-- parcel counts -->
    <div class="row mb-4">
      <?php
        $colors = ['Booked'=>'primary','In Transit'=>'warning','Delivered'=>'success','Cancelled'=>'danger'];
        if (!empty($parcel_counts)):
          foreach ($parcel_counts as $count):
            $s = $count['parcel_status'] ?? 'Pending';
            $c = $colors[$s] ?? 'secondary';
      ?>
      <div class="col-md-3 mb-3">
        <div class="stat-card">
          <i class="fas fa-truck text-<?= $c ?> fa-2x mb-2"></i>
          <h6 class="fw-bold"><?= htmlspecialchars($s) ?></h6>
          <h3 class="fw-bold text-<?= $c ?>"><?= (int)$count['count'] ?></h3>
        </div>
      </div>
      <?php endforeach; else: ?>
      <div class="col-12 text-center text-muted">No parcel stats found.</div>
      <?php endif; ?>
    </div>

    <!-- recent parcels table -->
    <div class="card mb-4">
      <div class="card-header bg-white">
        <h5 class="fw-bold text-primary mb-0">📦 Recent Parcels</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table align-middle table-striped">
            <thead>
              <tr>
                <th>Tracking #</th>
                <th>Sender</th>
                <th>Recipient</th>
                <th>Status</th>
                <th>Booking Date</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($recent_result && $recent_result->num_rows > 0): ?>
                <?php while ($row = $recent_result->fetch_assoc()): 
                  $status = $row['parcel_status'] ?? 'Booked';
                  $badgeClass = 'bg-secondary';
                  if ($status === 'Booked') $badgeClass = 'bg-info text-dark';
                  if ($status === 'In Transit') $badgeClass = 'bg-warning text-dark';
                  if ($status === 'Delivered') $badgeClass = 'bg-success';
                  if ($status === 'Cancelled') $badgeClass = 'bg-danger';
                ?>
                <tr>
                  <td><?= htmlspecialchars($row['parcel_tracking_number']) ?></td>
                  <td><?= htmlspecialchars($row['sender_name']) ?></td>
                  <td><?= htmlspecialchars($row['recipient_name']) ?></td>
                  <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span></td>
                  <td><?= htmlspecialchars(date('M j, Y', strtotime($row['booking_date']))) ?></td>
                  <td class="text-center">
                    <a href="update_status.php?parcel_id=<?= (int)$row['parcel_id'] ?>" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-edit"></i> Update
                    </a>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="6" class="text-center text-muted">No recent parcels found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </main>
</div>

<!-- bootstrap js (optional) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
