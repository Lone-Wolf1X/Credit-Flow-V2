<?php
require_once 'config/config.php';

echo "--- Tables in das_db ---\n";
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_array()) {
    $table = $row[0];
    echo "Table: $table\n";
    $columns = $conn->query("SHOW COLUMNS FROM $table");
    while ($col = $columns->fetch_assoc()) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
}

echo "\n--- Data Sample: branchsol ---\n";
$res = $conn->query("SELECT * FROM branchsol LIMIT 10");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n--- Data Sample: demopnepal ---\n";
$res = $conn->query("SELECT DISTINCT province FROM demopnepal LIMIT 20");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
