<?php
/**
 * Placeholder Mapping Debugger
 * Use this to see exactly what placeholders are being generated for a profile
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/DocumentDataFetcher.php';
require_once __DIR__ . '/includes/DocumentRuleEngine.php';
require_once __DIR__ . '/includes/PlaceholderMapper.php';
require_once __DIR__ . '/includes/PlaceholderLibrary.php';

// Get profile ID from URL
$profile_id = $_GET['profile_id'] ?? 2;

echo "<html><head><title>Placeholder Debug - Profile $profile_id</title>";
echo "<style>
    body { font-family: monospace; padding: 20px; background: #f5f5f5; }
    .section { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; border: 1px solid #ddd; }
    h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
    h3 { color: #555; margin-top: 20px; }
    .placeholder { background: #fff3cd; padding: 2px 5px; border-radius: 3px; font-weight: bold; }
    .value { background: #d4edda; padding: 2px 5px; border-radius: 3px; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background: #007bff; color: white; }
    tr:nth-child(even) { background: #f8f9fa; }
    .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; }
    .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; }
    .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; }
</style></head><body>";

echo "<h1>🔍 Placeholder Mapping Debug - Profile #$profile_id</h1>";

try {
    // Connect to database
    $das_conn = new mysqli('localhost', 'root', '', 'das_db');
    
    if ($das_conn->connect_error) {
        throw new Exception("Database connection failed: " . $das_conn->connect_error);
    }
    
    // Initialize Rule Engine
    $ruleEngine = new DocumentRuleEngine('mortgage_deed', $das_conn);
    
    echo "<div class='section success'>";
    echo "<h2>✓ Rule Engine Initialized</h2>";
    echo "<p>Document Type: <strong>mortgage_deed</strong></p>";
    echo "</div>";
    
    // Fetch Data
    echo "<div class='section'>";
    echo "<h2>📊 Raw Data Fetched</h2>";
    $data = $ruleEngine->fetchData($profile_id);
    
    echo "<h3>Borrowers: " . count($data['persons']['borrowers'] ?? []) . "</h3>";
    foreach ($data['persons']['borrowers'] ?? [] as $i => $borrower) {
        $num = $i + 1;
        echo "<p><strong>Borrower $num:</strong> " . ($borrower['full_name'] ?? 'N/A') . "</p>";
    }
    
    echo "<h3>Guarantors: " . count($data['persons']['guarantors'] ?? []) . "</h3>";
    foreach ($data['persons']['guarantors'] ?? [] as $i => $guarantor) {
        $num = $i + 1;
        echo "<p><strong>Guarantor $num:</strong> " . ($guarantor['full_name'] ?? 'N/A') . "</p>";
    }
    
    echo "<h3>Collateral Owners: " . count($data['persons']['collateral_owners'] ?? []) . "</h3>";
    foreach ($data['persons']['collateral_owners'] ?? [] as $i => $owner) {
        $num = $i + 1;
        echo "<p><strong>Collateral Owner $num:</strong> " . ($owner['full_name'] ?? 'N/A') . "</p>";
    }
    
    echo "<h3>Collateral: " . count($data['collateral'] ?? []) . "</h3>";
    foreach ($data['collateral'] ?? [] as $i => $col) {
        $num = $i + 1;
        echo "<p><strong>Collateral $num:</strong> Kitta " . ($col['land_kitta_no'] ?? 'N/A') . "</p>";
    }
    
    echo "<h3>Loan:</h3>";
    echo "<pre>" . print_r($data['loan'] ?? [], true) . "</pre>";
    
    echo "</div>";
    
    // Map to Placeholders
    echo "<div class='section'>";
    echo "<h2>🎯 Generated Placeholders</h2>";
    $placeholders = $ruleEngine->mapToPlaceholders($data);
    
    echo "<p><strong>Total Placeholders: " . count($placeholders) . "</strong></p>";
    
    // Group by category
    $grouped = [
        'Borrowers' => [],
        'Guarantors' => [],
        'Collateral Owners' => [],
        'Collateral' => [],
        'Loan' => [],
        'Bank' => [],
        'Other' => []
    ];
    
    foreach ($placeholders as $key => $value) {
        if (strpos($key, 'BR') === 0) {
            $grouped['Borrowers'][$key] = $value;
        } elseif (strpos($key, 'GR') === 0) {
            $grouped['Guarantors'][$key] = $value;
        } elseif (strpos($key, 'CO') === 0) {
            $grouped['Collateral Owners'][$key] = $value;
        } elseif (strpos($key, 'COL') === 0) {
            $grouped['Collateral'][$key] = $value;
        } elseif (strpos($key, 'LN_') === 0) {
            $grouped['Loan'][$key] = $value;
        } elseif (strpos($key, 'BNK_') === 0 || strpos($key, 'DOC_') === 0) {
            $grouped['Bank'][$key] = $value;
        } else {
            $grouped['Other'][$key] = $value;
        }
    }
    
    foreach ($grouped as $category => $items) {
        if (empty($items)) continue;
        
        echo "<h3>$category (" . count($items) . ")</h3>";
        echo "<table>";
        echo "<tr><th>Placeholder</th><th>Value</th></tr>";
        
        foreach ($items as $key => $value) {
            $displayValue = htmlspecialchars($value);
            if (empty($value) || trim($value) === '') {
                $displayValue = "<span class='error'>(EMPTY)</span>";
            }
            echo "<tr>";
            echo "<td><span class='placeholder'>\${" . htmlspecialchars($key) . "}</span></td>";
            echo "<td><span class='value'>$displayValue</span></td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "</div>";
    
    // Check for Empty Placeholders
    echo "<div class='section warning'>";
    echo "<h2>⚠️ Empty Placeholders</h2>";
    $empty = array_filter($placeholders, function($v) { 
        return empty($v) || trim($v) === ''; 
    });
    
    if (empty($empty)) {
        echo "<p class='success'>✓ All placeholders have values!</p>";
    } else {
        echo "<p>Found " . count($empty) . " empty placeholders:</p>";
        echo "<ul>";
        foreach (array_keys($empty) as $key) {
            echo "<li><span class='placeholder'>\${" . htmlspecialchars($key) . "}</span></li>";
        }
        echo "</ul>";
    }
    echo "</div>";
    
    // Generate Sample Document Section
    echo "<div class='section'>";
    echo "<h2>📄 Sample Document Snippet</h2>";
    echo "<p>Example of how placeholders would appear in your template:</p>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff;'>";
    echo "<p><strong>ऋणी:</strong> <span class='placeholder'>\${BR1_NM_NP}</span> (<span class='value'>" . ($placeholders['BR1_NM_NP'] ?? '') . "</span>)</p>";
    echo "<p><strong>बाबुको नाम:</strong> <span class='placeholder'>\${BR1_FATHER}</span> (<span class='value'>" . ($placeholders['BR1_FATHER'] ?? '') . "</span>)</p>";
    echo "<p><strong>ठेगाना:</strong> <span class='placeholder'>\${BR1_P_MUN}</span>, वडा नं. <span class='placeholder'>\${BR1_P_WARD}</span></p>";
    echo "<p><strong>ऋण रकम:</strong> रु. <span class='placeholder'>\${LN_AMT}</span> (<span class='value'>" . ($placeholders['LN_AMT'] ?? '') . "</span>)</p>";
    echo "<p><strong>धितो किता:</strong> <span class='placeholder'>\${COL1_KITTA_NO}</span> (<span class='value'>" . ($placeholders['COL1_KITTA_NO'] ?? '') . "</span>)</p>";
    echo "</div>";
    echo "</div>";
    
    // Export for Template Builder
    echo "<div class='section'>";
    echo "<h2>📋 Copy-Paste Placeholders</h2>";
    echo "<p>Copy these placeholders to use in your template:</p>";
    echo "<textarea style='width: 100%; height: 300px; font-family: monospace; padding: 10px;'>";
    foreach ($placeholders as $key => $value) {
        echo "\${" . $key . "}\n";
    }
    echo "</textarea>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</body></html>";
?>
