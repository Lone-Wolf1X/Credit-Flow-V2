<?php
require_once 'config/config.php';

echo "--- Tables in das_db ---\n";
$res = $conn->query("SHOW TABLES");
if ($res) {
    while ($row = $res->fetch_array()) {
        echo $row[0] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
