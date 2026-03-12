<?php
// Debug script to check database tables and schema
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli('localhost', 'root', '', 'das_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== Tables in das_db ===\n";
$result = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
    if (strpos($row[0], 'demo') !== false || strpos($row[0], 'nepal') !== false) {
        echo "[MATCH] " . $row[0] . "\n";
    }
}

$target_table = 'demopnepal';
if (in_array($target_table, $tables)) {
    echo "\n=== Schema for $target_table ===\n";
    $result = $conn->query("DESCRIBE $target_table");
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }

    echo "\n=== Data Sample (First 1) ===\n";
    $result = $conn->query("SELECT * FROM $target_table LIMIT 1");
    print_r($result->fetch_assoc());

    echo "\n=== Row Count ===\n";
    $result = $conn->query("SELECT COUNT(*) as count FROM $target_table");
    $row = $result->fetch_assoc();
    echo "Total Rows: " . $row['count'] . "\n";
    
    echo "\n=== Distinct Provinces ===\n";
    $result = $conn->query("SELECT DISTINCT province FROM $target_table LIMIT 10");
    while ($row = $result->fetch_assoc()) {
        echo $row['province'] . "\n";
    }

} else {
    echo "\n[ERROR] Table '$target_table' NOT FOUND!\n";
    echo "Did you mean one of these?\n";
    print_r($tables);
}

$conn->close();
?>
