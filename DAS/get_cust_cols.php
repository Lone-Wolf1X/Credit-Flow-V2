<?php
require_once 'config/config.php';
$conn = new mysqli('localhost', 'root', '', 'das_db');
$result = $conn->query("SHOW COLUMNS FROM customers");
$count = 0;
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
    $count++;
    if ($count > 15) break; 
}
