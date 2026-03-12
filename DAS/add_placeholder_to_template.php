<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;

$file = __DIR__ . '/templates/Individual/mortgage_deed_test.docx';

if (!file_exists($file)) {
    die("File not found: $file\n");
}

echo "Loading template: $file\n";

try {
    // Load the existing document
    $phpWord = IOFactory::load($file);

    // Get the first section (or add one if none - unlikely)
    $sections = $phpWord->getSections();
    if (count($sections) > 0) {
        $section = $sections[0];
    } else {
        $section = $phpWord->addSection();
    }

    // Add some spacing
    $section->addTextBreak(2);
    
    // Add the missing placeholder
    $style = ['bold' => true, 'color' => 'FF0000', 'size' => 14];
    $section->addText("--- Auto-Injected Placeholder ---", ['italic' => true]);
    $section->addText('${MD_BRTBL}', $style);
    $section->addText("---------------------------------");

    // Save
    $writer = IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($file);
    
    echo "Successfully appended '\${MD_BRTBL}' to the template.\n";

} catch (Exception $e) {
    echo "Error processing file: " . $e->getMessage() . "\n";
    // echo $e->getTraceAsString();
}
?>
