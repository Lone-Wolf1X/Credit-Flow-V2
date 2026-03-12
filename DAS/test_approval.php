<?php
/**
 * Test Approval Script for Profile #5
 * This will attempt to approve the profile and show detailed errors
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start session
session_start();

// Set session variables (simulating logged-in checker)
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'Checker';

// Set POST data
$_POST['action'] = 'approve_profile';
$_POST['profile_id'] = 5;
$_POST['remarks'] = 'Test approval from diagnostic script';

echo "=== TESTING PROFILE APPROVAL ===\n\n";
echo "Profile ID: 5\n";
echo "User ID: 1\n";
echo "Action: approve_profile\n\n";

echo "--- Starting API Call ---\n\n";

// Capture output
ob_start();

try {
    require_once __DIR__ . '/modules/api/customer_api.php';
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

$output = ob_get_clean();

echo "--- API Output ---\n";
echo $output . "\n\n";

// Try to parse as JSON
echo "--- JSON Parsing ---\n";
$json = json_decode($output, true);
if ($json) {
    echo "✓ Valid JSON\n";
    print_r($json);
} else {
    echo "✗ Invalid JSON or contains non-JSON output\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
}

echo "\n--- Checking Database ---\n";
$conn = new mysqli('localhost', 'root', '', 'das_db');
$result = $conn->query("SELECT id, status, approved_by, approved_at FROM customer_profiles WHERE id = 5");
$profile = $result->fetch_assoc();
echo "Profile #5 Status: " . $profile['status'] . "\n";
echo "Approved By: " . ($profile['approved_by'] ?? 'NULL') . "\n";
echo "Approved At: " . ($profile['approved_at'] ?? 'NULL') . "\n";

echo "\n--- Checking Generated Documents ---\n";
$result = $conn->query("SELECT id, template_id, file_path, created_at FROM generated_documents WHERE profile_id = 5");
if ($result->num_rows > 0) {
    while ($doc = $result->fetch_assoc()) {
        echo "Document #{$doc['id']}: Template {$doc['template_id']} - {$doc['file_path']}\n";
    }
} else {
    echo "No documents generated\n";
}

echo "\n--- Checking Logs ---\n";
if (file_exists(__DIR__ . '/debug_approval.log')) {
    echo "Last 5 lines of debug_approval.log:\n";
    $lines = file(__DIR__ . '/debug_approval.log');
    $last_lines = array_slice($lines, -5);
    foreach ($last_lines as $line) {
        echo $line;
    }
}

$conn->close();
?>
