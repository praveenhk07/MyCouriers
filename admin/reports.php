<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../config.php';

// Get report parameters
$report_type = $_GET['report_type'] ?? 'parcels';
$date_from   = $_GET['date_from'] ?? date('Y-m-01');
$date_to     = $_GET['date_to'] ?? date('Y-m-d');
$branch_filter = $_GET['branch'] ?? '';

// Get branches for filter
$branches_result = $conn->query("SELECT * FROM branch ORDER BY branch_name");
$branches = [];
if ($branches_result) {
    $branches = $branches_result->fetch_all(MYSQLI_ASSOC);
}

// Initialize
$report_data = [];
$report_title = '';
$total_amount = 0;
$total_parcels = 0;

// Generate report data
switch ($report_type) {
    case 'parcels':
        $report_title = 'Parcels Report';
        $sql = "SELECT p.parcel_id, p.parcel_tracking_number,
               CONCAT(c.customer_first_name,' ',IFNULL(c.customer_last_name,'')) AS sender_name,
               CONCAT(p.recipient_first_name,' ',IFNULL(p.recipient_last_name,'')) AS recipient_name,
               b1.branch_name AS from_branch_name,
               b2.branch_name AS to_branch_name,
               p.parcel_price,
               COALESCE(su_latest.parcel_status,'Pending') AS status,
               p.booking_date
        FROM parcel p
        JOIN customer c ON p.sender_id = c.customer_id
        JOIN branch b1 ON p.from_branch = b1.branch_id
        LEFT JOIN branch b2 ON p.to_branch = b2.branch_id
        LEFT JOIN (
            SELECT su1.parcel_id, su1.parcel_status
            FROM status_update su1
            INNER JOIN (
                SELECT parcel_id, MAX(status_update_time) AS latest_time
                FROM status_update
                GROUP BY parcel_id
            ) su2 ON su1.parcel_id = su2.parcel_id AND su1.status_update_time = su2.latest_time
            GROUP BY su1.parcel_id
        ) su_latest ON su_latest.parcel_id = p.parcel_id
        WHERE p.booking_date BETWEEN ? AND ?";



        $params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
        $types = "ss";

        if (!empty($branch_filter)) {
            $sql .= " AND (p.from_branch = ? OR p.to_branch = ?)";
            $params[] = $branch_filter;
            $params[] = $branch_filter;
            $types .= "ii";
        }

        $sql .= " ORDER BY p.booking_date DESC";
        break;

    case 'customers':
        $report_title = 'Customers Report';
        $sql = "SELECT customer_id, CONCAT(customer_first_name,' ',IFNULL(customer_last_name,'')) AS full_name,
                       customer_email, customer_phone
                FROM customer
                ORDER BY customer_first_name";
        $params = [];
        $types = "";
        break;

    case 'revenue':
        $report_title = 'Revenue Report';
        $sql = "SELECT DATE(p.booking_date) AS booking_date, COUNT(*) AS parcel_count,
                       SUM(p.parcel_price) AS total_revenue, b.branch_name
                FROM parcel p
                JOIN branch b ON p.from_branch = b.branch_id
                WHERE p.booking_date BETWEEN ? AND ?";
        $params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
        $types = "ss";

        if (!empty($branch_filter)) {
            $sql .= " AND p.from_branch = ?";
            $params[] = $branch_filter;
            $types .= "i";
        }

        $sql .= " GROUP BY DATE(p.booking_date), b.branch_name ORDER BY booking_date DESC";
        break;

    default:
        $report_type = 'parcels';
        $report_title = 'Parcels Report';
}

