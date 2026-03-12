<?php
require_once 'config/config.php';
$conn = new mysqli('localhost', 'root', '', 'das_db');

echo "=== USERS ===\n";
$result = $conn->query("SELECT * FROM users LIMIT 1");
print_r($result->fetch_assoc());

echo "\n=== CUSTOMERS TABLE DEFINITION ===\n";
$result = $conn->query("SHOW CREATE TABLE customers");
$row = $result->fetch_row();
echo $row[1];
