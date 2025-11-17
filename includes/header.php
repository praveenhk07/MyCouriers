<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /courier_db/login.php");
    exit();
}

// Get user info
$user_type = $_SESSION['user_type'] ?? '';
$full_name = $_SESSION['full_name'] ?? '';
$username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CourierSys Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/courier_db/assets/css/style.css">
    <style>
      .navbar {
        background-color: #ffffff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      }
      .navbar-brand {
        font-weight: 600;
        font-size: 1.25rem;
        color: #007bff;
      }
      .navbar-nav .nav-link {
        font-weight: 500;
        padding: 8px 16px;
        border-radius: 8px;
        transition: background-color 0.2s ease;
      }
      .navbar-nav .nav-link:hover {
        background-color: #f0f4f8;
      }
      .nav-link.text-danger {
        font-weight: 600;
      }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="/courier_db/">
            <i class="fas fa-shipping-fast me-2"></i> MyCouriers
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center">
                <?php if ($user_type === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="/courier_db/admin/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/courier_db/admin/parcels.php"><i class="fas fa-boxes me-2"></i> Parcels</a></li>
                    <li class="nav-item"><a class="nav-link" href="/courier_db/admin/customers.php"><i class="fas fa-users me-2"></i> Customers</a></li>
                    <li class="nav-item"><a class="nav-link" href="/courier_db/admin/staff.php"><i class="fas fa-user-tie me-2"></i> Staff</a></li>
                    <li class="nav-item"><a class="nav-link" href="/courier_db/admin/branches.php"><i class="fas fa-map-marker-alt me-2"></i> Branches</a></li>
                <?php elseif ($user_type === 'staff'): ?>
                    <li class="nav-item"><a class="nav-link" href="/courier_db/staff/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/courier_db/staff/parcels.php"><i class="fas fa-boxes me-2"></i> Parcels</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/courier_db/customer/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/courier_db/track.php"><i class="fas fa-search-location me-2"></i> Track Parcel</a></li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="/courier_db/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid mt-3">