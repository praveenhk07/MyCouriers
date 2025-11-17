<?php
require_once '../includes/auth_check.php';
requireRole('customer');
require_once '../config.php';

$customer_id = $_SESSION['user_id'];
$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $street  = trim($_POST['street']);
    $city    = trim($_POST['city']);
    $state   = trim($_POST['state']);
    $zip     = trim($_POST['zip']);
    $country = trim($_POST['country']);

    $sql = "UPDATE customer 
            SET customer_street=?, customer_city=?, customer_state=?, customer_zip=?, customer_country=? 
            WHERE customer_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $street, $city, $state, $zip, $country, $customer_id);

    if ($stmt->execute()) {
        $success = "Address updated successfully!";
    } else {
        $error = "Error updating address: " . $conn->error;
    }
}

$sql = "SELECT * FROM customer WHERE customer_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile - Courier Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body {
        background-color: #f4f6f9;
        font-family: 'Segoe UI', sans-serif;
    }
    .profile-card {
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 0 20px rgba(0,0,0,0.05);
        padding: 30px;
    }
    .profile-header {
        font-weight: 600;
        font-size: 1.5rem;
        color: #333;
        margin-bottom: 20px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
    }
    .form-label {
        font-weight: 500;
        color: #555;
    }
    .btn-primary {
        background-color: #2575fc;
        border: none;
        border-radius: 6px;
    }
    .btn-secondary {
        border-radius: 6px;
    }
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="profile-card">
        <div class="profile-header"><i class="fas fa-user-circle me-2 text-primary"></i>My Profile</div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($customer): ?>
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" 
                           value="<?php echo htmlspecialchars($customer['customer_first_name'] . ' ' . $customer['customer_last_name']); ?>" readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" 
                           value="<?php echo htmlspecialchars($customer['customer_email']); ?>" readonly>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" class="form-control" 
                           value="<?php echo htmlspecialchars($customer['customer_phone']); ?>" readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Street</label>
                    <input type="text" name="street" class="form-control" 
                           value="<?php echo htmlspecialchars($customer['customer_street'] ?? ''); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" 
                           value="<?php echo htmlspecialchars($customer['customer_city'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" 
                           value="<?php echo htmlspecialchars($customer['customer_state'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Zip</label>
                    <input type="text" name="zip" class="form-control" 
                           value="<?php echo htmlspecialchars($customer['customer_zip'] ?? ''); ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Country</label>
                <input type="text" name="country" class="form-control" 
                       value="<?php echo htmlspecialchars($customer['customer_country'] ?? ''); ?>">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update Address</button>
                <a href="dashboard.php" class="btn btn-secondary">Back</a>
            </div>
        </form>
        <?php else: ?>
            <div class="alert alert-warning">Profile not found.</div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>