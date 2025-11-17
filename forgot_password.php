<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $user_type = $_POST['user_type'];
    
    if (empty($email)) {
        $error = "Please enter your email address!";
    } else {
        // Check which table to search based on user type
        $table = '';
        switch ($user_type) {
            case 'admin': $table = 'admin'; break;
            case 'staff': $table = 'staff'; break;
            case 'customer': $table = 'customer'; break;
            default: $error = "Invalid user type!"; break;
        }
        
        if (!isset($error)) {
            $sql = "SELECT * FROM $table WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // In a real application, you would:
                // 1. Generate a password reset token
                // 2. Send an email with reset instructions
                // 3. Store the token in the database with an expiration time
                
                $success = "Password reset instructions have been sent to your email address.";
            } else {
                $error = "No account found with that email address!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Courier Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h2><i class="fas fa-key"></i> Forgot Password</h2>
                <p class="text-muted">Reset your account password</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="user_type" class="form-label">User Type</label>
                    <select class="form-select" id="user_type" name="user_type" required>
                        <option value="customer" selected>Customer</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                    <div class="form-text">Enter the email address associated with your account</div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Reset Password</button>
            </form>
            
            <div class="mt-3 text-center">
                <p>Remember your password? <a href="login.php">Login here</a></p>
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>