<?php
// Simulate web request to customer_api.php
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 3; // Checker ID
$_SESSION['username'] = 'Checker';
$_SESSION['role'] = 'checker';

// Set up Request
$_POST['action'] = 'approve_profile';
$_POST['profile_id'] = 1;

// Capture output
ob_start();
require 'customer_api.php';
$output = ob_get_clean();

echo "=== API OUTPUT ===\n";
echo $output . "\n\n";

echo "=== APPROVAL LOG ===\n";
echo @file_get_contents('../../debug_approval.log') . "\n\n";

echo "=== DOC GEN LOG ===\n";
echo @file_get_contents('../../debug_doc_gen.log') . "\n";
?>
