<?php
require_once __DIR__ . '/vendor/autoload.php';

$file = __DIR__ . '/Templates 2/DPN1.docx';

try {
    $phpWord = \PhpOffice\PhpWord\IOFactory::load($file);
    $text = '';
    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                foreach ($element->getElements() as $textElement) {
                    if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                        $text .= $textElement->getText();
                    }
                }
            } elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                $text .= $element->getText();
            }
        }
        $text .= "\n";
    }
    echo "Content of DPN1.docx:\n" . substr($text, 0, 1000); // First 1000 chars
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
