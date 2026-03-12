<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/DocumentGenerator.php';

use PhpOffice\PhpWord\TemplateProcessor;

echo "Testing Mortgage Deed Block Clone...\n";

// 1. Mock Data for 2 People
// User confirmed tag is BLOCK_DETAILS
$data = [
    'BLOCK_DETAILS' => [
        [
            'name' => 'Ram Bahadur',
            'address' => 'Kathmandu',
            'citizenship_no' => '12345',
            'father_name' => 'Hari Bahadur',
            'grandfather_name' => 'Shyam Bahadur'
        ],
        [
            'name' => 'Sita Kumari',
            'address' => 'Lalitpur',
            'citizenship_no' => '67890',
            'father_name' => 'Gopal Prasad',
            'grandfather_name' => 'Krishna Prasad'
        ]
    ]
];

// 2. Load the likely file
$templatePath = __DIR__ . '/templates/Individual/mortgage_deed.docx';

if (!file_exists($templatePath)) {
    // Try another path in case
    $templatePath = __DIR__ . '/../credit_flow/uploads/mortgage_deed.docx';
    if (!file_exists($templatePath)) {
         die("Could not find mortgage_deed.docx in expected paths.\n");
    }
}
echo "Using template: $templatePath\n";

$templateProcessor = new TemplateProcessor($templatePath);
$generator = new DocumentGenerator($conn);

// 3. Process Blocks
echo "Processing blocks...\n";
$generator->processBlocks($templateProcessor, $data);

// 4. Save
$outputPath = __DIR__ . '/generated_documents/test_mortgage_block_output.docx';
$templateProcessor->saveAs($outputPath);

echo "Generated: $outputPath\n";
