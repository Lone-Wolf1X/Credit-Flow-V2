<?php
require_once 'config/config.php';

echo "--- Promising Tables ---\n";
$promising = ['branchsol', 'demopnepal', 'branch_profiles', 'users'];
foreach ($promising as $table) {
    echo "Table: $table\n";
    $res = $conn->query("SELECT COUNT(*) FROM $table");
    if ($res) {
        $count = $res->fetch_row()[0];
        echo "  - Row count: $count\n";
    } else {
        echo "  - Error getting count: " . $conn->error . "\n";
    }
}

echo "\n--- Sample: branchsol (SOL IDs and Names) ---\n";
$res = $conn->query("SELECT * FROM branchsol LIMIT 20");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
}

echo "\n--- Distinct Provinces: demopnepal ---\n";
$res = $conn->query("SELECT DISTINCT province FROM demopnepal");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "Province: " . $row['province'] . "\n";
    }
}

echo "\n--- Sample: branch_profiles ---\n";
$res = $conn->query("SELECT * FROM branch_profiles LIMIT 10");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
}
?>
