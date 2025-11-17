<?php
include 'config.php';

$success = $error = "";
$inputs = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputs = array_map('trim', $_POST);

    $first_name = $inputs['first_name'];
    $last_name  = $inputs['last_name'];
    $username   = $inputs['username'];
    $password   = $inputs['password'];
    $email      = $inputs['email'];
    $phone      = $inputs['phone'];
    $street     = $inputs['street'];
    $city       = $inputs['city'];
    $state      = $inputs['state'];
    $zip        = $inputs['zip'];
    $country    = $inputs['country'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        $stmt = $conn->prepare("SELECT 1 FROM customer WHERE customer_username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "Username already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO customer 
                    (customer_first_name, customer_last_name, customer_username, customer_password, 
                     customer_email, customer_phone, customer_street, customer_city, customer_state, 
                     customer_zip, customer_country)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssssssssss", 
                $first_name, $last_name, $username, $hashed_password, 
                $email, $phone, $street, $city, $state, $zip, $country
            );

            if ($stmt->execute()) {
                $success = "Registration successful! You can now <a href='login.php'>login</a>.";
                $inputs = [];
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Registration</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        background-color: #f2f4f7;
        font-family: 'Segoe UI', sans-serif;
    }
    .card {
        border-radius: 12px;
        padding: 35px;
        box-shadow: 0 0 25px rgba(0,0,0,0.08);
    }
    .form-label {
        font-weight: 500;
        color: #333;
    }
    .form-control {
        border-radius: 8px;
    }
    .btn-primary {
        background-color: #2575fc;
        border: none;
        border-radius: 8px;
    }
    .btn-secondary {
        border-radius: 8px;
    }
</style>
</head>
<body>
<div class="container mt-5">
    <div class="card">
        <h2 class="mb-4">Customer Registration</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control" value="<?php echo $inputs['first_name'] ?? ''; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" value="<?php echo $inputs['last_name'] ?? ''; ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo $inputs['username'] ?? ''; ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo $inputs['email'] ?? ''; ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?php echo $inputs['phone'] ?? ''; ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Street</label>
                <input type="text" name="street" class="form-control" value="<?php echo $inputs['street'] ?? ''; ?>">
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="<?php echo $inputs['city'] ?? ''; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" value="<?php echo $inputs['state'] ?? ''; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">ZIP</label>
                    <input type="text" name="zip" class="form-control" value="<?php echo $inputs['zip'] ?? ''; ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Country</label>
                <input type="text" name="country" class="form-control" value="<?php echo $inputs['country'] ?? ''; ?>">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Register</button>
                <a href="login.php" class="btn btn-secondary">Back to Login</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>