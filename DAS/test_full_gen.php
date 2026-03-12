<?php
// Test Script for Smart Generation with HTML Injection
require_once __DIR__ . '/includes/SmartDocumentGenerator.php';

// Setup DB
$conn = new mysqli('localhost', 'root', '', 'das_db');

// update sample component to use 'collateral' instead of 'collaterals' if needed
// Checking Fetcher key: usually it is 'collateral'
// Let's update the component to be sure
$conn->query("UPDATE document_components SET html_content = REPLACE(html_content, '{{#collaterals}}', '{{#collateral}}') WHERE code = 'COLLATERAL_TABLE'");
$conn->query("UPDATE document_components SET html_content = REPLACE(html_content, '{{/collaterals}}', '{{/collateral}}') WHERE code = 'COLLATERAL_TABLE'");


$generator = new SmartDocumentGenerator($conn);

// Profile ID 1 (Assumed to exist)
$profile_id = 1;

// Create a dummy template with the placeholder
$phpWord = new \PhpOffice\PhpWord\PhpWord();
$section = $phpWord->addSection();
$section->addText("Test Document with HTML Injection");
$section->addText('${COLLATERAL_TABLE}');
$section->addText("End of Document");
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$templatePath = __DIR__ . '/mixed_template_test.docx';
$objWriter->save($templatePath);

// Register this template in DB as a test one? 
// SmartDocumentGenerator fetches template from DB. 
// We can assume we pass a template_id or mock the selection.
// SmartGenerator::generate has optional template_id.
// Depending on implementation, if we pass template_id, it fetches from DB.
// So we need to insert a row in 'templates' table corresponding to this file.

$conn->query("INSERT INTO templates (template_name, template_code, file_path, is_active) VALUES ('Test HTML', 'TEST_HTML', 'mixed_template_test.docx', 1)");
$template_id = $conn->insert_id;

echo "Created test template ID: $template_id\n";

// Run Generation
$result = $generator->generate($profile_id, 'test_doc', $template_id);

print_r($result);

if ($result['success']) {
    echo "\nGenerated File: " . $result['file_path'] . "\n";
} else {
    echo "\nFailed: " . $result['error'] . "\n";
}
?>
