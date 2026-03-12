<?php
/**
 * Debug Document Generation
 * Check what's wrong with the generated document
 */

require_once __DIR__ . '/includes/DocumentGenerator.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

// Database connection
$conn = new mysqli('localhost', 'root', '', 'das_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== Document Generation Debug ===\n\n";

try {
    // Get first customer profile with collateral
    $result = $conn->query("
        SELECT cp.id
        FROM customer_profiles cp
        LEFT JOIN collateral c ON cp.id = c.customer_profile_id
        WHERE c.id IS NOT NULL
        LIMIT 1
    ");
    
    if ($result && $result->num_rows > 0) {
        $profile = $result->fetch_assoc();
        $customer_profile_id = $profile['id'];
        
        echo "Testing with Profile ID: $customer_profile_id\n\n";
        
        // Fetch data
        require_once __DIR__ . '/includes/DocumentDataFetcher.php';
        require_once __DIR__ . '/includes/PlaceholderMapper.php';
        
        $fetcher = new DocumentDataFetcher($conn, $customer_profile_id);
        $data = $fetcher->fetchAllData();
        
        echo "Data fetched:\n";
        echo "  Borrowers: " . count($data['borrowers']) . "\n";
        echo "  Guarantors: " . count($data['guarantors']) . "\n";
        echo "  Collateral: " . count($data['collateral']) . "\n\n";
        
        // Map placeholders
        $mapper = new PlaceholderMapper($data);
        $placeholders = $mapper->mapForMortgageDeed(0);
        
        echo "Placeholders mapped: " . count($placeholders) . "\n\n";
        
        // Show first 10 placeholders
        echo "Sample placeholders:\n";
        $count = 0;
        foreach ($placeholders as $key => $value) {
            if ($count++ >= 10) break;
            $displayValue = mb_strlen($value) > 50 ? mb_substr($value, 0, 50) . '...' : $value;
            echo "  $key = $displayValue\n";
        }
        
        echo "\n✅ Data fetching and mapping successful!\n";
        echo "Issue might be with PHPWord template processing.\n\n";
        
        // Try to load template
        echo "Checking template...\n";
        $template_path = __DIR__ . '/templates/ptl/mortgage_deed.docx';
        
        if (file_exists($template_path)) {
            echo "✅ Template found: $template_path\n";
            
            try {
                $template = new TemplateProcessor($template_path);
                echo "✅ Template loaded successfully\n";
                
                // Get template variables
                $variables = $template->getVariables();
                echo "\nTemplate has " . count($variables) . " placeholders\n";
                
                // Show first 10
                echo "Sample template placeholders:\n";
                foreach (array_slice($variables, 0, 10) as $var) {
                    echo "  \${" . $var . "}\n";
                }
                
            } catch (Exception $e) {
                echo "❌ Error loading template: " . $e->getMessage() . "\n";
            }
            
        } else {
            echo "❌ Template not found!\n";
        }
        
    } else {
        echo "❌ No profiles found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

$conn->close();
