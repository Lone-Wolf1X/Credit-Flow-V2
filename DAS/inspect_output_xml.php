<?php
// Inspect OUTPUT docx XML
$zip = new ZipArchive;
$file = __DIR__ . '/generated_documents/test_md_output.docx';

echo "Checking: $file\n";

if (!file_exists($file)) {
    die("File not found!\n");
}

if ($zip->open($file) === TRUE) {
    if ($zip->locateName('word/document.xml') !== false) {
        $xml = $zip->getFromName('word/document.xml');
        
        // Regex Match for any CO1-CO9 variable
        $matches = [];
        if (preg_match_all('/CO[1-9]_[A-Z_0-9#]+/', $xml, $matches)) {
             // Count unique prefixes
             $prefixes = [];
             foreach($matches[0] as $m) {
                 $parts = explode('_', $m);
                 $prefixes[$parts[0]] = true;
             }
            echo "Variable Prefixes Found: " . implode(', ', array_keys($prefixes)) . "\n";
            echo "Total Variables Found: " . count($matches[0]) . "\n";
        } else {
            echo "No CO variables found.\n";
        }
        
    } else {
        echo "word/document.xml not found.\n";
    }
    $zip->close();
} else {
    echo "Failed to open zip.\n";
}
