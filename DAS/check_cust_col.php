<?php
require_once 'config/config.php';
$conn = new mysqli('localhost', 'root', '', 'das_db');

echo "=== CHECKING FOR customer_id column in customers ===\n";
$result = $conn->query("SHOW COLUMNS FROM customers LIKE 'customer_id'");
if($result->num_rows > 0) {
    echo "Column exists:\n";
    print_r($result->fetch_assoc());
} else {
    echo "Column customer_id DOES NOT EXIST in customers table.\n";
}
