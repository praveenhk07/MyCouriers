<?php
require_once '../config.php';

if (isset($_GET['mobile'])) {
    $mobile = $_GET['mobile'];

    $sql = "SELECT first_name, last_name, email, address, city, state, zip, country 
            FROM customer 
            WHERE mobile = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $mobile);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();

    echo json_encode($customer ? $customer : []);
}
?>
