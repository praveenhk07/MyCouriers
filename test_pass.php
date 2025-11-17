<?php
// test_pass.php
$hash = '$2y$10$Auk1YyfiC7wZoFQKQ4OcyebcBl7COXo1I8kR5BpG7Sc7OjIbSq1m6'; // your DB value
$plain = 'password123'; // the password you expect

if (password_verify($plain, $hash)) {
    echo "✅ Password matches hash!";
} else {
    echo "❌ Password does NOT match hash!";
}