<?php
require_once '../includes/auth_check.php';
requireRole('staff');
require_once '../config.php';

// Get staff branch
$staff_id = $_SESSION['user_id'];
$branch_sql = "SELECT branch_id FROM staff WHERE staff_id = ?";
$stmt = $conn->prepare($branch_sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$staff_branch = $stmt->get_result()->fetch_assoc();
$from_branch = $staff_branch['branch_id'] ?? 0;

$message = "";

// ===== Handle booking submission =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_parcel'])) {
    // Sender details
    $sender_first_name = trim($_POST['sender_first_name']);
    $sender_last_name  = trim($_POST['sender_last_name']);
    $sender_phone      = trim($_POST['sender_phone']);
    $sender_email      = trim($_POST['sender_email']);
    $sender_street     = trim($_POST['sender_street']);
    $sender_city       = trim($_POST['sender_city']);
    $sender_state      = trim($_POST['sender_state']);
    $sender_zip        = trim($_POST['sender_zip']);
    $sender_country    = trim($_POST['sender_country']);

    // Check if sender already exists
    $check_sql = "SELECT customer_id FROM customer WHERE customer_phone = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $sender_phone);
    $stmt->execute();
    $sender_result = $stmt->get_result()->fetch_assoc();

    if ($sender_result) {
        $sender_id = $sender_result['customer_id'];
    } else {
        $dummy_username = 'sender' . time() . rand(100,999);
        $dummy_password = password_hash('password123', PASSWORD_DEFAULT);

        $insert_sender = "INSERT INTO customer 
            (customer_username, customer_password, customer_first_name, customer_last_name, customer_phone, customer_email, customer_street, customer_city, customer_state, customer_zip, customer_country)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($insert_sender);
        $stmt->bind_param(
            "sssssssssss",
            $dummy_username, $dummy_password, $sender_first_name, $sender_last_name,
            $sender_phone, $sender_email, $sender_street, $sender_city, $sender_state, $sender_zip, $sender_country
        );
        $stmt->execute();
        $sender_id = $stmt->insert_id;
    }

    // Recipient details
    $recipient_phone      = trim($_POST['recipient_phone']);
    $recipient_first_name = trim($_POST['recipient_first_name']);
    $recipient_last_name  = trim($_POST['recipient_last_name']);
    $recipient_street     = trim($_POST['recipient_street']);
    $recipient_city       = trim($_POST['recipient_city']);
    $recipient_state      = trim($_POST['recipient_state']);
    $recipient_zip        = trim($_POST['recipient_zip']);
    $recipient_country    = trim($_POST['recipient_country']);

    // Parcel details
    $to_branch = $_POST['to_branch'];
    $weight    = $_POST['weight'];
    $price     = $weight * 50; // ₹50 per kg
    $tracking_number = 'TRK' . time() . rand(100, 999);

    $insert_parcel = "INSERT INTO parcel 
        (parcel_tracking_number, sender_id, recipient_first_name, recipient_last_name, recipient_street, recipient_city, recipient_state, recipient_zip, recipient_country, recipient_phone, from_branch, to_branch, parcel_weight, parcel_price, booking_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($insert_parcel);
    $stmt->bind_param(
        "sissssssssiidd",
        $tracking_number, $sender_id, $recipient_first_name, $recipient_last_name,
        $recipient_street, $recipient_city, $recipient_state, $recipient_zip, $recipient_country, $recipient_phone,
        $from_branch, $to_branch, $weight, $price
    );

    if ($stmt->execute()) {
        $parcel_id = $stmt->insert_id;
        $status_sql = "INSERT INTO status_update (parcel_id, parcel_status, status_update_time, updated_by) VALUES (?, 'Booked', NOW(), ?)";
        $status_stmt = $conn->prepare($status_sql);
        $status_stmt->bind_param("ii", $parcel_id, $staff_id);
        $status_stmt->execute();

        $message = "✅ Parcel booked successfully! Tracking #: " . $tracking_number;
    } else {
        $message = "❌ Error: " . $conn->error;
    }
}

// ===== Handle AJAX autofill =====
if (isset($_GET['lookup_phone'])) {
    $phone = $_GET['lookup_phone'];
    $type = $_GET['type']; 

    if ($type === 'sender') {
        $sql = "SELECT customer_first_name as first_name, customer_last_name as last_name, customer_phone as phone, customer_email as email, customer_street as street, customer_city as city, customer_state as state, customer_zip as zip, customer_country as country FROM customer WHERE customer_phone = ?";
    } else {
        $sql = "SELECT recipient_first_name as first_name, recipient_last_name as last_name, recipient_phone as phone, recipient_street as street, recipient_city as city, recipient_state as state, recipient_zip as zip, recipient_country as country 
                FROM parcel WHERE recipient_phone = ? ORDER BY parcel_id DESC LIMIT 1";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    echo json_encode($result ?: []);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book Parcel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.form-section-title {
    font-weight: 600;
    font-size: 18px;
    margin-top: 25px;
    padding-bottom: 8px;
    border-bottom: 2px solid #0d6efd;
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="container py-4">

<h3 class="mb-4">📦 Book a Parcel</h3>

<?php if (!empty($message)): ?>
    <div class="alert alert-info"><?php echo $message; ?></div>
<?php endif; ?>

<form method="post" class="border rounded p-4 bg-white shadow-sm">

    <div class="form-section-title">Sender Details</div>
    <div class="row">
        <div class="col-md-4 mb-3"><input type="text" name="sender_phone" id="sender_phone" class="form-control" placeholder="Phone" required></div>
        <div class="col-md-4 mb-3"><input type="text" name="sender_first_name" id="sender_first_name" class="form-control" placeholder="First Name" required></div>
        <div class="col-md-4 mb-3"><input type="text" name="sender_last_name" id="sender_last_name" class="form-control" placeholder="Last Name"></div>
        <div class="col-md-6 mb-3"><input type="email" name="sender_email" id="sender_email" class="form-control" placeholder="Email"></div>
        <div class="col-md-6 mb-3"><input type="text" name="sender_street" id="sender_street" class="form-control" placeholder="Street"></div>
        <div class="col-md-4 mb-3"><input type="text" name="sender_city" id="sender_city" class="form-control" placeholder="City"></div>
        <div class="col-md-4 mb-3"><input type="text" name="sender_state" id="sender_state" class="form-control" placeholder="State"></div>
        <div class="col-md-4 mb-3"><input type="text" name="sender_zip" id="sender_zip" class="form-control" placeholder="Zip Code"></div>
        <div class="col-md-6 mb-3"><input type="text" name="sender_country" id="sender_country" class="form-control" placeholder="Country"></div>
    </div>

    <div class="form-section-title">Recipient Details</div>
    <div class="row">
        <div class="col-md-6 mb-3"><input type="text" name="recipient_phone" id="recipient_phone" class="form-control" placeholder="Phone" required></div>
        <div class="col-md-6 mb-3"><input type="text" name="recipient_first_name" id="recipient_first_name" class="form-control" placeholder="First Name" required></div>
        <div class="col-md-6 mb-3"><input type="text" name="recipient_last_name" id="recipient_last_name" class="form-control" placeholder="Last Name"></div>
        <div class="col-md-12 mb-3"><input type="text" name="recipient_street" id="recipient_street" class="form-control" placeholder="Street Address"></div>
        <div class="col-md-4 mb-3"><input type="text" name="recipient_city" id="recipient_city" class="form-control" placeholder="City"></div>
        <div class="col-md-4 mb-3"><input type="text" name="recipient_state" id="recipient_state" class="form-control" placeholder="State"></div>
        <div class="col-md-4 mb-3"><input type="text" name="recipient_zip" id="recipient_zip" class="form-control" placeholder="Zip Code"></div>
        <div class="col-md-6 mb-3"><input type="text" name="recipient_country" id="recipient_country" class="form-control" placeholder="Country"></div>
    </div>

    <div class="form-section-title">Parcel Details</div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Destination Branch</label>
            <select name="to_branch" class="form-select" required>
                <?php
                $branch_sql = "SELECT branch_id, branch_name FROM branch WHERE branch_id != ?";
                $stmt = $conn->prepare($branch_sql);
                $stmt->bind_param("i", $from_branch);
                $stmt->execute();
                $branches = $stmt->get_result();
                while ($row = $branches->fetch_assoc()) {
                    echo "<option value='{$row['branch_id']}'>{$row['branch_name']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Weight (kg)</label>
            <input type="number" step="0.01" name="weight" id="weight" class="form-control" placeholder="Weight" required>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Price (₹)</label>
            <input type="text" id="price" class="form-control" readonly>
        </div>
    </div>

    <button type="submit" name="book_parcel" class="btn btn-primary">Book Parcel</button>
    <a href="parcels.php" class="btn btn-outline-secondary">Back</a>

</form>

<script>
// Auto-calc price
document.getElementById('weight').addEventListener('input', function() {
    let weight = parseFloat(this.value);
    let priceField = document.getElementById('price');
    priceField.value = (!isNaN(weight) && weight > 0) ? (weight * 50).toFixed(2) : '';
});

// Auto-fill sender details
$('#sender_phone').on('blur', function() {
    let phone = $(this).val();
    if (phone) {
        $.get('?lookup_phone=' + phone + '&type=sender', function(data) {
            let info = JSON.parse(data);
            if (info && info.first_name) {
                $('#sender_first_name').val(info.first_name);
                $('#sender_last_name').val(info.last_name);
                $('#sender_email').val(info.email);
                $('#sender_street').val(info.street);
                $('#sender_city').val(info.city);
                $('#sender_state').val(info.state);
                $('#sender_zip').val(info.zip);
                $('#sender_country').val(info.country);
            }
        });
    }
});

// Auto-fill recipient details
$('#recipient_phone').on('blur', function() {
    let phone = $(this).val();
    if (phone) {
        $.get('?lookup_phone=' + phone + '&type=recipient', function(data) {
            let info = JSON.parse(data);
            if (info && info.first_name) {
                $('#recipient_first_name').val(info.first_name);
                $('#recipient_last_name').val(info.last_name);
                $('#recipient_street').val(info.street);
                $('#recipient_city').val(info.city);
                $('#recipient_state').val(info.state);
                $('#recipient_zip').val(info.zip);
                $('#recipient_country').val(info.country);
            }
        });
    }
});
</script>

</body>
</html>
