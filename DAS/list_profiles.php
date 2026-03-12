<?php
require_once 'config/config.php';
$res = $conn->query("SELECT id, full_name, status FROM customer_profiles");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['full_name'] . " | Status: " . $row['status'] . "\n";
}
?>
