<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../config.php';

$branch = null;
$is_edit = false;
$error = "";

// Check if editing an existing branch
if (isset($_GET['id'])) {
    $branch_id = intval($_GET['id']);
    $is_edit = true;

    $stmt = $conn->prepare("SELECT * FROM branch WHERE branch_id = ?");
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $branch = $result->fetch_assoc();

    if (!$branch) {
        header("Location: branches.php?error=Branch not found");
        exit();
    }
}

// Fetch all staff members for manager dropdown
$staff_members = [];
$staff_sql = "SELECT staff_id, staff_first_name, staff_last_name FROM staff ORDER BY staff_first_name, staff_last_name";
$staff_result = $conn->query($staff_sql);
while ($row = $staff_result->fetch_assoc()) {
    $row['full_name'] = $row['staff_first_name'] . ' ' . ($row['staff_last_name'] ?? '');
    $staff_members[] = $row;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $branch_name = trim($_POST['branch_name']);
    $street = trim($_POST['branch_street']);
    $city = trim($_POST['branch_city']);
    $state = trim($_POST['branch_state']);
    $zip = trim($_POST['branch_zip']);
    $country = trim($_POST['branch_country']);
    $phone = trim($_POST['branch_phone']);
    $manager_id = !empty($_POST['manager_id']) ? intval($_POST['manager_id']) : null;

    if (empty($branch_name) || empty($street) || empty($city) || empty($phone)) {
        $error = "Please fill in all required fields!";
    } else {
        if ($is_edit) {
            $stmt = $conn->prepare("UPDATE branch SET branch_name=?, branch_street=?, branch_city=?, branch_state=?, branch_zip=?, branch_country=?, branch_phone=?, manager_id=? WHERE branch_id=?");
            $stmt->bind_param("ssssssiii", $branch_name, $street, $city, $state, $zip, $country, $phone, $manager_id, $branch_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO branch (branch_name, branch_street, branch_city, branch_state, branch_zip, branch_country, branch_phone, manager_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssi", $branch_name, $street, $city, $state, $zip, $country, $phone, $manager_id);
        }

        if ($stmt->execute()) {
            $success_message = $is_edit ? "Branch updated successfully!" : "Branch created successfully!";
            header("Location: branches.php?success=" . urlencode($success_message));
            exit();
        } else {
            $error = "Error saving branch. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit' : 'Add'; ?> Branch - Courier Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar p-0">
            <?php include 'navigation.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><?php echo $is_edit ? 'Edit Branch' : 'Add New Branch'; ?></h2>
                <a href="branches.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Branches
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Branch Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="branch_name" class="form-label">Branch Name *</label>
                            <input type="text" class="form-control" id="branch_name" name="branch_name"
                                   value="<?php echo $is_edit ? htmlspecialchars($branch['branch_name']) : ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="branch_street" class="form-label">Street *</label>
                            <input type="text" class="form-control" id="branch_street" name="branch_street"
                                   value="<?php echo $is_edit ? htmlspecialchars($branch['branch_street']) : ''; ?>" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="branch_city" class="form-label">City *</label>
                                <input type="text" class="form-control" id="branch_city" name="branch_city"
                                       value="<?php echo $is_edit ? htmlspecialchars($branch['branch_city']) : ''; ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="branch_state" class="form-label">State</label>
                                <input type="text" class="form-control" id="branch_state" name="branch_state"
                                       value="<?php echo $is_edit ? htmlspecialchars($branch['branch_state']) : ''; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="branch_zip" class="form-label">ZIP</label>
                                <input type="text" class="form-control" id="branch_zip" name="branch_zip"
                                       value="<?php echo $is_edit ? htmlspecialchars($branch['branch_zip']) : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="branch_country" class="form-label">Country</label>
                            <input type="text" class="form-control" id="branch_country" name="branch_country"
                                   value="<?php echo $is_edit ? htmlspecialchars($branch['branch_country']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="branch_phone" class="form-label">Phone Number *</label>
                            <input type="text" class="form-control" id="branch_phone" name="branch_phone"
                                   value="<?php echo $is_edit ? htmlspecialchars($branch['branch_phone']) : ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="manager_id" class="form-label">Manager</label>
                            <select class="form-select" id="manager_id" name="manager_id">
                                <option value="">Select Manager</option>
                                <?php foreach ($staff_members as $staff): ?>
                                    <option value="<?php echo $staff['staff_id']; ?>"
                                        <?php echo ($is_edit && $branch['manager_id'] == $staff['staff_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($staff['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Optional - assign a staff member as branch manager</div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?php echo $is_edit ? 'Update Branch' : 'Create Branch'; ?>
                        </button>
                        <a href="branches.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
