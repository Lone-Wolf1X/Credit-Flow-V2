<?php
require_once 'config.php';
// We need to connect to das_db
$conn = new mysqli('localhost', 'root', '', 'das_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to print table columns
function printTableSchema($tableName, $conn) {
    echo "\nTABLE: $tableName\n";
    $result = $conn->query("DESCRIBE $tableName");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo " - " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}

printTableSchema('collateral_details', $conn);
printTableSchema('guarantor_details', $conn);
printTableSchema('customer_profiles', $conn);
