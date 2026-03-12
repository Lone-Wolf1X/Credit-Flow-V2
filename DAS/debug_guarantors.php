<?php
require_once 'config/config.php';

// Connect to das_db
$das_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, 'das_db');

echo "=== GUARANTOR DEBUG FOR PROFILE 2026001 ===\n\n";

// Check guarantors in credit_db
echo "Guarantors in Database:\n";
echo str_repeat('-', 80) . "\n";
$result = $conn->query("SELECT id, full_name_np, full_name, is_co_borrower FROM guarantors WHERE customer_profile_id = 2026001 ORDER BY id");
while($row = $result->fetch_assoc()) {
    echo sprintf("ID: %d | Name (NP): %s | Name (EN): %s | Co-Borrower: %s\n", 
        $row['id'], 
        $row['full_name_np'] ?? 'N/A',
        $row['full_name'] ?? 'N/A',
        $row['is_co_borrower'] ?? '0'
    );
}

echo "\n\nGenerated Documents:\n";
echo str_repeat('-', 80) . "\n";
$result = $das_conn->query("SELECT id, filename, created_at FROM generated_documents WHERE customer_profile_id = 2026001 AND (filename LIKE '%Personal%' OR filename LIKE '%Attorney%' OR filename LIKE '%POA%' OR filename LIKE '%Guarantor%') ORDER BY created_at DESC");
while($row = $result->fetch_assoc()) {
    echo sprintf("ID: %d | File: %s | Created: %s\n", 
        $row['id'],
        $row['filename'],
        $row['created_at']
    );
}
