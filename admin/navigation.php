<div class="sidebar">
    <div class="px-3 mb-4">
    <h5 class="fw-bold text-center text-primary">MyCouriers</h5>
    </div>
    <nav class="flex-grow-1">
        <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="branches.php"><i class="fas fa-building"></i> Branches</a>
        <a href="staff.php"><i class="fas fa-users"></i> Staff</a>
        <a href="customers.php"><i class="fas fa-user-friends"></i> Customers</a>
        <a href="parcels.php"><i class="fas fa-box"></i> Parcels</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
    </nav>

    <!-- Logout at bottom -->
    <a href="../logout.php" class="logout-link mt-auto"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<style>
.sidebar {
  width: 250px;
  background: #fff;
  border-right: 1px solid #e5e7eb;
  padding-top: 1rem;
  position: fixed;
  top: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}

.sidebar h4 {
  text-align: center;
  margin-bottom: 1rem;
  color: #004aad; /* primary color */
}

.sidebar a {
  display: flex;
  align-items: center;
  padding: 12px 20px;
  color: #333;
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

.sidebar a.active, 
.sidebar a:hover {
  background-color: #004aad;
  color: white;
  border-radius: 12px;
  margin: 0 10px;
}

/* Logout link fixed at bottom */
.sidebar .logout-link {
  margin-top: auto;
  padding: 12px 20px;
  color: #ff4d4f;
  text-decoration: none;
  display: flex;
  align-items: center;
}

.sidebar .logout-link i {
  color: #ff4d4f;
  margin-right: 10px;
}

.sidebar .logout-link:hover {
  background-color: #004aad;
  color: white;
  border-radius: 12px;
}
</style>
