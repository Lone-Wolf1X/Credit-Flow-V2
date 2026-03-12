<?php
// Test loan save API directly
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;

$_POST = [
    'action' => 'save_loan',
    'customer_profile_id' => '2',
    'loan_type' => 'New',
    'loan_approved_date' => '2080-01-01',
    'approval_ref_no' => 'TEST/001',
    'scheme_id' => '1',
    'loan_purpose' => 'Test purpose',
    'remarks' => ''
];

// Capture the output
ob_start();
include 'modules/api/customer_api.php';
$output = ob_get_clean();

// Show raw output
echo "RAW OUTPUT:\n";
echo bin2hex(substr($output, 0, 100)) . "\n\n";
echo "OUTPUT:\n";
echo $output;
