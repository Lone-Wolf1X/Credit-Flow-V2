<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

echo "Running Template Expansion Test...\n";

$template = __DIR__ . '/templates/Individual/test_md.docx';
$output = __DIR__ . '/generated_documents/test_expanded.docx';

// 1. Manually Open Zip to get XML
$zip = new ZipArchive;
if ($zip->open($template) === TRUE) {
    $xml = $zip->getFromName('word/document.xml');
    
    // 2. Identify Block
    $blockStart = '${BLOCK_DETAILS}';
    $blockEnd = '${/BLOCK_DETAILS}';
    
    // Simple Regex to grab content between tags
    // We assume tags are fairly clean or we use a loose match
    $pattern = '/' . preg_quote($blockStart, '/') . '(.*?)' . preg_quote($blockEnd, '/') . '/s';
    
    if (preg_match($pattern, $xml, $matches)) {
        $fullMatch = $matches[0];
        $innerContent = $matches[1];
        
        echo "Block Found! Length: " . strlen($innerContent) . "\n";
        
        // 3. Create Clone with CO1 -> CO2 Replacement
        $clone2 = $innerContent;
        $clone2 = str_replace('CO1', 'CO2', $clone2); // Rename variables
        
        // 4. Inject: Original + Clone 2
        // We replace the WHOLE block (including tags) with:
        // Inner (Table 1) + Inner (Table 2 renamed)
        // (We remove tags so they don't show up, or keep them if we want to recurse?)
        // Let's remove matches.
        
        $newContent = $innerContent . $clone2;
        
        $newXml = str_replace($fullMatch, $newContent, $xml);
        
        // 5. Save
        $zip->addFromString('word/document.xml', $newXml);
        $zip->close();
        
        echo "Expanded Template Saved to: $output\n";
        
        // Copy to output
        copy($template, $output);
        $zip2 = new ZipArchive;
        $zip2->open($output);
        $zip2->addFromString('word/document.xml', $newXml);
        $zip2->close();
        
    } else {
        echo "Block tags not found in XML (Regex failed).\n";
        // Dump snippet for debugging
        echo "Snippet: " . substr($xml, 0, 500) . "\n";
    }
} else {
    echo "Failed to open template.\n";
}
