<?php
// Simulate an AJAX request for generated documents
$_POST['action'] = 'get_generated_documents';
$_POST['profile_id'] = 1;
$_POST['latest_only'] = 'true';

// Mock session
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;

require_once 'modules/api/document_generation_api.php';
?>
