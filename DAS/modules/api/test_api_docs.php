<?php
// Simulate API call for get_generated_documents
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;

$_GET['action'] = 'get_generated_documents';
$_GET['profile_id'] = 2; // confirmed profile ID for 2025002

// Capture output
ob_start();
require 'customer_api.php';
$output = ob_get_clean();

echo "=== RAW OUTPUT START ===\n";
echo $output;
echo "\n=== RAW OUTPUT END ===\n";

$json = json_decode($output, true);
if ($json) {
    echo "Success: " . ($json['success'] ? 'YES' : 'NO') . "\n";
    if (isset($json['data'])) {
        echo "Data Count: " . count($json['data']) . "\n";
        print_r($json['data']);
    }
} else {
    echo "Failed to decode JSON.\n";
}
