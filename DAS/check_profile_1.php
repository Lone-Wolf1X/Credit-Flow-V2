<?php
$conn = new mysqli('localhost', 'root', '', 'das_db');

echo "=== Profile ID 1 - Guarantors ===\n\n";
$result = $conn->query("SELECT id, full_name_np, full_name, is_co_borrower FROM guarantors WHERE customer_profile_id = 1 ORDER BY id");

$total = 0;
$co_borrowers = 0;
$actual_guarantors = 0;

while($row = $result->fetch_assoc()) {
    $total++;
    $name = $row['full_name_np'] ?: $row['full_name'] ?: 'N/A';
    $is_co = $row['is_co_borrower'] ? 'YES' : 'NO';
    
    echo "$total. ID: {$row['id']} | Name: $name | Is Co-Borrower: $is_co\n";
    
    if ($row['is_co_borrower']) {
        $co_borrowers++;
    } else {
        $actual_guarantors++;
    }
}

echo "\n--- Summary ---\n";
echo "Total Records: $total\n";
echo "Co-Borrowers (is_co_borrower=1): $co_borrowers\n";
echo "Actual Guarantors (is_co_borrower=0 or NULL): $actual_guarantors\n";
echo "\nExpected POA/Guarantor Documents: $actual_guarantors\n";

// Check generated documents
echo "\n=== Generated Guarantor/POA Documents ===\n";
$result = $conn->query("SELECT id, file_path, created_at FROM generated_documents WHERE customer_profile_id = 1 AND (file_path LIKE '%Personal%' OR file_path LIKE '%Attorney%' OR file_path LIKE '%POA%' OR file_path LIKE '%Guarantor%') ORDER BY created_at DESC LIMIT 10");
$doc_count = 0;
while($row = $result->fetch_assoc()) {
    $doc_count++;
    echo "$doc_count. " . basename($row['file_path']) . " (Created: {$row['created_at']})\n";
}
echo "\nTotal Documents Generated: $doc_count\n";
