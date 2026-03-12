<?php
require_once '../../config/config.php';
$res = $conn->query("SHOW COLUMNS FROM customers LIKE 'status'");
print_r($res->fetch_assoc());
?>
