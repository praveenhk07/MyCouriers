<?php
// hash_staff_passwords.php
include 'config.php';

// Fetch all staff
$sql = "SELECT staff_id, password FROM staff";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $staff_id = $row['staff_id'];
        $plain_password = $row['password'];

        // Skip if already hashed (starts with $2y$)
        if (substr($plain_password, 0, 4) === '$2y$') {
            continue;
        }

        // Hash the password
        $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

        // Update staff table
        $update = $conn->prepare("UPDATE staff SET password=? WHERE staff_id=?");
        $update->bind_param("si", $hashed_password, $staff_id);
        $update->execute();
    }
    echo "✅ All staff passwords are now hashed!";
} else {
    echo "No staff found.";
}
