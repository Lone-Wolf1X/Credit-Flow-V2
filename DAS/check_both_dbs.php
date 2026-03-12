<?php
// Check both possible databases
$das = new mysqli('localhost', 'root', '', 'das_db');
$credit = @new mysqli('localhost', 'root', '', 'credit_db');

echo "=== Checking das_db ===\n";
$result = $das->query("SELECT COUNT(*) as count FROM guarantors WHERE customer_profile_id = 2026001");
$row = $result->fetch_assoc();
echo "Guarantors in das_db: {$row['count']}\n\n";

if (!$credit->connect_error) {
    echo "=== Checking credit_db ===\n";
    $result = $credit->query("SELECT COUNT(*) as count FROM guarantors WHERE customer_profile_id = 2026001");
    $row = $result->fetch_assoc();
    echo "Guarantors in credit_db: {$row['count']}\n";
    
    if ($row['count'] > 0) {
        echo "\nGuarantor Details:\n";
        $result = $credit->query("SELECT id, full_name_np, full_name, is_co_borrower FROM guarantors WHERE customer_profile_id = 2026001 ORDER BY id");
        while($g = $result->fetch_assoc()) {
            echo "ID: {$g['id']} | Name: " . ($g['full_name_np'] ?: $g['full_name']) . " | Co-Borrower: {$g['is_co_borrower']}\n";
        }
    }
}
