<?php
/**
 * Test Template Converter
 * Upload a Word file and convert shorthand codes to placeholders
 */

require_once __DIR__ . '/includes/TemplatePlaceholderConverter.php';

echo "=== Template Placeholder Converter ===\n\n";

// Show available shortcuts
echo "Available Shortcuts:\n";
TemplatePlaceholderConverter::showReference();

echo "\n\n" . str_repeat("=", 60) . "\n";
echo "Usage:\n";
echo "  1. Create a Word document with shorthand codes\n";
echo "  2. Example: Type 'br1_name' for borrower name\n";
echo "  3. Run converter to replace with ${BR1_NM_NP}\n";
echo str_repeat("=", 60) . "\n\n";

// Example conversion
$inputFile = __DIR__ . '/templates/dummy_shorthand_template.docx';
$outputFile = __DIR__ . '/templates/dummy_shorthand_template_converted.docx';

if (file_exists($inputFile)) {
    echo "Converting: $inputFile\n";
    $result = TemplatePlaceholderConverter::convert($inputFile, $outputFile);
    
    if ($result['success']) {
        echo "\n✅ SUCCESS!\n";
        echo "  Input: {$result['input']}\n";
        echo "  Output: {$result['output']}\n";
        echo "  Replacements: {$result['replacements']}\n";
        echo "  Message: {$result['message']}\n";
    } else {
        echo "\n❌ FAILED!\n";
        echo "  Error: {$result['error']}\n";
    }
} else {
    echo "No template file found at: $inputFile\n";
    echo "\nTo use:\n";
    echo "  1. Place your Word file at: $inputFile\n";
    echo "  2. Run: php DAS/convert_template.php\n";
}
