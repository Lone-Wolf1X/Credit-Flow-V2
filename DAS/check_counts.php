<?php
require_once 'config/config.php';
$profile_id = 2;
$conn = new mysqli('localhost', 'root', '', 'das_db');

$res = $conn->query("SELECT COUNT(*) as c FROM borrowers WHERE customer_profile_id=$profile_id");
echo "Borrowers: " . $res->fetch_assoc()['c'] . "\n";

$res = $conn->query("SELECT COUNT(*) as c FROM guarantors WHERE customer_profile_id=$profile_id");
echo "Guarantors: " . $res->fetch_assoc()['c'] . "\n";
