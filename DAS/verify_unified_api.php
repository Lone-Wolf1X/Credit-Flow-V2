<?php
// Mock session and parameters
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['id'] = 1; // Mock user id
$_POST['action'] = 'get_generated_documents';
$_POST['profile_id'] = 1; // Rameshwar Prasad Sah
$_POST['latest_only'] = 'true';

// Buffer output to catch potential leakage
ob_start();
require_once 'modules/api/document_generation_api.php';
$output = ob_get_clean();

echo "API Response for Profile ID 1:\n";
echo $output . "\n";
?>
