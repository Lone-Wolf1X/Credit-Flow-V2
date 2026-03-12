<?php
require_once 'config/config.php';

echo "--- Checking demopnepal province values ---\n";
// Temporarily using $conn which is das_db
$res = $conn->query("SELECT DISTINCT province FROM demopnepal LIMIT 10");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "Province Value: [" . $row['province'] . "]\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
