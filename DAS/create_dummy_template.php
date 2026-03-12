<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

echo "Creating dummy template with shorthands...\n";

$phpWord = new PhpWord();
$section = $phpWord->addSection();

$section->addText('This is a test template for shorthand conversion.');
$section->addText('Borrower 1 Name: br1_name');
$section->addText('Borrower 1 Citizenship: br1_cit');
$section->addText('Loan Amount: ln_amt');
$section->addText('Collateral Kitta: col_kitta');
$section->addText('Some text that should remain unchanged.');

$file = __DIR__ . '/templates/dummy_shorthand_template.docx';

// Ensure directory exists
if (!is_dir(dirname($file))) {
    mkdir(dirname($file), 0777, true);
}

$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($file);

echo "Template created at: $file\n";
