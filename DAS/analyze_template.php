<?php
// Analyze template placeholders
require_once 'vendor/autoload.php';
require_once 'includes/PlaceholderLibrary.php';

use PhpOffice\PhpWord\TemplateProcessor;

$templatePath = 'templates/ptl/mortgage_deed.docx';

echo "Analyzing Template: $templatePath\n";

if (!file_exists($templatePath)) {
    die("Template file not found!\n");
}

try {
    $template = new TemplateProcessor($templatePath);
    $variables = $template->getVariables();
    
    echo "Found " . count($variables) . " placeholders:\n";
    sort($variables);
    
    // Get all known placeholders from library (construct a list of potential codes)
    $knownCodes = [];
    foreach (PlaceholderLibrary::$FIELD_MAPPING['person_fields'] as $key => $val) $knownCodes[] = $val;
    foreach (PlaceholderLibrary::$FIELD_MAPPING['loan_fields'] as $key => $val) $knownCodes[] = $val;
    foreach (PlaceholderLibrary::$FIELD_MAPPING['collateral_fields'] as $key => $val) $knownCodes[] = $val; // Note regex checks needed for COL1_...
    foreach (PlaceholderLibrary::$FIELD_MAPPING['bank_fields'] as $key => $val) $knownCodes[] = $val;
    
    $missing = [];
    $matched = [];
    
    foreach ($variables as $var) {
        $isKnown = false;
        
        // Check simple match
        if (in_array($var, $knownCodes)) $isKnown = true;
        
        // Check pattern match for prefixes
        // BR1_NM_NP
        if (preg_match('/^(BR|GR|CO)(\d+)_/', $var, $matches)) {
            $suffix = substr($var, strlen($matches[0]));
            if (in_array($suffix, $knownCodes)) $isKnown = true;
        }
        
        // COL1_KITTA_NO
        if (preg_match('/^COL(\d+)_/', $var, $matches)) {
            $suffix = substr($var, strlen($matches[0]));
            // Also logic in PlaceholderLibrary removes COL_ from code?
            // Library: 'land_kitta_no' => 'COL_KITTA_NO'
            // getCollateralPlaceholder: "COL" . $number . "_" . str_replace('COL_', '', $code)
            // So if code is COL_KITTA_NO, placeholder becomes COL1_KITTA_NO.
            // Suffix should be KITTA_NO.
            // Let's verify against stripped codes.
            foreach (PlaceholderLibrary::$FIELD_MAPPING['collateral_fields'] as $k => $v) {
                 $stripped = str_replace('COL_', '', $v);
                 if ($suffix === $stripped) $isKnown = true;
            }
        }
        
        if ($isKnown) {
            $matched[] = $var;
        } else {
            $missing[] = $var;
        }
    }
    
    echo "\n=== MATCHED PLACEHOLDERS (" . count($matched) . ") ===\n";
    print_r($matched);
    
    echo "\n=== UNKNOWN/MISSING PLACEHOLDERS (" . count($missing) . ") ===\n";
    print_r($missing);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
