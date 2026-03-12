<?php
require_once 'config/config.php';

echo "--- Status Column in customer_profiles ---\n";
// conn is das_db
$res = $conn->query("SHOW COLUMNS FROM customer_profiles LIKE 'status'");
if ($res) {
    if ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
