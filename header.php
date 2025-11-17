<?php
// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get logged-in info
$full_name = $_SESSION['full_name'] ?? 'Guest';
$user_type = $_SESSION['user_type'] ?? '';
$username  = $_SESSION['username'] ?? '';

// Determine logout link relative to current folder
// Admin/staff/customer pages are inside subfolders, so ../ is needed
$logout_link = '../logout.php';
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">CourierSys</a>

        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <?php if ($user_type): ?>
                    <li class="nav-item">
                        <span class="nav-link">Logged in as: <?php echo htmlspecialchars($user_type); ?></span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">User: <?php echo htmlspecialchars($username); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $logout_link; ?>">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
