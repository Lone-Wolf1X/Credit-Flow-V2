<?php
// require_once __DIR__ . '/../../config/config.php';

$conn = new mysqli('localhost', 'root', '', 'das_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("DESCRIBE guarantors");

if ($result) {
    echo "Columns in 'guarantors' table:\n";
    echo str_pad("Field", 30) . str_pad("Type", 20) . "\n";
    echo str_repeat("-", 50) . "\n";
    while ($row = $result->fetch_assoc()) {
        echo str_pad($row['Field'], 30) . str_pad($row['Type'], 20) . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}
