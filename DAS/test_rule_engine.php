<?php
/**
 * Test Document Rule Engine
 * Verifies rule-based data fetching and mapping
 */

require_once __DIR__ . '/includes/DocumentRuleEngine.php';

// Database connection
$conn = new mysqli('localhost', 'root', '', 'das_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== Testing Document Rule Engine ===\n\n";

try {
    // Test with customer profile ID 1
    $profileId = 1;
    
    echo "1. Loading Rules...\n";
    $engine = new DocumentRuleEngine('mortgage_deed', $conn);
    echo "   ✅ Rules loaded successfully\n\n";
    
    echo "2. Fetching Data for Profile ID: $profileId...\n";
    $data = $engine->fetchData($profileId);
    
    echo "   Borrowers: " . count($data['persons']['borrowers'] ?? []) . "\n";
    echo "   Guarantors: " . count($data['persons']['guarantors'] ?? []) . "\n";
    echo "   Collateral Owners: " . count($data['persons']['collateral_owners'] ?? []) . "\n";
    echo "   Collateral Items: " . count($data['collateral'] ?? []) . "\n";
    echo "   ✅ Data fetched\n\n";
    
    echo "3. Mapping to Placeholders...\n";
    $placeholders = $engine->mapToPlaceholders($data);
    
    echo "   Total Placeholders: " . count($placeholders) . "\n\n";
    
    echo "4. Sample Placeholders:\n";
    echo "   " . str_repeat("-", 60) . "\n";
    
    $sampleKeys = array_slice(array_keys($placeholders), 0, 10);
    foreach ($sampleKeys as $key) {
        $value = $placeholders[$key];
        $displayValue = strlen($value) > 40 ? substr($value, 0, 40) . '...' : $value;
        printf("   %-20s => %s\n", $key, $displayValue);
    }
    echo "   " . str_repeat("-", 60) . "\n";
    
    // Check for multi-person support
    echo "\n5. Multi-Person Support Check:\n";
    $personPrefixes = ['BR1_', 'BR2_', 'GR1_', 'GR2_', 'CO1_', 'CO2_'];
    foreach ($personPrefixes as $prefix) {
        $found = false;
        foreach ($placeholders as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $found = true;
                break;
            }
        }
        $status = $found ? "✅ Found" : "⚠️  Not found";
        echo "   $status: {$prefix}xxx\n";
    }
    
    echo "\n🎉 Rule Engine Test Complete!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

$conn->close();
