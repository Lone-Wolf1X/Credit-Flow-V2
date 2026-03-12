<?php
/**
 * Test Document Generation
 * Generate a mortgage deed for testing
 */

require_once __DIR__ . '/includes/DocumentGenerator.php';

// Database connection
$conn = new mysqli('localhost', 'root', '', 'das_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== Document Generation Test ===\n\n";

// Get first customer profile with collateral
$result = $conn->query("
    SELECT cp.id, cp.customer_id, cp.full_name,
           COUNT(c.id) as collateral_count
    FROM customer_profiles cp
    LEFT JOIN collateral c ON cp.id = c.customer_profile_id
    WHERE c.id IS NOT NULL
    GROUP BY cp.id
    LIMIT 1
");

if ($result && $result->num_rows > 0) {
    $profile = $result->fetch_assoc();
    
    echo "Testing with Profile:\n";
    echo "  ID: {$profile['id']}\n";
    echo "  Customer ID: {$profile['customer_id']}\n";
    echo "  Name: {$profile['full_name']}\n";
    echo "  Collateral Count: {$profile['collateral_count']}\n\n";
    
    // Generate document
    echo "Generating mortgage deed...\n";
    
    $generator = new DocumentGenerator($conn);
    $result = $generator->generateMortgageDeed($profile['id']);
    
    if ($result['success']) {
        echo "\n✅ SUCCESS!\n";
        echo "  Filename: {$result['filename']}\n";
        echo "  Path: {$result['path']}\n";
        echo "  Message: {$result['message']}\n";
    } else {
        echo "\n❌ FAILED!\n";
        echo "  Error: {$result['error']}\n";
    }
    
} else {
    echo "❌ No customer profiles with collateral found in database.\n";
    echo "Please create a customer profile with collateral first.\n";
}

$conn->close();
