<?php
session_start();
include '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get statistics
$parcels_count   = $conn->query("SELECT COUNT(*) as total FROM parcel")->fetch_assoc()['total'];
$customers_count = $conn->query("SELECT COUNT(*) as total FROM customer")->fetch_assoc()['total'];
$staff_count     = $conn->query("SELECT COUNT(*) as total FROM staff")->fetch_assoc()['total'];
$branches_count  = $conn->query("SELECT COUNT(*) as total FROM branch")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - Courier Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
  position: relative;
}

/* Main Content */
.content {
  flex-grow: 1;
  margin-left: 250px; /* keep same width as sidebar */
  padding: 2rem 3rem;
}

/* Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}
.page-header h3 { 
  font-weight: 700; 
  color: var(--primary-color); 
}

/* Cards */
.stat-card {
  background: white;
  border: none;
  border-radius: var(--border-radius);
  padding: 25px;
  box-shadow: var(--card-shadow);
  text-align: center;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.stat-card:hover { 
  transform: translateY(-4px); 
  box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}
.stat-card h6 { 
  font-weight: 600; 
  font-size: 0.9rem; 
  color: #555; 
}
.stat-card h2 { 
  font-weight: 700; 
  color: var(--primary-color); 
}
.stat-card a {
  color: var(--primary-color);
  font-weight: 500;
  font-size: 0.85rem;
  transition: color 0.2s ease;
  text-decoration: none;
  margin-top: 10px;
}
.stat-card a:hover {
  color: #004aad;
  text-decoration: underline;
}

/* Table */
.table thead {
  background-color: var(--primary-color);
  color: white;
}
.table-hover tbody tr:hover {
  background-color: #e8f0fe;
}

/* Badge */
.badge {
  font-size: 0.8rem; 
  padding: 6px 10px; 
  border-radius: 6px;
}
</style>
</head>
<body>

<div class="wrapper">

  <!-- Sidebar -->
  <?php include 'navigation.php'; ?>

  <!-- Main Content -->
  <div class="content">
    <div class="page-header">
      <h3><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h3>
      <p class="text-muted mb-0">Welcome, <strong><?php echo $_SESSION['full_name']; ?></strong></p>
    </div>

    <!-- Stat Cards with View Details -->
    <div class="row mb-4">
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
          <div>
            <i class="fas fa-box fa-2x mb-2 text-primary"></i>
            <h6>Total Parcels</h6>
            <h2><?php echo $parcels_count; ?></h2>
          </div>
          <a href="parcels.php">View Details &rarr;</a>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
          <div>
            <i class="fas fa-user-friends fa-2x mb-2 text-success"></i>
            <h6>Total Customers</h6>
            <h2><?php echo $customers_count; ?></h2>
          </div>
          <a href="customers.php">View Details &rarr;</a>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
          <div>
            <i class="fas fa-users fa-2x mb-2 text-warning"></i>
            <h6>Total Staff</h6>
            <h2><?php echo $staff_count; ?></h2>
          </div>
          <a href="staff.php">View Details &rarr;</a>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
          <div>
            <i class="fas fa-building fa-2x mb-2 text-info"></i>
            <h6>Total Branches</h6>
            <h2><?php echo $branches_count; ?></h2>
          </div>
          <a href="branches.php">View Details &rarr;</a>
        </div>
      </div>
    </div>

    <!-- Recent Parcels Table -->
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-header bg-white">
        <h5 class="mb-0 text-primary">Recent Parcels</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Tracking #</th>
                <th>Sender</th>
                <th>Recipient</th>
                <th>Status</th>
                <th>Updated At</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $recent_sql = "
                SELECT 
                    p.parcel_tracking_number AS tracking_number,
                    CONCAT(c.customer_first_name,' ',c.customer_last_name) AS sender,
                    CONCAT(p.recipient_first_name,' ',p.recipient_last_name) AS recipient,
                    su.parcel_status AS status,
                    su.status_update_time AS updated_at
                FROM parcel p
                JOIN customer c ON p.sender_id = c.customer_id
                LEFT JOIN (
                    SELECT parcel_id, parcel_status, status_update_time
                    FROM status_update
                    WHERE (parcel_id, status_update_time) IN (
                        SELECT parcel_id, MAX(status_update_time)
                        FROM status_update
                        GROUP BY parcel_id
                    )
                ) su ON su.parcel_id = p.parcel_id
                ORDER BY updated_at DESC
                LIMIT 5";
              
              $recent_result = $conn->query($recent_sql);

              if ($recent_result->num_rows > 0) {
                  while ($row = $recent_result->fetch_assoc()) {
                      echo "<tr>
                          <td>" . htmlspecialchars($row['tracking_number']) . "</td>
                          <td>" . htmlspecialchars($row['sender']) . "</td>
                          <td>" . htmlspecialchars($row['recipient']) . "</td>
                          <td><span class='badge bg-info text-dark'>" . htmlspecialchars($row['status']) . "</span></td>
                          <td>" . date('M j, Y H:i', strtotime($row['updated_at'])) . "</td>
                      </tr>";
                  }
              } else {
                  echo "<tr><td colspan='5' class='text-center text-muted'>No parcels found</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div> <!-- end content -->

</div> <!-- end wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
