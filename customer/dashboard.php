<?php
require_once '../includes/auth_check.php';
requireRole('customer');
require_once '../config.php';

$customer_id = $_SESSION['user_id'] ?? 0;
$full_name  = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

// Parcel counts by latest status
$parcel_counts = [];
$status_sql = "
    SELECT su.parcel_status, COUNT(*) AS count
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
    WHERE p.sender_id = ?
    GROUP BY su.parcel_status
";
$stmt = $conn->prepare($status_sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) $parcel_counts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Recent 5 parcels
$recent_parcels = [];
$recent_sql = "
    SELECT p.parcel_tracking_number, p.recipient_first_name, p.recipient_last_name,
           COALESCE(su.parcel_status,'Pending') AS parcel_status, p.booking_date
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
    WHERE p.sender_id = ?
    ORDER BY p.booking_date DESC
    LIMIT 5
";
$stmt = $conn->prepare($recent_sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) $recent_parcels = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Dashboard - MyCouriers</title>
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
.wrapper {
  display: flex;
  min-height: 100vh;
}
.sidebar {
  width: 250px;
  background: #fff;
  border-right: 1px solid #e5e7eb;
  padding-top: 1rem;
  position: fixed;
  top: 0; bottom: 0;
  overflow-y: auto;
}
.sidebar a {
  display: flex;
  align-items: center;
  padding: 12px 20px;
  color: var(--text-dark);
  text-decoration: none;
  font-weight: 500;
  transition: all 0.2s ease;
}
.sidebar a i {
  margin-right: 10px;
  font-size: 1rem;
  width: 20px;
  text-align: center;
}
.sidebar a.active, .sidebar a:hover {
  background-color: var(--primary-color);
  color: white;
  border-radius: var(--border-radius);
  margin: 0 10px;
}
.content {
  flex-grow: 1;
  margin-left: 250px;
  padding: 2rem 3rem;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}
.page-header h3 { font-weight: 700; color: var(--primary-color); }
.quick-actions .card {
  border: none;
  border-radius: var(--border-radius);
  text-align: center;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  box-shadow: var(--card-shadow);
}
.quick-actions .card:hover { transform: translateY(-5px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
.quick-actions i { font-size: 1.8rem; margin-bottom: 8px; color: var(--primary-color); }
.stat-card {
  background: white;
  border: none;
  border-radius: var(--border-radius);
  padding: 25px;
  box-shadow: var(--card-shadow);
  text-align: center;
  transition: transform 0.2s ease;
}
.stat-card:hover { transform: translateY(-4px); }
.table thead { background-color: var(--primary-color); color: white; }
.badge { font-size: 0.8rem; padding: 6px 10px; border-radius: 6px; }
</style>
</head>
<body>
<div class="sidebar">
  <?php include 'navigation.php'; ?>
</div>

  <!-- Main Content -->
  <main class="content">
    <div class="page-header">
      <h3>Customer Dashboard</h3>
      <span class="text-secondary">Welcome, <?= htmlspecialchars($full_name ?: 'Customer'); ?> 👋</span>
    </div>

    <!-- Quick Actions -->
    <div class="row quick-actions mb-4">
      <div class="col-md-4 mb-3">
        <a href="book_parcel.php" class="text-decoration-none text-dark">
          <div class="card p-3"><i class="fas fa-plus-circle"></i><h6 class="fw-semibold mt-2">Book New Parcel</h6></div>
        </a>
      </div>
      <div class="col-md-4 mb-3">
        <a href="my_parcels.php" class="text-decoration-none text-dark">
          <div class="card p-3"><i class="fas fa-box"></i><h6 class="fw-semibold mt-2">My Parcels</h6></div>
        </a>
      </div>
      <div class="col-md-4 mb-3">
        <a href="track_parcel.php" class="text-decoration-none text-dark">
          <div class="card p-3"><i class="fas fa-search"></i><h6 class="fw-semibold mt-2">Track Parcel</h6></div>
        </a>
      </div>
    </div>

    <!-- Parcel Statistics -->
    <div class="row mb-4">
      <?php
      $colors = ['Booked'=>'primary','In Transit'=>'warning','Delivered'=>'success','Cancelled'=>'danger'];
      if ($parcel_counts):
        foreach ($parcel_counts as $count):
          $s = $count['parcel_status'] ?? 'Pending';
          $c = $colors[$s] ?? 'secondary';
      ?>
      <div class="col-md-3 mb-3">
        <div class="stat-card">
          <i class="fas fa-truck text-<?= $c ?> fa-2x mb-2"></i>
          <h6 class="fw-bold"><?= htmlspecialchars($s) ?></h6>
          <h3 class="fw-bold text-<?= $c ?>"><?= $count['count'] ?></h3>
        </div>
      </div>
      <?php endforeach; else: ?>
      <div class="col-12 text-center text-muted">No parcels booked yet.</div>
      <?php endif; ?>
    </div>

    <!-- Recent Parcels -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0">
        <h5 class="fw-bold text-primary mb-0">📦 Recent Parcels</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table align-middle table-striped table-bordered">
            <thead>
              <tr>
                <th>Tracking #</th>
                <th>Recipient</th>
                <th>Status</th>
                <th>Booking Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($recent_parcels): foreach ($recent_parcels as $r):
                $cls = match($r['parcel_status']) {
                  'Booked'=>'bg-info','In Transit'=>'bg-warning',
                  'Delivered'=>'bg-success','Cancelled'=>'bg-danger',
                  default=>'bg-secondary'
                };
              ?>
              <tr>
                <td><?= htmlspecialchars($r['parcel_tracking_number']); ?></td>
                <td><?= htmlspecialchars($r['recipient_first_name'].' '.$r['recipient_last_name']); ?></td>
                <td><span class="badge <?= $cls ?> text-white"><?= $r['parcel_status']; ?></span></td>
                <td><?= date('M j, Y', strtotime($r['booking_date'])); ?></td>
                <td><a href="track_parcel.php?tracking_number=<?= $r['parcel_tracking_number']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</a></td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="5" class="text-center text-muted">No recent parcels found</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </main>
</div>
</body>
</html>
