<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../config.php';

// Get all branches with manager information
$sql = "SELECT b.*,
               CONCAT(s.staff_first_name, ' ', s.staff_last_name) AS manager_name
        FROM branch b
        LEFT JOIN staff s ON b.manager_id = s.staff_id
        ORDER BY b.branch_name";
$result = $conn->query($sql);

$branches = [];
while ($row = $result->fetch_assoc()) {
    $branches[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Branches - Courier Management System</title>
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
        <h2 class="page-header"><i class="fas fa-building me-2 text-primary"></i>Manage Branches</h2>
        <a href="manage_branch.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i> Add New Branch
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Branch operation completed successfully!</div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">Error: <?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">All Branches</div>
        <div class="card-body table-responsive">
            <table class="table table-hover table-bordered align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Branch Name</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Manager</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($branches)): ?>
                        <tr><td colspan="6" class="text-center">No branches found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($branches as $branch): ?>
                            <tr>
                                <td><?php echo $branch['branch_id']; ?></td>
                                <td><?php echo htmlspecialchars($branch['branch_name']); ?></td>
                                <td>
                                    <?php
                                        $address_parts = array_filter([
                                            $branch['branch_street'] ?? '',
                                            $branch['branch_city'] ?? '',
                                            $branch['branch_state'] ?? '',
                                            $branch['branch_zip'] ?? '',
                                            $branch['branch_country'] ?? ''
                                        ]);
                                        echo htmlspecialchars(implode(', ', $address_parts));
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($branch['branch_phone'] ?? ''); ?></td>
                                <td>
                                    <?php echo $branch['manager_name'] ? htmlspecialchars($branch['manager_name']) : '<span class="text-muted">Not assigned</span>'; ?>
                                </td>
                                <td>
                                    <a href="manage_branch.php?id=<?php echo $branch['branch_id']; ?>" class="btn btn-sm btn-warning" title="Edit Branch">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="delete_branch.php?id=<?php echo $branch['branch_id']; ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this branch?');"
                                       title="Delete Branch">
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