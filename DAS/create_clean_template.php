<?php
require_once __DIR__ . '/vendor/autoload.php';

$phpWord = new \PhpOffice\PhpWord\PhpWord();
$section = $phpWord->addSection();
$section->addText("This is a clean test template.");
$section->addText("Below should be the table:");
$section->addText('${MD_BRTBL}');
$section->addText("End of document.");

$writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$file = __DIR__ . '/templates/Individual/clean_test.docx';
$writer->save($file);

echo "Created clean template at $file\n";
?>
