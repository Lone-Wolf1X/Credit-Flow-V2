<?php
// Simple test without dependencies
require_once __DIR__ . '/includes/TemplatePlaceholderConverter.php';

echo "=== Testing Converter with New Shortcuts ===\n\n";

$shortcuts = [
    'br1_son' => '${BR1_SON}',
    'br2_daughter' => '${BR2_DAUGHTER}',
    'gr1_mil' => '${GR1_MIL}',
    'co1_temp_country' => '${CO1_T_CNTRY}',
    'br3_perm_street_number' => '${BR3_P_STREET_NO}',
    'gr2_relationship_status' => '${GR2_REL_STATUS}',
];

$allPassed = true;
foreach ($shortcuts as $input => $expected) {
    // Use reflection to test private method
    $reflection = new ReflectionClass('TemplatePlaceholderConverter');
    $method = $reflection->getMethod('replaceShorthands');
    $method->setAccessible(true);
    
    $result = $method->invoke(null, $input);
    
    if ($result === $expected) {
        echo "✅ $input → $result\n";
    } else {
        echo "❌ $input → Expected: $expected, Got: $result\n";
        $allPassed = false;
    }
}

if ($allPassed) {
    echo "\n🎉 All NEW shortcuts convert correctly!\n";
    echo "\n✨ Your converter is READY for upload!\n";
} else {
    echo "\n⚠️ Some shortcuts failed.\n";
}
