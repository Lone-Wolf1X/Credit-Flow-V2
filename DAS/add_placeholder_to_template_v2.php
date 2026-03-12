<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

$file = __DIR__ . '/templates/Individual/mortgage_deed_test.docx';

if (!file_exists($file)) {
    die("File not found: $file\n");
}

echo "Loading template: $file\n";

try {
    // 1. Appending with IOFactory
    $phpWord = IOFactory::load($file);

    // Get the first section
    $sections = $phpWord->getSections();
    if (count($sections) > 0) {
        $section = $sections[0];
    } else {
        $section = $phpWord->addSection();
    }

    $section->addTextBreak(2);
    // Add plain text without style array to avoid XML splitting
    $section->addText('${MD_BRTBL}'); 
    // Also add the paragraph placeholder to test
    $section->addTextBreak(1);
    $section->addText('${MD_LND_DTLS}');

    $writer = IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($file);
    
    echo "Saved modified template.\n";
    
    // 2. Verification
    echo "Verifying variables...\n";
    $temp = new TemplateProcessor($file);
    $vars = $temp->getVariables();
    print_r($vars);
    
    if (in_array('MD_BRTBL', $vars)) {
        echo "SUCCESS: MD_BRTBL found!\n";
    } else {
        echo "FAILURE: MD_BRTBL not found.\n";
        // maybe try setValue to see if it works anyway?
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
