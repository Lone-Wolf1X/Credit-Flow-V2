<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

echo "Running SIMPLE Clone Test...\n";

$templatePath = __DIR__ . '/templates/Individual/mortgage_deed.docx';
$outputPath = __DIR__ . '/generated_documents/simple_clone_output.docx';

$templateProcessor = new TemplateProcessor($templatePath);

// Data
$items = [
    ['name' => 'Ram Bahadur'],
    ['name' => 'Sita Kumari']
];

// MANUAL CLONE
try {
    echo "Cloning BLOCK_DETAILS...\n";
    $templateProcessor->cloneBlock('BLOCK_DETAILS', count($items), true, true);
    
    $i = 1;
    foreach ($items as $item) {
        $templateProcessor->setValue('name#' . $i, $item['name']);
        echo "Set name#$i to " . $item['name'] . "\n";
        $i++;
    }
    
    $templateProcessor->saveAs($outputPath);
    echo "Saved to $outputPath\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
