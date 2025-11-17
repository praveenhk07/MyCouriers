<?php
// hash_customer_passwords.php
include 'config.php';

// Fetch all customers
$sql = "SELECT customer_id, password FROM customer";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $customer_id = $row['customer_id'];
        $plain_password = $row['password'];

        // Skip if already hashed (starts with $2y$)
        if (substr($plain_password, 0, 4) === '$2y$') {
            continue;
        }

        // Hash the password
        $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

        // Update customer table
        $update = $conn->prepare("UPDATE customer SET password=? WHERE customer_id=?");
        $update->bind_param("si", $hashed_password, $customer_id);
        $update->execute();
    }
    echo "✅ All customer passwords are now hashed!";
} else {
    echo "No customers found.";
}
