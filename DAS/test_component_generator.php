<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/DocumentGenerator.php';
require_once __DIR__ . '/includes/DocumentDataFetcher.php';

use PhpOffice\PhpWord\TemplateProcessor;

$profileId = 2026001; // Test profile ID

echo "Starting component injection test...\n";

// Initialize Generator
$generator = new DocumentGenerator($conn);

// 1. Fetch Data
$fetcher = new DocumentDataFetcher($conn, $profileId);
$data = $fetcher->fetchAllData();
echo "Data fetched for profile $profileId.\n";
// Debug data a bit
// print_r(array_keys($data));

// 2. Load Template
$templatePath = __DIR__ . '/templates/Individual/test.docx';
if (!file_exists($templatePath)) {
    // Try user upload path if template not found
    $templatePath = __DIR__ . '/../credit_flow/uploads/Test_user/Test.docx';
    if (!file_exists($templatePath)) {
        die("Template not found at default or upload path.\n");
    }
}
echo "Template loaded: $templatePath\n";

$templateProcessor = new TemplateProcessor($templatePath);

// 3. Inject Components (The main test)
echo "Injecting components...\n";
try {
    $generator->renderHtmlComponents($templateProcessor, $data);
} catch (Exception $e) {
    echo "Error during injection: " . $e->getMessage() . "\n";
}

// 4. Save Output
$outputDir = __DIR__ . '/generated_documents';
if (!file_exists($outputDir)) {
    mkdir($outputDir, 0777, true);
}
$outputPath = $outputDir . '/test_component_output.docx';
$templateProcessor->saveAs($outputPath);

echo "Document saved to: $outputPath\n";
echo "Done.\n";
