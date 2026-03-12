<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/DocumentGenerator.php';

use PhpOffice\PhpWord\TemplateProcessor;

echo "Running Block Clone Test...\n";

// 1. Data with Blocks
$data = [
    'BLOCK_TEST' => [
        ['name' => 'Amit Kumar', 'amount' => '50,000'],
        ['name' => 'Rahul Singh', 'amount' => '75,000'],
        ['name' => 'Sita Ram', 'amount' => '1,00,000']
    ]
];

// 2. Load Template
$templatePath = __DIR__ . '/templates/Individual/test_block_template.docx';
if (!file_exists($templatePath)) {
    die("Error: Template not created yet.\n");
}

$templateProcessor = new TemplateProcessor($templatePath);
$generator = new DocumentGenerator($conn);

// 3. Process Blocks
// Note: We need to make processBlocks public or use a helper script that can access it.
// Assuming I made it public in previous step.
$generator->processBlocks($templateProcessor, $data);

// 4. Save
$outputPath = __DIR__ . '/generated_documents/test_block_output.docx';
$templateProcessor->saveAs($outputPath);

echo "Generated: $outputPath\n";
