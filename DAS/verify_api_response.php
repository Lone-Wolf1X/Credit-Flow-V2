<?php
// Mock session and parameters
session_start();
$_SESSION['logged_in'] = true;
$_POST['action'] = 'get_generated_documents';
$_POST['profile_id'] = 2; // Testing with profile 2 which we know has documents
$_POST['latest_only'] = 'true';

// Buffer output to catch potential leakage
ob_start();
require_once 'modules/api/document_generation_api.php';
$output = ob_get_clean();

echo "API Response:\n";
echo $output;
?>
