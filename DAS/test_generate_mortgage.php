<?php
/**
 * Test Script for Mortgage Deed Generation with Paragraph Components
 * Usage: php test_generate_mortgage.php
 */


require_once __DIR__ . '/includes/DocumentGenerator.php';

// Database Connection
$das_conn = new mysqli('localhost', 'root', '', 'das_db');
if ($das_conn->connect_error) {
    die("Connection failed: " . $das_conn->connect_error);
}
$das_conn->set_charset("utf8mb4");

// Configuration
$customerProfileId = 2026001; // As requested
$templatePath = __DIR__ . '/templates/Individual/mortgage_deed_test.docx'; // Likely path

// Verify template exists
if (!file_exists($templatePath)) {
    // Try fallback search
    $possiblePaths = [
        __DIR__ . '/templates/Individual/mortgage_deed.docx',
        __DIR__ . '/templates/ptl/mortgage_deed.docx', 
        __DIR__ . '/../Templates/Mortgage Deed.docx'
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $templatePath = $path;
            break;
        }
    }
}

if (!file_exists($templatePath)) {
    die("Error: Template not found at $templatePath or fallbacks.\n");
}

echo "Using Template: $templatePath\n";
echo "Profile ID: $customerProfileId\n";

// Initialize Generator
$generator = new DocumentGenerator($das_conn);

// Check variables (DEBUG)
$tempTemplate = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
$vars = $tempTemplate->getVariables();
echo "--- Template Variables Found ---\n";
print_r($vars);
echo "--------------------------------\n";

// Generate Document
echo "Generating document...\n";
$result = $generator->generateDocument($templatePath, $customerProfileId, 'mortgage_deed');

if ($result['success']) {
    echo "SUCCESS!\n";
    echo "Output File: " . $result['output_path'] . "\n";
    echo "Relative Path: " . $result['relative_path'] . "\n";
    echo "Folder: " . $result['folder_name'] . "\n";
    
    // Copy to test_generated for easy access as requested
    $testDir = __DIR__ . '/test_generated/';
    if (!file_exists($testDir)) mkdir($testDir, 0777, true);
    
    $testFile = $testDir . 'Test_Mortgage_2026001.docx';
    copy($result['output_path'], $testFile);
    
    echo "------------------------------------------------\n";
    echo "Saved user-friendly copy to: \n$testFile\n";
    echo "------------------------------------------------\n";
    
} else {
    echo "FAILED.\n";
    echo "Error: " . $result['error'] . "\n";
    if (isset($result['trace'])) {
        echo "Trace:\n" . $result['trace'] . "\n";
    }
}
