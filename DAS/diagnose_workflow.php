<?php
/**
 * DAS Profile Submission & Approval Diagnostic Tool
 * This script checks for common issues in the workflow
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to database
$conn = new mysqli('localhost', 'root', '', 'das_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== DAS PROFILE SUBMISSION & APPROVAL DIAGNOSTIC ===\n\n";

// 1. Check customer_profiles table structure
echo "1. Checking customer_profiles table structure...\n";
$result = $conn->query("DESCRIBE customer_profiles");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}
echo "   Columns: " . implode(", ", $columns) . "\n";
echo "   ✓ Table exists\n\n";

// 2. Check for profiles
echo "2. Checking existing profiles...\n";
$result = $conn->query("SELECT id, customer_id, full_name, status, submitted_at, approved_at FROM customer_profiles ORDER BY id DESC LIMIT 5");
$profiles = [];
while ($row = $result->fetch_assoc()) {
    $profiles[] = $row;
    echo "   Profile #{$row['id']}: {$row['customer_id']} - {$row['full_name']} | Status: {$row['status']}\n";
    echo "      Submitted: " . ($row['submitted_at'] ?? 'NULL') . " | Approved: " . ($row['approved_at'] ?? 'NULL') . "\n";
}
if (count($profiles) == 0) {
    echo "   ⚠ No profiles found\n";
}
echo "\n";

// 3. Check for loan_details (required for approval)
echo "3. Checking loan_details...\n";
$result = $conn->query("SELECT customer_profile_id, scheme_id FROM loan_details");
$loan_count = $result->num_rows;
echo "   Total loan details: $loan_count\n";
if ($loan_count > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "   Profile #{$row['customer_profile_id']} → Scheme ID: {$row['scheme_id']}\n";
    }
} else {
    echo "   ⚠ No loan details found - approval will fail!\n";
}
echo "\n";

// 4. Check for templates
echo "4. Checking templates...\n";
$result = $conn->query("SELECT id, template_name, scheme_id FROM templates LIMIT 10");
$template_count = $result->num_rows;
echo "   Total templates: $template_count\n";
if ($template_count > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "   Template #{$row['id']}: {$row['template_name']} (Scheme: {$row['scheme_id']})\n";
    }
} else {
    echo "   ⚠ No templates found - document generation will fail!\n";
}
echo "\n";

// 5. Check for stored procedures
echo "5. Checking stored procedures...\n";
$procedures = ['sp_generate_customer_profile_id', 'sp_log_audit', 'sp_approve_profile'];
foreach ($procedures as $proc) {
    $result = $conn->query("SHOW PROCEDURE STATUS WHERE Db = 'das_db' AND Name = '$proc'");
    if ($result->num_rows > 0) {
        echo "   ✓ $proc exists\n";
    } else {
        echo "   ✗ $proc NOT FOUND\n";
    }
}
echo "\n";

// 6. Check profile_comments table
echo "6. Checking profile_comments table...\n";
$result = $conn->query("SHOW TABLES LIKE 'profile_comments'");
if ($result->num_rows > 0) {
    echo "   ✓ profile_comments table exists\n";
    $result = $conn->query("SELECT COUNT(*) as count FROM profile_comments");
    $count = $result->fetch_assoc()['count'];
    echo "   Total comments: $count\n";
} else {
    echo "   ✗ profile_comments table NOT FOUND\n";
}
echo "\n";

// 7. Test submission logic
echo "7. Testing submission logic (DRY RUN)...\n";
if (count($profiles) > 0) {
    $test_profile = $profiles[0];
    $profile_id = $test_profile['id'];
    echo "   Testing with Profile #{$profile_id}\n";
    
    // Check if profile can be submitted
    if ($test_profile['status'] == 'Draft') {
        echo "   ✓ Profile is in Draft status - can be submitted\n";
    } else {
        echo "   ⚠ Profile status is '{$test_profile['status']}' - may not be submittable\n";
    }
    
    // Check if profile has required data
    $result = $conn->query("SELECT COUNT(*) as count FROM borrowers WHERE customer_profile_id = $profile_id");
    $borrower_count = $result->fetch_assoc()['count'];
    echo "   Borrowers: $borrower_count\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM loan_details WHERE customer_profile_id = $profile_id");
    $loan_count = $result->fetch_assoc()['count'];
    echo "   Loan details: $loan_count\n";
    
    if ($borrower_count > 0 && $loan_count > 0) {
        echo "   ✓ Profile has minimum required data\n";
    } else {
        echo "   ⚠ Profile missing required data (borrowers or loan details)\n";
    }
} else {
    echo "   ⚠ No profiles to test\n";
}
echo "\n";

// 8. Check for common errors
echo "8. Checking for common issues...\n";

// Check if document_generation.php exists
$doc_gen_path = __DIR__ . '/includes/document_generation.php';
if (file_exists($doc_gen_path)) {
    echo "   ✓ document_generation.php exists\n";
} else {
    echo "   ✗ document_generation.php NOT FOUND at $doc_gen_path\n";
}

// Check if comment_functions.php exists
$comment_func_path = __DIR__ . '/includes/comment_functions.php';
if (file_exists($comment_func_path)) {
    echo "   ✓ comment_functions.php exists\n";
} else {
    echo "   ✗ comment_functions.php NOT FOUND at $comment_func_path\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
echo "\nSUMMARY:\n";
echo "- Profiles found: " . count($profiles) . "\n";
echo "- Loan details: $loan_count\n";
echo "- Templates: $template_count\n";
echo "\nRECOMMENDATIONS:\n";

if ($loan_count == 0) {
    echo "⚠ CREATE loan details for profiles before approval\n";
}
if ($template_count == 0) {
    echo "⚠ CREATE templates for document generation\n";
}
if (count($profiles) == 0) {
    echo "⚠ CREATE test profiles to verify workflow\n";
}

$conn->close();
?>
