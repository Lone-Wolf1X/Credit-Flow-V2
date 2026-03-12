<?php
require_once 'config/config.php';
$conn = new mysqli('localhost', 'root', '', 'das_db');

echo "=== CUSTOMERS TABLE SCHEMA ===\n";
$result = $conn->query("DESCRIBE customers");
if ($result) {
    while($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Table 'customers' does not exist or error: " . $conn->error . "\n";
}

echo "\n=== CHECKING IF 2025002 EXISTS IN CUSTOMERS ===\n";
if ($result) {
    // Try searching as ID
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $id = 2025002;
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) echo "Found by ID (2025002)\n";
    else echo "Not found by ID (2025002)\n";

    // Try searching by some code column if it exists (guessing 'customer_id' or 'code')
    // We'll see schema first.
}
