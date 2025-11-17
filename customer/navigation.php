<nav class="sidebar">
    <div class="px-3 mb-4">
    <h5 class="fw-bold text-center text-primary">MyCouriers</h5>
    </div>
    <a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a>
    <a href="book_parcel.php"><i class="fas fa-plus-circle"></i> Book Parcel</a>
    <a href="my_parcels.php"><i class="fas fa-box"></i> My Parcels</a>
    <a href="track_parcel.php"><i class="fas fa-search"></i> Track Parcel</a>
    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
    <a href="../logout.php" class="text-danger mt-auto"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
<style>
  :root {
  --primary-color: #004aad;
  --secondary-color: #f7f9fc;
  --text-dark: #333;
  --text-light: #6c757d;
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
  background: white;
  border-right: 1px solid #e5e7eb;
  box-shadow: var(--card-shadow);
  padding-top: 1.5rem;
  display: flex;
  flex-direction: column;
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
  padding: 2rem 3rem;
}
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
.quick-actions .card {
  border: none;
  border-radius: var(--border-radius);
  text-align: center;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  box-shadow: var(--card-shadow);
}
.quick-actions .card:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}
.quick-actions i {
  font-size: 1.8rem;
  margin-bottom: 8px;
  color: var(--primary-color);
}
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
.table thead {
  background-color: var(--primary-color);
  color: white;
}
.badge {
  font-size: 0.8rem;
  padding: 6px 10px;
  border-radius: 6px;
}
</style>