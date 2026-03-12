<?php
// Unit test for DocumentGenerator logic
require_once 'config/config.php';
require_once 'includes/DocumentGenerator.php';

$generator = new DocumentGenerator($conn);
$profileId = 2; // Known to have 2 borrowers

echo "=== TESTING TEMPLATE RESOLUTION ===\n";

// Debug where the class is loaded from
$ref = new ReflectionClass('DocumentGenerator');
echo "Class Loaded From: " . $ref->getFileName() . "\n";
echo "Has generateDocument method: " . ($ref->hasMethod('generateDocument') ? 'YES' : 'NO') . "\n";

// Test 1: Mortgage Deed (should resolve to _1 because profile 2 has 1 borrower)
$basePath = __DIR__ . '/templates/ptl/mortgage_deed.docx';
$resolved = $generator->resolveDynamicPath($basePath, $profileId);
echo "Base: " . basename($basePath) . "\n";
echo "Resolved: " . basename($resolved) . "\n";

if (basename($resolved) === 'mortgage_deed_1.docx') {
    echo "SUCCESS: Correctly resolved to 1 borrower template.\n";
} else {
    echo "FAILURE: Resolved to " . basename($resolved) . " (Expected mortgage_deed_1.docx)\n";
}

// Introspect Object
echo "Object Class: " . get_class($generator) . "\n";
echo "Methods available: " . implode(", ", get_class_methods($generator)) . "\n";

// Proceed to Test 3
if (method_exists($generator, 'generateDocument')) {
    echo "Method exists check passed.\n";
    $result = $generator->generateDocument($resolved, $profileId, 'mortgage_deed');

    if ($result['success']) {
        echo "Generation Success.\n";
        echo "Saved to: " . $result['output_path'] . "\n";
        // Check log for Kitta Words match
        $logFile = 'generated_documents/last_generation_log.txt';
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            if (strpos($logContent, '[MATCH] $COL1_DHITO_MULYA') !== false) {
                echo "Log Check: COL1_DHITO_MULYA found.\n";
            } else {
                echo "Log Check: COL1_DHITO_MULYA NOT found.\n";
            }
            
            if (strpos($logContent, '[MATCH] $COL1_KITTA_WORDS') !== false) {
                 echo "Log Check: COL1_KITTA_WORDS found.\n";
            } else {
                 echo "Log Check: COL1_KITTA_WORDS NOT found.\n";
            }
        }
    } else {
        echo "Generation Failed: " . $result['error'] . "\n";
    }
} else {
    echo "FATAL: generateDocument method NOT found on instance!\n";
}
