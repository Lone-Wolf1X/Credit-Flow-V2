<?php
$conn = new mysqli('localhost', 'root', '', 'das_db');

echo "Guarantors for Profile 2026001:\n";
$result = $conn->query("SELECT id, full_name_np, full_name, is_co_borrower FROM guarantors WHERE customer_profile_id = 2026001 ORDER BY id");

$count = 0;
while($row = $result->fetch_assoc()) {
    $count++;
    echo "$count. ID: {$row['id']} | Name: " . ($row['full_name_np'] ?: $row['full_name']) . " | Co-Borrower: {$row['is_co_borrower']}\n";
}

echo "\nTotal Guarantors: $count\n";
