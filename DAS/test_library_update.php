<?php
/**
 * Test Updated Placeholder Library
 * Verifies all new fields are accessible for dynamic indices
 */

require_once __DIR__ . '/includes/PlaceholderLibrary.php';

echo "=== Testing Updated Placeholder Library ===\n\n";

$tests = [
    // Borrower 1 - Basic + New Fields
    ['type' => 'Borrower', 'num' => 1, 'field' => 'son_name', 'expect' => '${BR1_SON}'],
    ['type' => 'Borrower', 'num' => 1, 'field' => 'perm_street_number', 'expect' => '${BR1_P_STREET_NO}'],
    
    // Guarantor 5 - Dynamic Index + New Family
    ['type' => 'Guarantor', 'num' => 5, 'field' => 'mother_in_law', 'expect' => '${GR5_MIL}'],
    ['type' => 'Guarantor', 'num' => 5, 'field' => 'temp_country', 'expect' => '${GR5_T_CNTRY}'],
    
    // Collateral Owner 2 - Citizenship Reissue
    ['type' => 'CollateralOwner', 'num' => 2, 'field' => 'reissue_times', 'expect' => '${CO2_REISS_TIMES}'],
    ['type' => 'CollateralOwner', 'num' => 2, 'field' => 'relationship_status', 'expect' => '${CO2_REL_STATUS}'],
];

$passed = 0;
foreach ($tests as $t) {
    $result = PlaceholderLibrary::getPersonPlaceholder($t['type'], $t['num'], $t['field']);
    if ($result === $t['expect']) {
        echo "✅ {$t['type']} {$t['num']} {$t['field']} -> $result\n";
        $passed++;
    } else {
        echo "❌ {$t['type']} {$t['num']} {$t['field']} -> Expected {$t['expect']}, Got $result\n";
    }
}

if ($passed === count($tests)) {
    echo "\n🎉 All library updates verified successfully!\n";
} else {
    echo "\n⚠️ Some tests failed.\n";
}
