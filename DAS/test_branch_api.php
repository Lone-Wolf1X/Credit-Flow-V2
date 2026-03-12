<?php
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;

require_once 'config/config.php';

// Test get_branches endpoint
$_GET['action'] = 'get_branches';

ob_start();
include 'modules/api/customer_api.php';
$output = ob_get_clean();

echo "=== Branch API Response ===\n";
echo $output;
