<?php
// reset_admin.php
include 'config.php';

$new_pass = password_hash("password123", PASSWORD_DEFAULT);

$sql = "UPDATE admin SET password = ? WHERE username = 'admin'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $new_pass);

if ($stmt->execute()) {
    echo "✅ Admin password reset to 'password123'";
} else {
    echo "❌ Error: " . $conn->error;
}
