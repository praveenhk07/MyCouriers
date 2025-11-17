<?php
session_start();

// Embedded DB connection
include 'config.php';

$error = "";

if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['user_type']) {
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'staff':
            header("Location: staff/dashboard.php");
            break;
        default:
            header("Location: customer/dashboard.php");
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username  = trim($_POST['username']);
    $password  = trim($_POST['password']);
    $user_type = $_POST['user_type'];

    if ($user_type == 'admin') {
        $table    = "admin";
        $redirect = "admin/dashboard.php";
        $id_field = "admin_id";
        $username_field = "admin_username";
        $password_field = "admin_password";
        $first_name_field = "admin_first_name";
        $last_name_field  = "admin_last_name";
    } elseif ($user_type == 'staff') {
        $table    = "staff";
        $redirect = "staff/dashboard.php";
        $id_field = "staff_id";
        $username_field = "staff_username";
        $password_field = "staff_password";
        $first_name_field = "staff_first_name";
        $last_name_field  = "staff_last_name";
    } else {
        $table    = "customer";
        $redirect = "customer/dashboard.php";
        $id_field = "customer_id";
        $username_field = "customer_username";
        $password_field = "customer_password";
        $first_name_field = "customer_first_name";
        $last_name_field  = "customer_last_name";
    }

    $sql = "SELECT * FROM $table WHERE $username_field = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user[$password_field])) {
        $_SESSION['user_id']   = $user[$id_field];
        $_SESSION['username']  = $user[$username_field];
        $_SESSION['user_type'] = $user_type;
        $_SESSION['full_name'] = trim($user[$first_name_field] . ' ' . $user[$last_name_field]);

        header("Location: $redirect");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - Courier Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body {
        background-color: #f2f4f7;
        font-family: 'Segoe UI', sans-serif;
    }
    .login-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 30px;
    }
    .login-card {
        background-color: #ffffff;
        border-radius: 12px;
        padding: 40px 30px;
        box-shadow: 0 0 25px rgba(0,0,0,0.08);
        width: 100%;
        max-width: 420px;
    }
    .login-card h2 {
        font-weight: 600;
        margin-bottom: 10px;
        color: #333;
    }
    .login-card p {
        color: #777;
        margin-bottom: 30px;
    }
    .form-floating label {
        color: #555;
    }
    .form-control {
        border-radius: 8px;
    }
    .btn-primary {
        background-color: #2575fc;
        border: none;
        border-radius: 8px;
    }
    .btn-outline-secondary {
        border-radius: 8px;
    }
    .text-muted a {
        color: #2575fc;
        text-decoration: none;
    }
    .text-muted a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="text-center">
            <h2><i class="fas fa-box-open me-2"></i>Courier Login</h2>
            <p>Access your account securely</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-floating mb-3">
                <select class="form-select" id="user_type" name="user_type" required>
                    <option value="customer">Customer</option>
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
                <label for="user_type">User Type</label>
            </div>

            <div class="mb-3">
                 <label for="username" class="form-label"><i class="fas fa-user me-2"></i>Username</label>
                 <input type="text" class="form-control rounded-3" id="username" name="username" placeholder="Enter your username" required>
             </div>

            <div class="mb-3">
                 <label for="password" class="form-label"><i class="fas fa-lock me-2"></i>Password</label>
                 <input type="password" class="form-control rounded-3" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-2">
                <i class="fas fa-sign-in-alt me-1"></i> Login
            </button>

            <a href="index.php" class="btn btn-outline-secondary w-100">
                <i class="fas fa-home me-1"></i> Back to Home
            </a>
        </form>

        <div class="mt-3 text-center text-muted">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</div>
</body>
</html>