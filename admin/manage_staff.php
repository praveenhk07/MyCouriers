<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../config.php';

$staff = null;
$is_edit = false;
$error = '';

// Check if editing
if (isset($_GET['id'])) {
    $staff_id = intval($_GET['id']);
    $is_edit = true;

    $stmt = $conn->prepare("SELECT * FROM staff WHERE staff_id = ?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $staff = $result->fetch_assoc();

    if (!$staff) {
        header("Location: staff.php?error=Staff member not found");
        exit();
    }
}

// Fetch all branches
$branches_result = $conn->query("SELECT * FROM branch ORDER BY branch_name");
$branches = [];
while ($row = $branches_result->fetch_assoc()) {
    $branches[] = $row;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username   = trim($_POST['username']);
    $password   = trim($_POST['password']);
    $email      = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $branch_id  = intval($_POST['branch_id']);
    $position   = trim($_POST['position']);
    $phone      = trim($_POST['phone']);

    // Validation
    if (empty($username) || empty($email) || empty($first_name) || empty($branch_id) || empty($position) || empty($phone)) {
        $error = "Please fill in all required fields!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } else {
        // Check username uniqueness
        if ($is_edit) {
            $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE staff_username = ? AND staff_id != ?");
            $stmt->bind_param("si", $username, $staff_id);
        } else {
            $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE staff_username = ?");
            $stmt->bind_param("s", $username);
        }
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Username already exists!";
        } else {
            // Hash password if provided, or default for new staff
            if (!empty($password)) {
                $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            } elseif (!$is_edit) {
                $password_hashed = password_hash('password123', PASSWORD_DEFAULT);
            }

            if ($is_edit) {
                // Update staff
                $sql = "UPDATE staff SET staff_username=?, staff_email=?, staff_first_name=?, staff_last_name=?, branch_id=?, position=?, staff_phone=?";
                // types: username(s), email(s), first_name(s), last_name(s), branch_id(i), position(s), phone(s)
                $types = "ssssiss";
                $params = [$username, $email, $first_name, $last_name, $branch_id, $position, $phone];

                if (!empty($password)) {
                    $sql .= ", staff_password=?";
                    $types .= "s";
                    $params[] = $password_hashed;
                }

                $sql .= " WHERE staff_id=?";
                $types .= "i";
                $params[] = $staff_id;

                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
            } else {
                // Insert new staff
                $stmt = $conn->prepare("INSERT INTO staff (staff_username, staff_password, staff_email, staff_first_name, staff_last_name, branch_id, position, staff_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                // types: username(s), password(s), email(s), first_name(s), last_name(s), branch_id(i), position(s), phone(s)
                $stmt->bind_param("sssssiss", $username, $password_hashed, $email, $first_name, $last_name, $branch_id, $position, $phone);
                $stmt->execute();
            }

            header("Location: staff.php?success=" . urlencode($is_edit ? "Staff updated successfully!" : "Staff created successfully!"));
            exit();
        }
    }
}

// Fetch all staff for listing
$staff_list_result = $conn->query("
    SELECT s.*, b.branch_name, CONCAT(s.staff_first_name, ' ', s.staff_last_name) AS full_name
    FROM staff s
    LEFT JOIN branch b ON s.branch_id = b.branch_id
    ORDER BY s.staff_first_name, s.staff_last_name
");

$staff_list = [];
while ($row = $staff_list_result->fetch_assoc()) {
    $staff_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $is_edit ? 'Edit' : 'Add'; ?> Staff - Courier Management System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 sidebar p-0">
            <?php include 'navigation.php'; ?>
        </div>
        <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><?php echo $is_edit ? 'Edit Staff Member' : 'Add New Staff Member'; ?></h2>
                <a href="staff.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Staff</a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Staff Details</h5></div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" name="username" class="form-control" value="<?php echo $is_edit ? htmlspecialchars($staff['staff_username']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" value="<?php echo $is_edit ? htmlspecialchars($staff['staff_email']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-control" value="<?php echo $is_edit ? htmlspecialchars($staff['staff_first_name']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" value="<?php echo $is_edit ? htmlspecialchars($staff['staff_last_name']) : ''; ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone *</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo $is_edit ? htmlspecialchars($staff['staff_phone']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Branch *</label>
                                <select name="branch_id" class="form-select" required>
                                    <option value="">Select Branch</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch['branch_id']; ?>" <?php echo ($is_edit && $staff['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Position *</label>
                                <input type="text" name="position" class="form-control" value="<?php echo $is_edit ? htmlspecialchars($staff['position']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password <?php echo $is_edit ? '(leave blank to keep current)' : '*'; ?></label>
                            <input type="password" name="password" class="form-control">
                            <?php if (!$is_edit) echo '<div class="form-text">Default password: password123</div>'; ?>
                        </div>

                        <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Update Staff Member' : 'Create Staff Member'; ?></button>
                        <a href="staff.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">All Staff Members</h5></div>
                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Branch</th>
                                <th>Phone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff_list as $s): ?>
                            <tr>
                                <td><?php echo $s['staff_id']; ?></td>
                                <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($s['staff_username']); ?></td>
                                <td><?php echo htmlspecialchars($s['staff_email']); ?></td>
                                <td><?php echo htmlspecialchars($s['position']); ?></td>
                                <td><?php echo htmlspecialchars($s['branch_name'] ?: 'Not assigned'); ?></td>
                                <td><?php echo htmlspecialchars($s['staff_phone']); ?></td>
                                <td>
                                    <a href="?id=<?php echo $s['staff_id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="delete_staff.php?id=<?php echo $s['staff_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');" title="Delete"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
