<?php
require_once 'config/config.php';

echo "--- system_settings ---\n";
$res = $conn->query("SELECT * FROM system_settings");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
}

echo "\n--- demopnepal Columns ---\n";
$res = $conn->query("SHOW COLUMNS FROM demopnepal");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}

echo "\n--- users Table Provinces ---\n";
$res = $conn->query("SELECT DISTINCT province FROM users");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "Province: " . $row['province'] . "\n";
    }
}
?>
