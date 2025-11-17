<?php
require_once '../includes/auth_check.php';
requireRole('customer');
require_once '../config.php';

$customer_id = $_SESSION['user_id'];

$sql = "SELECT p.parcel_id, p.parcel_tracking_number, p.recipient_first_name, p.recipient_last_name,
               p.parcel_weight, p.parcel_price, p.from_branch, p.to_branch, p.booking_date,
               b1.branch_name AS from_branch_name, b2.branch_name AS to_branch_name,
               (SELECT su.parcel_status 
                FROM status_update su 
                WHERE su.parcel_id = p.parcel_id 
                ORDER BY su.status_sequence DESC LIMIT 1) AS latest_status
        FROM parcel p
        LEFT JOIN branch b1 ON p.from_branch = b1.branch_id
        LEFT JOIN branch b2 ON p.to_branch = b2.branch_id
        WHERE p.sender_id = ?
        ORDER BY p.booking_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$parcels = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>My Parcels - Courier Management System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f4f6f9;
    }

    /* Sidebar */
    .sidebar {
      width: 250px;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      background-color: #fff;
      border-right: 1px solid #ddd;
      padding-top: 60px; /* adjust if header height changes */
    }

    /* Main content */
    .main-content {
      margin-left: 250px; /* same as sidebar width */
      padding: 20px;
    }

    /* Card & table styles */
    .card {
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0,0,0,0.05);
      border: none;
    }
    .card-header {
      background-color: #fff;
      font-weight: 500;
      font-size: 1.1rem;
      border-bottom: 1px solid #eee;
    }
    .badge-pill {
      padding: 6px 12px;
      font-size: 0.8rem;
      border-radius: 50px;
      font-weight: 500;
    }
    .empty-state {
      background-color: #fff;
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 40px;
      text-align: center;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.03);
    }
    .empty-state i {
      font-size: 2rem;
      color: #999;
      margin-bottom: 10px;
    }

    /* Toast positioning */
    .toast-container {
      z-index: 1055;
    }
  </style>
</head>
<body>


<!-- sidebar -->
 <div class="sidebar">
  <?php include 'navigation.php'; ?>
</div>
<!-- Main Content -->
<div class="main-content">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-box me-2 text-primary"></i>My Parcels</h2>
    <a href="book_parcel.php" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Book Parcel</a>
  </div>

  <?php if (empty($parcels)): ?>
    <div class="empty-state">
      <i class="fas fa-box-open"></i>
      <p class="mt-2 mb-3">You haven't sent any parcels yet.</p>
      <a href="book_parcel.php" class="btn btn-sm btn-primary">
        <i class="fas fa-plus-circle me-1"></i> Book your first parcel
      </a>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="card-header">Parcel History</div>
      <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Tracking #</th>
              <th>Recipient</th>
              <th>From</th>
              <th>To</th>
              <th>Weight (kg)</th>
              <th>Price (₹)</th>
              <th>Status</th>
              <th>Booking Date</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($parcels as $parcel): 
              $status = $parcel['latest_status'] ?? 'Pending';
              $status_class = match($status) {
                'Booked' => 'bg-info text-dark',
                'In Transit' => 'bg-warning text-dark',
                'Delivered' => 'bg-success',
                'Cancelled' => 'bg-danger',
                'Pending' => 'bg-secondary',
                default => 'bg-secondary'
              };
            ?>
            <tr>
              <td><strong><?= htmlspecialchars($parcel['parcel_tracking_number']); ?></strong></td>
              <td><?= htmlspecialchars($parcel['recipient_first_name'] . ' ' . $parcel['recipient_last_name']); ?></td>
              <td><?= htmlspecialchars($parcel['from_branch_name']); ?></td>
              <td><?= htmlspecialchars($parcel['to_branch_name']); ?></td>
              <td><?= number_format($parcel['parcel_weight'], 2); ?></td>
              <td>₹<?= number_format($parcel['parcel_price'], 2); ?></td>
              <td><span class="badge-pill <?= $status_class; ?>"><?= htmlspecialchars($status); ?></span></td>
              <td><?= date('M j, Y', strtotime($parcel['booking_date'])); ?></td>
              <td class="text-center">
                <div class="d-flex justify-content-center gap-2">
                  <a href="track_parcel.php?tracking_number=<?= urlencode($parcel['parcel_tracking_number']); ?>" 
                     class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Track Parcel">
                    <i class="fas fa-eye"></i>
                  </a>
                  <button type="button"
                          class="btn btn-sm btn-outline-danger"
                          data-bs-toggle="tooltip"
                          title="Cancel Parcel"
                          onclick="handleCancel(<?= $parcel['parcel_id']; ?>, '<?= $parcel['parcel_tracking_number']; ?>', '<?= $status; ?>')">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="cancelToast" class="toast align-items-center text-bg-warning border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">
        Can’t be cancelled right now.
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function handleCancel(parcelId, trackingNumber, status) {
  if (status === 'Pending') {
    if (confirm(`Are you sure you want to cancel parcel ${trackingNumber}?`)) {
      window.location.href = `cancel_parcel.php?id=${parcelId}`;
    }
  } else {
    const toast = new bootstrap.Toast(document.getElementById('cancelToast'));
    toast.show();
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.forEach(function (tooltipTriggerEl) {
    new bootstrap.Tooltip(tooltipTriggerEl);
  });
});
</script>
</body>
</html>
