<?php
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;

require_once 'config/config.php';

echo "=== Testing get_provinces ===\n";
$_GET['action'] = 'get_provinces';
ob_start();
include 'modules/api/customer_api.php';
$output = ob_get_clean();
$data = json_decode($output, true);
echo "Success: " . ($data['success'] ? 'Yes' : 'No') . "\n";
echo "Count: " . count($data['data']) . "\n";
if (!empty($data['data'])) {
    echo "First 2:\n";
    print_r(array_slice($data['data'], 0, 2));
}

echo "\n=== Testing get_branches ===\n";
// Clear previous includes by calling the functions directly if possible, or use a fresh request.
// Since customer_api.php defines functions, we can just call them if we've included it.
if (!function_exists('getBranches')) {
    include_once 'modules/api/customer_api.php';
}

ob_start();
getBranches();
$output = ob_get_clean();
$data = json_decode($output, true);
echo "Success: " . ($data['success'] ? 'Yes' : 'No') . "\n";
echo "Count: " . count($data['data']) . "\n";
if (!empty($data['data'])) {
    echo "First 2:\n";
    print_r(array_slice($data['data'], 0, 2));
}
?>
