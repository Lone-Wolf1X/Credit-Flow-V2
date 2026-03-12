<?php
require_once 'config/config.php';
$conn = new mysqli('localhost', 'root', '', 'das_db');

$result = $conn->query("SHOW COLUMNS FROM customers");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Default'] . "\n";
}
