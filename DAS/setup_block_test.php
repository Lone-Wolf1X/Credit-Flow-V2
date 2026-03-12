<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;

echo "Creating Template with Block Tags...\n";

$phpWord = new PhpWord();
$section = $phpWord->addSection();

$section->addText("Clone Block Test Template", ['bold' => true, 'size' => 16]);
$section->addTextBreak(1);

// Start Block Tag
$section->addText('${BLOCK_TEST}', ['color' => 'FF0000']);

// Add Table inside block
$table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'width' => 5000]);
$table->addRow();
$table->addCell(2000)->addText("Name: ${name}");
$table->addCell(2000)->addText("Amount: ${amount}");

// End Block Tag
$section->addText('${/BLOCK_TEST}', ['color' => 'FF0000']);

$section->addTextBreak(1);
$section->addText("End of Document");

$file = __DIR__ . '/templates/Individual/test_block_template.docx';
if (!file_exists(dirname($file))) {
    mkdir(dirname($file), 0777, true);
}

$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save($file);

echo "Template created at: $file\n";
