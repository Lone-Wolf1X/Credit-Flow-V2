<?php
require_once 'config/config.php';

echo "--- Columns in customer_profiles ---\n";
// conn is das_db
$res = $conn->query("DESCRIBE customer_profiles");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
