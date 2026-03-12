<?php
/**
 * Database Schema Analyzer
 * Gets all table structures to create comprehensive placeholder mapping
 */

$conn = new mysqli('localhost', 'root', '', 'das_db');

$tables = [
    'customer_profiles',
    'borrowers',
    'guarantors',
    'collateral',
    'loan_details',
    'limit_details',
    'family_details',
    'authorized_persons',
    'loan_schemes',
    'das_placeholders',
    'template_placeholders'
];

echo "=== DATABASE SCHEMA ANALYSIS ===\n\n";

foreach ($tables as $table) {
    echo "TABLE: $table\n";
    echo str_repeat("=", 80) . "\n";
    
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            printf("%-30s %-20s %s\n", 
                $row['Field'], 
                $row['Type'], 
                $row['Null'] == 'YES' ? 'NULL' : 'NOT NULL'
            );
        }
    } else {
        echo "Error: Table not found\n";
    }
    echo "\n";
}

// Check existing placeholders
echo "\n=== EXISTING PLACEHOLDERS ===\n";
echo str_repeat("=", 80) . "\n";
$result = $conn->query("SELECT * FROM das_placeholders ORDER BY category, placeholder_key");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        printf("%-40s | %-20s | %s\n", 
            $row['placeholder_key'] ?? 'N/A', 
            $row['category'] ?? 'N/A',
            $row['description'] ?? 'N/A'
        );
    }
} else {
    echo "No existing placeholders found or table doesn't exist\n";
}

$conn->close();
?>
