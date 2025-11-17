<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../config.php';

// Get all staff with branch info and full_name
$sql = "SELECT s.*, b.branch_name, 
               CONCAT(s.staff_first_name, ' ', s.staff_last_name) AS full_name
        FROM staff s
        LEFT JOIN branch b ON s.branch_id = b.branch_id
        ORDER BY s.staff_first_name, s.staff_last_name";

$result = $conn->query($sql);

$staff_members = [];
while ($row = $result->fetch_assoc()) {
    $staff_members[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Staff - Courier Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body {
        background-color: #f4f6f9;
        font-family: 'Segoe UI', sans-serif;
    }
    .page-header {
        font-weight: 600;
        font-size: 1.5rem;
        color: #333;
        margin-bottom: 20px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
    }
    .card {
        border-radius: 12px;
        box-shadow: 0 0 15px rgba(0,0,0,0.05);
        border: none;
    }
    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #eee;
        font-weight: 500;
        font-size: 1.1rem;
    }
    .table th {
        background-color: #f8f9fa;
        font-weight: 500;
        color: #555;
    }
    .table td {
        vertical-align: middle;
    }
    .btn-sm i {
        margin-right: 4px;
    }
    .btn-warning {
        background-color: #ffc107;
        border: none;
        color: #212529;
    }
    .btn-danger {
        background-color: #dc3545;
        border: none;
    }
    .btn-primary {
        background-color: #2575fc;
        border: none;
    }
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="page-header"><i class="fas fa-users me-2 text-primary"></i>Manage Staff</h2>
        <a href="manage_staff.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i> Add New Staff
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">Error: <?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">All Staff Members</div>
        <div class="card-body table-responsive">
            <table class="table table-hover table-bordered align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Position</th>
                        <th>Branch</th>
                        <th>Phone</th>
                        <th>Hire Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($staff_members)): ?>
                        <tr><td colspan="9" class="text-center">No staff members found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($staff_members as $staff): ?>
                            <tr>
                                <td><?php echo $staff['staff_id']; ?></td>
                                <td><?php echo htmlspecialchars($staff['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($staff['staff_username']); ?></td>
                                <td><?php echo htmlspecialchars($staff['staff_email']); ?></td>
                                <td><?php echo htmlspecialchars($staff['position']); ?></td>
                                <td><?php echo $staff['branch_name'] ? htmlspecialchars($staff['branch_name']) : '<span class="text-muted">Not assigned</span>'; ?></td>
                                <td><?php echo htmlspecialchars($staff['staff_phone']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($staff['staff_hire_date'])); ?></td>
                                <td>
                                    <a href="manage_staff.php?id=<?php echo $staff['staff_id']; ?>" class="btn btn-sm btn-warning" title="Edit Staff">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="delete_staff.php?id=<?php echo $staff['staff_id']; ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this staff member?');"
                                       title="Delete Staff">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>