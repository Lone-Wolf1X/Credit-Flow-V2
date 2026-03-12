<?php
require_once 'config/config.php';
$result = $conn->query("SHOW TABLES");
$tables = [];
while($row = $result->fetch_array()) {
    $tables[] = $row[0];
}
echo "Tables:\n" . implode("\n", $tables) . "\n\n";

$tables_to_check = ['branch_profiles', 'branchsol', 'demopnepal', 'users'];
foreach ($tables_to_check as $table) {
    if (in_array($table, $tables)) {
        $count_res = $conn->query("SELECT COUNT(*) as cnt FROM $table");
        $count = $count_res->fetch_assoc()['cnt'];
        echo "Table: $table | Total Rows: $count\n";
        
        if ($table === 'branchsol') {
            echo "Sample from branchsol:\n";
            $res = $conn->query("SELECT * FROM branchsol LIMIT 10");
            while($r = $res->fetch_assoc()) {
                echo "  " . $r['SOL ID'] . " - " . $r['SOL Detail'] . "\n";
            }
        }
        
        if ($table === 'users') {
            echo "Distinct provinces in users table:\n";
            $res = $conn->query("SELECT DISTINCT province FROM users");
            while($r = $res->fetch_assoc()) {
                echo "  " . $r['province'] . "\n";
            }
        }
        
        if ($table === 'demopnepal') {
            echo "Distinct provinces in demopnepal:\n";
            $res = $conn->query("SELECT DISTINCT province FROM demopnepal");
            while($r = $res->fetch_assoc()) {
                echo "  " . $r['province'] . "\n";
            }
        }
        echo "---------------------------------\n";
    }
}
