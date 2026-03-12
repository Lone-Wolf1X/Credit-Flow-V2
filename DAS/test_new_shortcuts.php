<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

echo "Creating test template with NEW field shortcuts...\n";

$phpWord = new PhpWord();
$section = $phpWord->addSection();

// Test new shortcuts
$section->addText('Borrower 1 Son: br1_son');
$section->addText('Borrower 2 Daughter: br2_daughter');
$section->addText('Guarantor 1 Mother-in-law: gr1_mil');
$section->addText('Collateral Owner 1 Temp Country: co1_temp_country');
$section->addText('Borrower 3 Perm Street Number: br3_perm_street_number');
$section->addText('Guarantor 2 Relationship Status: gr2_relationship_status');

$file = __DIR__ . '/templates/test_new_shortcuts.docx';
if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);

$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($file);

echo "Created: $file\n";
echo "Now converting...\n\n";

// Convert it
require_once __DIR__ . '/includes/TemplatePlaceholderConverter.php';
$result = TemplatePlaceholderConverter::convert($file);

if ($result['success']) {
    echo "✅ Conversion SUCCESS!\n";
    echo "Replacements: {$result['replacements']}\n";
    echo "Output: {$result['output']}\n\n";
    
    // Read and verify
    $converted = IOFactory::load($result['output']);
    echo "Converted Content:\n";
    echo str_repeat("=", 50) . "\n";
    foreach ($converted->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            if (method_exists($element, 'getText')) {
                echo $element->getText() . "\n";
            }
        }
    }
    echo str_repeat("=", 50) . "\n";
} else {
    echo "❌ FAILED: {$result['error']}\n";
}
