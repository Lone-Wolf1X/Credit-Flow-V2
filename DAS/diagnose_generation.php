<?php
// Comprehensive diagnostic script for document generation
require_once 'C:/xampp/htdocs/Credit/DAS/config/config.php';

echo "=== DOCUMENT GENERATION DIAGNOSTIC ===\n\n";

// 1. Check templates in database
echo "1. TEMPLATES IN DATABASE:\n";
$result = $conn->query("SELECT id, template_name, template_folder_path, scheme_id FROM templates ORDER BY id");
while ($row = $result->fetch_assoc()) {
    echo "  Template ID: {$row['id']}\n";
    echo "  Name: {$row['template_name']}\n";
    echo "  Scheme ID: {$row['scheme_id']}\n";
    echo "  Path: {$row['template_folder_path']}\n";
    
    // Check if file exists
    if ($row['template_folder_path']) {
        $full_path = "C:/xampp/htdocs/Credit/DAS/" . $row['template_folder_path'];
        if (file_exists($full_path)) {
            echo "  ✓ File EXISTS at: $full_path\n";
            echo "  File size: " . filesize($full_path) . " bytes\n";
        } else {
            echo "  ✗ File NOT FOUND at: $full_path\n";
        }
    } else {
        echo "  ✗ No file path set!\n";
    }
    echo "\n";
}

// 2. Check loan schemes
echo "2. LOAN SCHEMES:\n";
$result = $conn->query("SELECT id, scheme_name, scheme_code FROM loan_schemes ORDER BY id");
while ($row = $result->fetch_assoc()) {
    echo "  Scheme ID: {$row['id']} - {$row['scheme_name']} ({$row['scheme_code']})\n";
}
echo "\n";

// 3. Check profile 1 details
echo "3. PROFILE 1 DETAILS:\n";
$result = $conn->query("SELECT * FROM customer_profiles WHERE id = 1");
if ($profile = $result->fetch_assoc()) {
    echo "  Customer ID: {$profile['customer_id']}\n";
    echo "  Name: {$profile['full_name']}\n";
    echo "  Status: {$profile['status']}\n";
    echo "  Created by: {$profile['created_by']}\n";
    echo "  Approved by: " . ($profile['approved_by'] ?? 'NULL') . "\n";
} else {
    echo "  ✗ Profile 1 not found!\n";
}
echo "\n";

// 4. Check loan details for profile 1
echo "4. LOAN DETAILS FOR PROFILE 1:\n";
$result = $conn->query("SELECT * FROM loan_details WHERE customer_profile_id = 1");
if ($loan = $result->fetch_assoc()) {
    echo "  Loan Type: {$loan['loan_type']}\n";
    echo "  Scheme ID: " . ($loan['scheme_id'] ?? 'NULL') . "\n";
    
    // Find matching templates
    if (isset($loan['scheme_id'])) {
        $scheme_id = $loan['scheme_id'];
        $templates = $conn->query("SELECT id, template_name FROM templates WHERE scheme_id = $scheme_id");
        echo "  Templates for this scheme:\n";
        while ($t = $templates->fetch_assoc()) {
            echo "    - Template ID {$t['id']}: {$t['template_name']}\n";
        }
    }
} else {
    echo "  ✗ No loan details found for profile 1!\n";
}
echo "\n";

// 5. Check generated documents
echo "5. GENERATED DOCUMENTS FOR PROFILE 1:\n";
$result = $conn->query("SELECT * FROM profile_documents WHERE customer_profile_id = 1");
$count = 0;
while ($doc = $result->fetch_assoc()) {
    $count++;
    echo "  Document ID: {$doc['id']}\n";
    echo "  Name: {$doc['document_name']}\n";
    echo "  Path: {$doc['file_path']}\n";
    echo "  Generated at: {$doc['generated_at']}\n";
    echo "\n";
}
if ($count == 0) {
    echo "  ✗ No documents generated yet\n";
}
echo "\n";

// 6. Test fetchProfileData function
echo "6. TESTING fetchProfileData():\n";
require_once 'C:/xampp/htdocs/Credit/DAS/includes/document_generation.php';
$profile_data = fetchProfileData(1);
if ($profile_data) {
    echo "  ✓ fetchProfileData() works\n";
    echo "  Customer ID: {$profile_data['customer_id']}\n";
    echo "  Status: {$profile_data['status']}\n";
} else {
    echo "  ✗ fetchProfileData() returned NULL\n";
}
echo "\n";

// 7. Check if PHPWord is available
echo "7. CHECKING PHPWORD:\n";
if (class_exists('PhpOffice\PhpWord\TemplateProcessor')) {
    echo "  ✓ PHPWord TemplateProcessor class is available\n";
} else {
    echo "  ✗ PHPWord TemplateProcessor class NOT found\n";
    echo "  Run: composer require phpoffice/phpword\n";
}
echo "\n";

// 8. Check generated directory
echo "8. CHECKING OUTPUT DIRECTORY:\n";
$gen_dir = "C:/xampp/htdocs/Credit/DAS/generated";
if (is_dir($gen_dir)) {
    echo "  ✓ Generated directory exists\n";
    if (is_writable($gen_dir)) {
        echo "  ✓ Directory is writable\n";
    } else {
        echo "  ✗ Directory is NOT writable\n";
    }
} else {
    echo "  ✗ Generated directory does NOT exist\n";
    echo "  Creating directory...\n";
    mkdir($gen_dir, 0777, true);
}

echo "\n=== END OF DIAGNOSTIC ===\n";
?>
