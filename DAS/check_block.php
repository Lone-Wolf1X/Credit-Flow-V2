<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/DocumentGenerator.php';

use PhpOffice\PhpWord\TemplateProcessor;

$templatePath = __DIR__ . '/templates/Individual/mortgage_deed.docx';
$templateProcessor = new TemplateProcessor($templatePath);

echo "Variables found via getVariables():\n";
print_r($templateProcessor->getVariables());

// There isn't a direct public method to get Blocks, but we can try to clone and catch error
try {
    $templateProcessor->cloneBlock('BLOCK_DETAILS', 2, true, true);
    echo "Success: Block 'BLOCK_DETAILS' was found and cloned (simulated).\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
