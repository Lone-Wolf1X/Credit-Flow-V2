<?php
$conn = new mysqli('localhost', 'root', '', 'das_db');

// Check if profile exists
echo "=== Profile Check ===\n";
$result = $conn->query("SELECT id, customer_id, profile_number, status FROM customer_profiles WHERE id = 2026001");
if ($row = $result->fetch_assoc()) {
    echo "Profile Found: ID={$row['id']}, Customer={$row['customer_id']}, Number={$row['profile_number']}, Status={$row['status']}\n\n";
} else {
    echo "Profile 2026001 NOT FOUND!\n\n";
}

// Check guarantors
echo "=== Guarantors ===\n";
$result = $conn->query("SELECT id, full_name_np, full_name, is_co_borrower FROM guarantors WHERE customer_profile_id = 2026001 ORDER BY id");
$count = 0;
while($row = $result->fetch_assoc()) {
    $count++;
    $name = $row['full_name_np'] ?: $row['full_name'] ?: 'N/A';
    echo "$count. ID: {$row['id']} | Name: $name | Co-Borrower: {$row['is_co_borrower']}\n";
}
echo "Total: $count guarantors\n\n";

// Check generated documents
echo "=== Generated Documents (Guarantor/POA) ===\n";
$result = $conn->query("SELECT id, file_path, created_at FROM generated_documents WHERE customer_profile_id = 2026001 AND (file_path LIKE '%Personal%' OR file_path LIKE '%Attorney%' OR file_path LIKE '%POA%' OR file_path LIKE '%Guarantor%') ORDER BY created_at DESC");
$count = 0;
while($row = $result->fetch_assoc()) {
    $count++;
    echo "$count. ID: {$row['id']} | File: " . basename($row['file_path']) . " | Created: {$row['created_at']}\n";
}
echo "Total: $count documents\n";
