<?php
require_once '../includes/auth_check.php';
requireRole('staff');
require_once '../config.php';

$staff_id = $_SESSION['user_id'];

$sql = "SELECT s.staff_id, 
               s.staff_username AS username, 
               s.staff_first_name AS first_name, 
               s.staff_last_name AS last_name, 
               s.staff_email AS email, 
               s.staff_phone AS phone, 
               b.branch_name
        FROM staff s
        JOIN branch b ON s.branch_id = b.branch_id
        WHERE s.staff_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $current_password = trim($_POST['current_password']);
    $new_password     = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($email) || empty($phone)) {
        $error = "Please fill in all required fields!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } else {
        try {
            $email_check_sql = "SELECT staff_id FROM staff WHERE staff_email = ? AND staff_id != ?";
            $email_check_stmt = $conn->prepare($email_check_sql);
            $email_check_stmt->bind_param("si", $email, $staff_id);
            $email_check_stmt->execute();
            $email_check_result = $email_check_stmt->get_result();

            if ($email_check_result->num_rows > 0) {
                $error = "Email address already exists!";
            } else {
                $update_sql = "UPDATE staff SET staff_email = ?, staff_phone = ?";
                $params = [$email, $phone];
                $types = "ss";

                if (!empty($current_password) && !empty($new_password)) {
                    if ($new_password !== $confirm_password) {
                        $error = "New passwords do not match!";
                    } elseif ($current_password !== 'password123') {
                        $error = "Current password is incorrect!";
                    } else {
                        $update_sql .= ", staff_password = ?";
                        $params[] = $new_password;
                        $types .= "s";
                    }
                }

                if (!isset($error)) {
                    $update_sql .= " WHERE staff_id = ?";
                    $params[] = $staff_id;
                    $types .= "i";

                    $stmt = $conn->prepare($update_sql);
                    $stmt->bind_param($types, ...$params);

                    if ($stmt->execute()) {
                        $success = "Profile updated successfully!";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $staff_id);
                        $stmt->execute();
                        $staff = $stmt->get_result()->fetch_assoc();
                    } else {
                        $error = "Error updating profile. Please try again.";
                    }
                }
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile - Staff</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {
    background-color: #f4f6f9;
    font-family: 'Segoe UI', sans-serif;
}
.card {
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(0,0,0,0.05);
}
.card-header {
    background-color: #fff;
    font-weight: 500;
    font-size: 1.1rem;
    border-bottom: 1px solid #eee;
}
.center-wrapper {
    display: flex;
    justify-content: center;
}
.center-content {
    max-width: 800px;
    width: 100%;
}
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4 center-wrapper">
    <div class="center-content">
        <h2 class="mb-3"><i class="fas fa-user-circle me-2 text-primary"></i>My Profile</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">Edit Profile</div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($staff['username']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($staff['branch_name']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($staff['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone *</label>
                            <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($staff['phone']) ?>" required>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5>Change Password</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password">
                        </div>
                    </div>
                    <div class="form-text mt-2 mb-3">
                        Leave password fields blank to keep current password.
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Profile</button>
                        <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Cancel</a>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>