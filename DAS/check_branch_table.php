<?php
require_once 'config/config.php';

echo "=== branch_profiles table structure ===\n";
$result = $conn->query('DESCRIBE branch_profiles');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n=== Sample branch data ===\n";
$result = $conn->query('SELECT * FROM branch_profiles LIMIT 3');
while($row = $result->fetch_assoc()) {
    print_r($row);
}
