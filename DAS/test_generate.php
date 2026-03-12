<?php
// Test approval and document generation directly
require_once 'C:/xampp/htdocs/Credit/DAS/config/config.php';
require_once 'C:/xampp/htdocs/Credit/DAS/includes/document_generation.php';

echo "=== TESTING DOCUMENT GENERATION ===\n\n";

// Simulate approval for profile 1
$profile_id = 1;
$user_id = 3; // Checker user ID

echo "1. Getting profile data...\n";
$profile = fetchProfileData($profile_id);
if (!$profile) {
    die("ERROR: Profile not found!\n");
}
echo "   ✓ Profile found: {$profile['customer_id']} - {$profile['full_name']}\n";
echo "   Status: {$profile['status']}\n\n";

// Get loan details to find scheme
echo "2. Getting loan details...\n";
$loan_result = $conn->query("SELECT * FROM loan_details WHERE customer_profile_id = $profile_id");
$loan = $loan_result->fetch_assoc();
if (!$loan) {
    die("ERROR: No loan details found!\n");
}
echo "   ✓ Loan Type: {$loan['loan_type']}\n";
echo "   Scheme ID: {$loan['scheme_id']}\n\n";

// Get templates for this scheme
echo "3. Finding templates for scheme {$loan['scheme_id']}...\n";
$template_result = $conn->query("SELECT * FROM templates WHERE scheme_id = {$loan['scheme_id']}");
$templates = [];
while ($t = $template_result->fetch_assoc()) {
    $templates[] = $t;
    echo "   ✓ Template ID {$t['id']}: {$t['template_name']}\n";
    echo "     Path: {$t['template_folder_path']}\n";
}

if (empty($templates)) {
    die("ERROR: No templates found for this scheme!\n");
}
echo "\n";

// Try to generate documents
echo "4. Generating documents...\n";
$generated_count = 0;
$errors = [];

foreach ($templates as $template) {
    echo "   Generating from template: {$template['template_name']}...\n";
    
    $result = generateDocument($profile_id, $template['id']);
    
    if ($result['success']) {
        $generated_count++;
        echo "   ✓ SUCCESS! Document generated\n";
        echo "     File: {$result['filename']}\n";
        echo "     Path: {$result['file_path']}\n";
        echo "     Document ID: {$result['document_id']}\n";
    } else {
        $errors[] = $result['error'];
        echo "   ✗ FAILED: {$result['error']}\n";
    }
    echo "\n";
}

echo "5. SUMMARY:\n";
echo "   Documents generated: $generated_count\n";
if (!empty($errors)) {
    echo "   Errors:\n";
    foreach ($errors as $error) {
        echo "     - $error\n";
    }
}

// Check database
echo "\n6. Checking database for generated documents...\n";
$doc_result = $conn->query("SELECT * FROM profile_documents WHERE customer_profile_id = $profile_id");
$db_count = 0;
while ($doc = $doc_result->fetch_assoc()) {
    $db_count++;
    echo "   ✓ Document ID {$doc['id']}: {$doc['document_name']}\n";
    echo "     Path: {$doc['file_path']}\n";
    
    // Check if file exists
    $full_path = "C:/xampp/htdocs/Credit/DAS/" . $doc['file_path'];
    if (file_exists($full_path)) {
        echo "     ✓ File exists (" . filesize($full_path) . " bytes)\n";
    } else {
        echo "     ✗ File NOT found at: $full_path\n";
    }
}
echo "   Total in database: $db_count\n";

echo "\n=== TEST COMPLETE ===\n";
?>
