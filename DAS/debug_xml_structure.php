<?php
// Inspect docx XML Context
$zip = new ZipArchive;
$file = __DIR__ . '/templates/Individual/test_md.docx';

if ($zip->open($file) === TRUE) {
    if ($zip->locateName('word/document.xml') !== false) {
        $xml = $zip->getFromName('word/document.xml');
        
        $pos = strpos($xml, '${BLOCK_DETAILS}');
        if ($pos !== false) {
            echo "START TAG CONTEXT:\n";
            echo substr($xml, max(0, $pos - 400), 800) . "\n\n";
        } else {
            echo "Start tag not found via strpos.\n";
        }

        $posEnd = strpos($xml, '${/BLOCK_DETAILS}');
        if ($posEnd !== false) {
            echo "END TAG CONTEXT:\n";
            echo substr($xml, max(0, $posEnd - 400), 800) . "\n";
        }
    } else {
        echo "word/document.xml not found.\n";
    }
    $zip->close();
} else {
    echo "Failed to open zip.\n";
}
