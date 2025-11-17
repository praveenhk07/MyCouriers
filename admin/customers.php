<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../config.php';

// Fetch all customers
$sql = "SELECT * FROM customer ORDER BY customer_id DESC";
$result = $conn->query($sql);

$customers = [];
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

// Fetch parcel counts per customer
$parcel_counts = [];
if (!empty($customers)) {
    $customer_ids = array_column($customers, 'customer_id');
    $ids = implode(',', $customer_ids);

    $count_sql = "SELECT sender_id, COUNT(*) AS parcel_count 
                  FROM parcel 
                  WHERE sender_id IN ($ids)
                  GROUP BY sender_id";
    $counts_result = $conn->query($count_sql);
    while ($count = $counts_result->fetch_assoc()) {
        $parcel_counts[$count['sender_id']] = $count['parcel_count'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Customers - Courier Management System</title>
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
    .btn-info {
        background-color: #17a2b8;
        border: none;
    }
    .btn-danger {
        background-color: #dc3545;
        border: none;
    }
    .parcel-pill {
        display: inline-block;
        padding: 6px 12px;
        font-size: 0.85rem;
        background-color: #2575fc;
        color: #fff;
        border-radius: 50px;
        font-weight: 500;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .action-group {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
    }
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="page-header"><i class="fas fa-user-friends me-2 text-primary"></i>Manage Customers</h2>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Operation completed successfully!</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">All Customers</div>
        <div class="card-body table-responsive">
            <table class="table table-hover table-bordered align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Parcels</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr><td colspan="9" class="text-center">No customers found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo $customer['customer_id']; ?></td>
                                <td><?php echo htmlspecialchars($customer['customer_first_name'] . ' ' . ($customer['customer_last_name'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($customer['customer_username']); ?></td>
                                <td><?php echo htmlspecialchars($customer['customer_email']); ?></td>
                                <td>
                                    <?php
                                        $address_parts = array_filter([
                                            $customer['customer_street'] ?? '',
                                            $customer['customer_city'] ?? '',
                                            $customer['customer_state'] ?? '',
                                            $customer['customer_zip'] ?? '',
                                            $customer['customer_country'] ?? ''
                                        ]);
                                        $full_address = implode(', ', $address_parts);
                                        echo htmlspecialchars(mb_substr($full_address, 0, 30)) . (strlen($full_address) > 30 ? '...' : '');
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($customer['customer_phone']); ?></td>
                                <td class="text-center">
                                    <span class="parcel-pill" title="Total parcels sent">
                                        <?php echo $parcel_counts[$customer['customer_id']] ?? 0; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($customer['created_at'] ?? 'now')); ?></td>
                                <td>
                                    <div class="action-group">
                                        <a href="customer_details.php?id=<?php echo $customer['customer_id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="delete_customer.php?id=<?php echo $customer['customer_id']; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this customer? All their parcels will also be deleted.');"
                                           title="Delete Customer">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
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