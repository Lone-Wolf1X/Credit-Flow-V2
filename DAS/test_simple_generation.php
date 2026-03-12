<?php
/**
 * Simple Document Generation Test
 * Generate with minimal placeholders to test
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

echo "=== Simple Document Generation Test ===\n\n";

$template_path = __DIR__ . '/templates/ptl/mortgage_deed.docx';
$output_path = __DIR__ . '/generated/Test_Simple_' . date('Ymd_His') . '.docx';

try {
    // Load template
    $template = new TemplateProcessor($template_path);
    echo "✅ Template loaded\n";
    
    // Get all variables
    $variables = $template->getVariables();
    echo "Template has " . count($variables) . " placeholders\n\n";
    
    // Set simple test values (no conversion, just plain text)
    echo "Setting placeholder values...\n";
    
    $testValues = [
        'CO1_NM_NP' => 'टेस्ट नाम',
        'CO1_NM_EN' => 'Test Name',
        'CO1_DOB' => '२०५०-०१-०१',
        'CO1_AGE' => '३०',
        'CO1_CIT_NO' => '१२-३४-५६-७८९०',
        'CO1_P_DIST' => 'काठमाडौं',
        'CO1_P_MUN' => 'काठमाडौं महानगरपालिका',
        'CO1_P_WARD' => '१',
        'COL_PROV' => 'बागमती प्रदेश',
        'COL_DIST' => 'काठमाडौं',
        'COL_MUN' => 'काठमाडौं महानगरपालिका',
        'COL_WARD' => '१',
        'COL_KITTA_NO' => '१२३',
        'COL_AREA' => '०-०-१-०',
        'LN_AMT' => 'रू ५,००,०००',
        'LN_AMT_WORDS' => 'पाँच लाख रुपैयाँ मात्र',
        'LN_SCHEME' => 'गृह ऋण',
        'SN1' => '१',
    ];
    
    foreach ($testValues as $key => $value) {
        $template->setValue($key, $value);
        echo "  Set: $key\n";
    }
    
    echo "\n✅ All values set\n";
    
    // Save document
    echo "Saving document...\n";
    $template->saveAs($output_path);
    
    echo "\n✅ SUCCESS!\n";
    echo "File saved: $output_path\n";
    echo "\nTry opening this file in MS Word.\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
