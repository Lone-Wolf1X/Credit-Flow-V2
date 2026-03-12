<?php
// Inspect docx XML
$zip = new ZipArchive;
// Using the file from the last test run
$file = __DIR__ . '/templates/Individual/mortgage_deed.docx';

if ($zip->open($file) === TRUE) {
    $xml = $zip->getFromName('word/document.xml');
    
    // Check for BLOCK tags
    if (strpos($xml, '${BLOCK_DETAILS}') !== false) {
        echo "Found \${BLOCK_DETAILS} tag.\n";
    } else {
        echo "Did NOT find \${BLOCK_DETAILS} tag.\n";
        // Search for partial or broken tags
        if (strpos($xml, 'BLOCK_DETAILS') !== false) {
            echo "Found 'BLOCK_DETAILS' text but maybe not as a clean tag.\n";
        }
    }
    
    if (strpos($xml, '${/BLOCK_DETAILS}') !== false) {
        echo "Found \${/BLOCK_DETAILS} tag.\n";
    } else {
        echo "Did NOT find \${/BLOCK_DETAILS} tag.\n";
    }
    
    // Dump a snippet around the tag if found
    $pos = strpos($xml, 'BLOCK_DETAILS');
    if ($pos !== false) {
        echo "Snippet:\n" . substr($xml, max(0, $pos - 100), 300) . "\n";
    }

    $zip->close();
} else {
    echo 'failed';
}
