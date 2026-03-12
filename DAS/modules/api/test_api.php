<?php
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;

// We are in modules/api/
// customer_api.php is in the same directory

echo "=== Testing get_provinces ===\n";
$_GET['action'] = 'get_provinces';
ob_start();
include 'customer_api.php';
$output = ob_get_clean();
// The API has a lot of PHP overhead, find the JSON portion
$json_start = strpos($output, '{"success"');
if ($json_start !== false) {
    $json = substr($output, $json_start);
    $data = json_decode($json, true);
    echo "Success: " . ($data['success'] ? 'Yes' : 'No') . "\n";
    echo "Count: " . count($data['data']) . "\n";
    if (!empty($data['data'])) {
        echo "Example: " . $data['data'][0]['name'] . "\n";
    }
} else {
    echo "Failed to find JSON in output:\n" . substr($output, 0, 500) . "...\n";
}

echo "\n=== Testing get_branches ===\n";
$_GET['action'] = 'get_branches';
ob_start();
// Since customer_api.php uses functions, we can just call them now that it's included
if (function_exists('getBranches')) {
    getBranches();
}
$output = ob_get_clean();
$json_start = strpos($output, '{"success"');
if ($json_start !== false) {
    $json = substr($output, $json_start);
    $data = json_decode($json, true);
    echo "Success: " . ($data['success'] ? 'Yes' : 'No') . "\n";
    echo "Count: " . count($data['data']) . "\n";
    if (!empty($data['data'])) {
        echo "Example: " . $data['data'][0]['sol_id'] . " - " . $data['data'][0]['sol_name'] . "\n";
    }
} else {
    echo "Failed to find JSON in output.\n";
}
?>