// Execute query
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $report_data = $result->fetch_all(MYSQLI_ASSOC);

        // Totals calculation
        if ($report_type == 'parcels') {
            $parcel_ids = [];
            foreach ($report_data as $row) {
                if (!in_array($row['parcel_id'], $parcel_ids)) {
                    $total_amount += $row['parcel_price'];
                    $parcel_ids[] = $row['parcel_id'];
                }
            }
            $total_parcels = count($parcel_ids);
        }

        if ($report_type == 'customers') {
            $total_parcels = count($report_data); // total customers
            $total_amount = 0; // no amount
        }

        if ($report_type == 'revenue') {
            foreach ($report_data as $row) {
                $total_parcels += $row['parcel_count'];
                $total_amount += $row['total_revenue'];
            }
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $report_title ?> - Courier Management System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script>
function printReport() {
    const printContents = document.getElementById('report-section').innerHTML;
    const originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
}
</script>
</head>
<body>


<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 sidebar p-0">
            <?php include 'navigation.php'; ?>
        </div>

        <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
            <h2><?= $report_title ?></h2>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Report Filters</h5></div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="report_type" class="form-label">Report Type</label>
                                <select class="form-select" id="report_type" name="report_type">
                                    <option value="parcels" <?= $report_type=='parcels'?'selected':'' ?>>Parcels</option>
                                    <option value="customers" <?= $report_type=='customers'?'selected':'' ?>>Customers</option>
                                    <option value="revenue" <?= $report_type=='revenue'?'selected':'' ?>>Revenue</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="branch" class="form-label">Branch</label>
                                <select class="form-select" id="branch" name="branch">
                                    <option value="">All Branches</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?= $branch['branch_id']; ?>" <?= $branch_filter==$branch['branch_id']?'selected':'' ?>>
                                            <?= htmlspecialchars($branch['branch_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?= $date_from; ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?= $date_to; ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                        <button type="button" class="btn btn-secondary" onclick="printReport()"><i class="fas fa-print"></i> Print</button>
                    </form>
                </div>
            </div>

            <!-- Report Table -->
            <div class="card" id="report-section">
                <div class="card-header"><h5 class="mb-0"><?= $report_title ?></h5></div>
                <div class="card-body">
                    <?php if (!empty($report_data)): ?>
                        <div class="mb-3">
                            <strong>
                            <?php if($report_type=='customers'): ?>
                                Total Customers: <?= $total_parcels; ?>
                            <?php else: ?>
                                Total Parcels: <?= $total_parcels; ?> | Total Amount: ₹<?= number_format($total_amount,2); ?>
                            <?php endif; ?>
                            </strong>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <?php if($report_type=='parcels'): ?>
                                            <th>Tracking #</th><th>Sender</th><th>Recipient</th>
                                            <th>From Branch</th><th>To Branch</th><th>Status</th>
                                            <th>Booking Date</th><th>Price (₹)</th>
                                        <?php elseif($report_type=='customers'): ?>
                                            <th>Customer Name</th><th>Email</th><th>Phone</th>
                                        <?php elseif($report_type=='revenue'): ?>
                                            <th>Date</th><th>Branch</th><th>Parcel Count</th><th>Estimated Revenue (₹)</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($report_data as $row): ?>
                                        <tr>
                                            <?php if($report_type=='parcels'): ?>
                                                <td><?= $row['parcel_tracking_number']; ?></td>
                                                <td><?= htmlspecialchars($row['sender_name']); ?></td>
                                                <td><?= htmlspecialchars($row['recipient_name']); ?></td>
                                                <td><?= htmlspecialchars($row['from_branch_name']); ?></td>
                                                <td><?= htmlspecialchars($row['to_branch_name']); ?></td>
                                                <td><span class="badge bg-<?= 
                                                    match($row['status']) {
                                                        'Booked'=>'info',
                                                        'In Transit'=>'warning',
                                                        'Delivered'=>'success',
                                                        'Cancelled'=>'danger',
                                                        default=>'secondary'
                                                    }; 
                                                ?>"><?= $row['status'] ?? 'Pending'; ?></span></td>
                                                <td><?= date('M j, Y', strtotime($row['booking_date'])); ?></td>
                                                <td><?= number_format($row['parcel_price'],2); ?></td>
                                            <?php elseif($report_type=='customers'): ?>
                                                <td><?= htmlspecialchars($row['full_name']); ?></td>
                                                <td><?= htmlspecialchars($row['customer_email']); ?></td>
                                                <td><?= htmlspecialchars($row['customer_phone']); ?></td>
                                            <?php elseif($report_type=='revenue'): ?>
                                                <td><?= date('M j, Y', strtotime($row['booking_date'])); ?></td>
                                                <td><?= htmlspecialchars($row['branch_name']); ?></td>
                                                <td><?= $row['parcel_count']; ?></td>
                                                <td>₹<?= number_format($row['total_revenue'],2); ?></td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i> No data found for the selected criteria.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>
