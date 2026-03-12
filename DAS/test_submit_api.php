<?php
// Direct API Test - Simulate Submission
error_reporting(E_ALL);
ini_set('display_errors', 1);

$_SESSION['user_id'] = 1;
$_SESSION['logged_in'] = true;
$_SESSION['role_name'] = 'Maker';

$_POST['action'] = 'submit_profile';
$_POST['profile_id'] = 5;
$_POST['remarks'] = 'Test submission from diagnostic script';

echo "=== TESTING SUBMIT PROFILE API ===\n\n";

ob_start();
require_once 'c:/xampp/htdocs/Credit/DAS/modules/api/customer_api.php';
$output = ob_get_clean();

echo "API Output:\n";
echo $output . "\n";

if (json_decode($output)) {
    echo "\nJSON Valid: YES\n";
    $data = json_decode($output, true);
    print_r($data);
} else {
    echo "\nJSON Valid: NO\n";
    echo "Raw output contains non-JSON data (likely PHP errors/warnings)\n";
}
?>
