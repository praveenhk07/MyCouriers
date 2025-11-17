<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../config.php';

if (!isset($_GET['id'])) {
    die("Customer ID not specified.");
}

$customer_id = intval($_GET['id']);

// Fetch customer details
$stmt = $conn->prepare("SELECT * FROM customer WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

if (!$customer) {
    die("Customer not found.");
}

// Fetch parcels for this customer
$parcels_stmt = $conn->prepare("
    SELECT p.*, 
           b1.branch_name AS from_branch_name, 
           b2.branch_name AS to_branch_name
    FROM parcel p
    LEFT JOIN branch b1 ON p.from_branch = b1.branch_id
    LEFT JOIN branch b2 ON p.to_branch = b2.branch_id
    WHERE p.sender_id = ?
    ORDER BY p.parcel_id DESC
");
$parcels_stmt->bind_param("i", $customer_id);
$parcels_stmt->execute();
$parcels_result = $parcels_stmt->get_result();
$parcels = [];
while ($row = $parcels_result->fetch_assoc()) {
    $parcels[] = $row;
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <h2>Customer Details: 
        <?php echo htmlspecialchars($customer['customer_first_name'] . ' ' . ($customer['customer_last_name'] ?? '')); ?>
    </h2>

    <table class="table table-bordered mt-3">
        <tr><th>ID</th><td><?php echo $customer['customer_id']; ?></td></tr>
        <tr><th>Username</th><td><?php echo htmlspecialchars($customer['customer_username']); ?></td></tr>
        <tr><th>Email</th><td><?php echo htmlspecialchars($customer['customer_email']); ?></td></tr>
        <tr><th>Phone</th><td><?php echo htmlspecialchars($customer['customer_phone']); ?></td></tr>
        <tr>
            <th>Address</th>
            <td>
                <?php
                    $address_parts = array_filter([
                        $customer['customer_street'] ?? '',
                        $customer['customer_city'] ?? '',
                        $customer['customer_state'] ?? '',
                        $customer['customer_zip'] ?? '',
                        $customer['customer_country'] ?? ''
                    ]);
                    echo htmlspecialchars(implode(', ', $address_parts));
                ?>
            </td>
        </tr>
    </table>

    <h4 class="mt-5">Parcels</h4>
    <?php if (empty($parcels)): ?>
        <p>No parcels found for this customer.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Parcel Tracking #</th>
                        <th>From Branch</th>
                        <th>To Branch</th>
                        <th>Recipient</th>
                        <th>Weight (kg)</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parcels as $parcel): ?>
                        <tr>
                            <td><?php echo $parcel['parcel_id']; ?></td>
                            <td><?php echo htmlspecialchars($parcel['parcel_tracking_number']); ?></td>
                            <td><?php echo htmlspecialchars($parcel['from_branch_name']); ?></td>
                            <td><?php echo htmlspecialchars($parcel['to_branch_name']); ?></td>
                            <td><?php echo htmlspecialchars($parcel['recipient_first_name'] . ' ' . ($parcel['recipient_last_name'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($parcel['parcel_weight']); ?></td>
                            <td><?php echo htmlspecialchars($parcel['parcel_price']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <a href="customers.php" class="btn btn-secondary mt-3">Back to Customers</a>
</div>

<?php include '../includes/footer.php'; ?>
