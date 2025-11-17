<?php
require_once '../includes/auth_check.php';
requireRole('customer');
require_once '../config.php';

$error = '';
$success = '';

// AJAX recipient lookup
if (isset($_GET['phone'])) {
    header('Content-Type: application/json; charset=utf-8');
    $phone = trim($_GET['phone']);
    $stmt = $conn->prepare("SELECT * FROM parcel WHERE recipient_phone = ? ORDER BY parcel_id DESC LIMIT 1");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result ? $result->fetch_assoc() : null);
    $stmt->close();
    exit;
}

// POST submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $recipient_first_name = trim($_POST['recipient_first_name']);
    $recipient_last_name = trim($_POST['recipient_last_name']);
    $recipient_street = trim($_POST['recipient_street']);
    $recipient_city = trim($_POST['recipient_city']);
    $recipient_state = trim($_POST['recipient_state']);
    $recipient_zip = trim($_POST['recipient_zip']);
    $recipient_country = trim($_POST['recipient_country']);
    $recipient_phone = trim($_POST['recipient_phone']);

    $from_branch = (int)($_POST['from_branch'] ?? 0);
    $to_branch = $from_branch;
    $weight = (float)($_POST['weight'] ?? 0);
    $parcel_details = trim($_POST['parcel_details'] ?? '');

    $customer_id = $_SESSION['user_id'];
    $tracking_number = 'TRK' . strtoupper(uniqid());
    $price = $weight * 50;

    if ($weight <= 0) $error = "Weight must be greater than zero.";
    else {
        $sql = "INSERT INTO parcel 
        (parcel_tracking_number, sender_id, recipient_first_name, recipient_last_name, recipient_street, recipient_city, recipient_state, recipient_zip, recipient_country, recipient_phone, from_branch, to_branch, parcel_weight, parcel_price, parcel_details) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissssssssiidss", $tracking_number, $customer_id, $recipient_first_name, $recipient_last_name, $recipient_street, $recipient_city, $recipient_state, $recipient_zip, $recipient_country, $recipient_phone, $from_branch, $to_branch, $weight, $price, $parcel_details);
        if ($stmt->execute()) {
            $parcel_id = $conn->insert_id;
            // Set initial status to 'Pending' so staff can review and confirm (change to 'Booked')
            $status_stmt = $conn->prepare("INSERT INTO status_update (parcel_id, parcel_status, updated_by, status_update_notes) VALUES (?, 'Pending', ?, 'Parcel created by customer - pending staff confirmation')");
            $system_user_id = 1;
            $status_stmt->bind_param("ii", $parcel_id, $system_user_id);
            $status_stmt->execute();
            $status_stmt->close();
            $success = "Parcel booked successfully! Tracking: <strong>$tracking_number</strong> | Price: <strong>₹$price</strong>";
        } else $error = "Error booking parcel: " . $stmt->error;
        $stmt->close();
    }
}

$branches_result = $conn->query("SELECT * FROM branch ORDER BY branch_name");
$branches = $branches_result ? $branches_result->fetch_all(MYSQLI_ASSOC) : [];
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-center">
        <div style="max-width: 900px; width: 100%;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>📦 Book New Parcel</h3>
                <a href="my_parcels.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Parcels</a>
            </div>

            <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <div class="card shadow-sm hover-card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Recipient Phone *</label>
                                <input type="text" class="form-control" id="recipient_phone" name="recipient_phone" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Weight (kg) *</label>
                                <input type="number" class="form-control" id="weight" name="weight" step="0.01" min="0.1" required>
                            </div>

                            <div id="recipient-details" class="col-12 mt-2">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Recipient First Name *</label>
                                        <input type="text" class="form-control" name="recipient_first_name" id="recipient_first_name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Recipient Last Name</label>
                                        <input type="text" class="form-control" name="recipient_last_name" id="recipient_last_name">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Street Address *</label>
                                        <textarea class="form-control" name="recipient_street" id="recipient_street" rows="2" required></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">City *</label>
                                        <input type="text" class="form-control" name="recipient_city" id="recipient_city" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">State *</label>
                                        <input type="text" class="form-control" name="recipient_state" id="recipient_state" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">ZIP Code *</label>
                                        <input type="text" class="form-control" name="recipient_zip" id="recipient_zip" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Country *</label>
                                        <input type="text" class="form-control" name="recipient_country" id="recipient_country" required>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">From Branch *</label>
                                <select class="form-select" name="from_branch" required>
                                    <option value="">Select Branch</option>
                                    <?php foreach($branches as $b): ?>
                                        <option value="<?= (int)$b['branch_id']; ?>"><?= htmlspecialchars($b['branch_name'].' - '.$b['branch_city']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Price (₹)</label>
                                <input type="text" class="form-control" id="price" readonly placeholder="₹50 per kg">
                                <div class="form-text text-muted">Automatically calculated at ₹50 per kg</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Parcel Details</label>
                                <textarea class="form-control" name="parcel_details" rows="3"></textarea>
                            </div>

                            <div class="col-12 text-end mt-2">
                                <button type="submit" class="btn btn-primary px-4">Book Parcel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div> <!-- centered content -->
    </div> <!-- flex wrapper -->
</div> <!-- container -->

<?php include '../includes/footer.php'; ?>

<script>
$(function() {
    // Phone lookup
    $('#recipient_phone').on('blur', function(){
        var phone = $(this).val().trim();
        if(phone.length > 0){
            $.get('?phone=' + encodeURIComponent(phone), function(data){
                var res = (typeof data === 'object') ? data : JSON.parse(data || '{}');
                if(res){
                    $('#recipient_first_name').val(res.recipient_first_name || '');
                    $('#recipient_last_name').val(res.recipient_last_name || '');
                    $('#recipient_street').val(res.recipient_street || '');
                    $('#recipient_city').val(res.recipient_city || '');
                    $('#recipient_state').val(res.recipient_state || '');
                    $('#recipient_zip').val(res.recipient_zip || '');
                    $('#recipient_country').val(res.recipient_country || '');
                } else {
                    $('#recipient-details input, #recipient-details textarea').val('');
                }
            });
        }
    });

    // Price calculation
    $('#weight').on('input', function(){
        var w = parseFloat(this.value);
        var rate = 50; // ₹50 per
                var rate = 50; // ₹50 per kg
        $('#price').val(!isNaN(w) && w > 0 ? (w * rate).toFixed(2) : '');
    });
});
</script>

<style>
.hover-card {
    transition: 0.3s;
    cursor: pointer;
}
.hover-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}
</style>