<?php
/**
 * Database Schema Extractor for Placeholder Mapping
 * Extracts all columns from all relevant tables
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
    'loan_schemes'
];

$schema = [];

foreach ($tables as $table) {
    echo "Analyzing: $table\n";
    echo str_repeat("=", 80) . "\n";
    
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = [
                'field' => $row['Field'],
                'type' => $row['Type'],
                'null' => $row['Null'],
                'key' => $row['Key'],
                'default' => $row['Default']
            ];
            
            printf("%-40s %-30s %s\n", 
                $row['Field'], 
                $row['Type'], 
                $row['Null'] == 'YES' ? 'NULL' : 'NOT NULL'
            );
        }
        $schema[$table] = $columns;
    }
    echo "\n";
}

// Save schema to JSON for reference
file_put_contents('schema_mapping.json', json_encode($schema, JSON_PRETTY_PRINT));
echo "\nSchema saved to schema_mapping.json\n";

$conn->close();
?>
