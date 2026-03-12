<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'c:/xampp/htdocs/Credit/DAS/config/config.php';

echo "=== WORKFLOW DEEP DIAGNOSTIC ===\n\n";

$profile_id = 5; // Profile for customer 2025005

// 1. Check Profile Status
echo "1. PROFILE STATUS CHECK\n";
$stmt = $conn->prepare("SELECT id, customer_id, full_name, status, created_by FROM customer_profiles WHERE id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
if (!$profile) {
    die("ERROR: Profile $profile_id not found\n");
}
echo "   Profile: {$profile['customer_id']} | {$profile['full_name']} | Status: {$profile['status']}\n";
echo "   Created By: {$profile['created_by']}\n\n";

// 2. Check Loan Details
echo "2. LOAN DETAILS CHECK\n";
$stmt = $conn->prepare("SELECT id, scheme_id, loan_type FROM loan_details WHERE customer_profile_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$loan = $stmt->get_result()->fetch_assoc();
if (!$loan) {
    echo "   WARNING: No loan details found!\n\n";
} else {
    echo "   Loan ID: {$loan['id']} | Type: {$loan['loan_type']} | Scheme ID: {$loan['scheme_id']}\n\n";
}

// 3. Check Templates for Scheme
if ($loan && $loan['scheme_id']) {
    echo "3. TEMPLATE CHECK\n";
    $stmt = $conn->prepare("SELECT id, template_name, file_path FROM templates WHERE scheme_id = ?");
    $stmt->bind_param("i", $loan['scheme_id']);
    $stmt->execute();
    $templates = $stmt->get_result();
    if ($templates->num_rows === 0) {
        echo "   ERROR: No templates found for Scheme ID {$loan['scheme_id']}!\n";
        echo "   This will cause document generation to fail.\n\n";
    } else {
        echo "   Found {$templates->num_rows} template(s):\n";
        while ($tpl = $templates->fetch_assoc()) {
            $path = __DIR__ . "/../" . $tpl['file_path'];
            $exists = file_exists($path) ? "EXISTS" : "MISSING";
            echo "   - ID {$tpl['id']}: {$tpl['template_name']} [$exists]\n";
            echo "     Path: {$tpl['file_path']}\n";
        }
        echo "\n";
    }
}

// 4. Test Submission Logic
echo "4. SIMULATING SUBMISSION\n";
$stmt = $conn->prepare("UPDATE customer_profiles SET status = 'Submitted', submitted_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $profile_id);
if ($stmt->execute()) {
    echo "   SUCCESS: Profile status updated to 'Submitted'\n\n";
} else {
    echo "   ERROR: Failed to update status: " . $conn->error . "\n\n";
}

// 5. Test Approval Logic (without actual document generation)
echo "5. SIMULATING APPROVAL (Status Update Only)\n";
$stmt = $conn->prepare("UPDATE customer_profiles SET status = 'Approved', approved_at = NOW(), approved_by = ? WHERE id = ?");
$user_id = 1; // Admin user
$stmt->bind_param("ii", $user_id, $profile_id);
if ($stmt->execute()) {
    echo "   SUCCESS: Profile status updated to 'Approved'\n\n";
} else {
    echo "   ERROR: Failed to approve: " . $conn->error . "\n\n";
}

// 6. Check for PHP Errors in customer_api.php
echo "6. CHECKING customer_api.php FOR SYNTAX ERRORS\n";
$api_file = 'c:/xampp/htdocs/Credit/DAS/modules/api/customer_api.php';
exec("php -l $api_file 2>&1", $output, $return_code);
if ($return_code === 0) {
    echo "   SUCCESS: No syntax errors in customer_api.php\n\n";
} else {
    echo "   ERROR: Syntax errors found:\n";
    echo "   " . implode("\n   ", $output) . "\n\n";
}

// 7. Check for PHP Errors in document_generation.php
echo "7. CHECKING document_generation.php FOR SYNTAX ERRORS\n";
$doc_file = 'c:/xampp/htdocs/Credit/DAS/includes/document_generation.php';
exec("php -l $doc_file 2>&1", $output, $return_code);
if ($return_code === 0) {
    echo "   SUCCESS: No syntax errors in document_generation.php\n\n";
} else {
    echo "   ERROR: Syntax errors found:\n";
    echo "   " . implode("\n   ", $output) . "\n\n";
}

// 8. Check Apache Error Log for Recent Errors
echo "8. CHECKING APACHE ERROR LOG (Last 20 lines)\n";
$error_log = 'C:/xampp/apache/logs/error.log';
if (file_exists($error_log)) {
    $lines = file($error_log);
    $recent = array_slice($lines, -20);
    foreach ($recent as $line) {
        if (stripos($line, 'DAS') !== false || stripos($line, 'customer_api') !== false) {
            echo "   " . trim($line) . "\n";
        }
    }
    echo "\n";
} else {
    echo "   Error log not found at $error_log\n\n";
}

// 9. Reset Profile Status
echo "9. RESETTING PROFILE TO DRAFT\n";
$stmt = $conn->prepare("UPDATE customer_profiles SET status = 'Draft' WHERE id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
echo "   Profile reset to Draft status\n\n";

echo "=== DIAGNOSTIC COMPLETE ===\n";
?>
