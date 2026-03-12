<?php
require_once 'config/config.php';

$dbs = ['credit_flow_db', 'edms_db'];
foreach ($dbs as $db) {
    echo "--- Tables in $db ---\n";
    $res = $conn->query("SHOW TABLES FROM $db");
    if ($res) {
        while ($row = $res->fetch_array()) {
            echo "  - " . $row[0] . "\n";
        }
    } else {
        echo "  - Error: " . $conn->error . "\n";
    }
}
?>
