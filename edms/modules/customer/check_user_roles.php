<?php
require_once '../../config/config.php';

// Connect to Credit Flow DB (where users are)
// config.php usually connects to EDMS, check if it has CF conn
// dashboard.php had $cf_conn. Let's assume config.php might only have $conn (EDMS).
// Let's explicitly connect to credit_flow_db to be safe, or check if $cf_conn exists.

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'credit_flow_db';

$cf_conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($cf_conn->connect_error) {
    die("Connection failed: " . $cf_conn->connect_error);
}

echo "Connected to Credit Flow DB\n";
echo str_repeat("-", 40) . "\n";
echo sprintf("%-10s | %-20s | %-15s\n", "Staff ID", "Full Name", "Role");
echo str_repeat("-", 40) . "\n";

$result = $cf_conn->query("SELECT staff_id, full_name, role FROM users");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-10s | %-20s | %-15s\n", 
            $row['staff_id'], 
            substr($row['full_name'], 0, 20), 
            $row['role']
        );
    }
} else {
    echo "Error: " . $cf_conn->error;
}

echo str_repeat("-", 40) . "\n";
?>
