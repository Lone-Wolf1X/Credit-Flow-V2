<?php
// debug_das_docs.php
require_once 'config/config.php';

$query = "
    SELECT 
        cp.id as profile_id,
        cp.customer_id,
        cp.full_name,
        cp.approved_at,
        COUNT(pd.id) as doc_count,
        u.full_name as approved_by_name
    FROM customer_profiles cp
    JOIN generated_documents pd ON cp.id = pd.customer_profile_id
    LEFT JOIN users u ON cp.approved_by = u.id
    WHERE cp.status = 'Approved'
    GROUP BY cp.id
    ORDER BY cp.approved_at DESC
";

$result = $conn->query($query);

if (!$result) {
    echo "Query Failed: " . $conn->error . "\n";
    exit;
}

echo "Query Successful. Rows: " . $result->num_rows . "\n";
echo "Columns per row expected: 6 (ID, Name, Count, Approver, Date, Action)\n\n";

$i = 0;
while ($row = $result->fetch_assoc()) {
    $i++;
    echo "Row $i:\n";
    echo " - profile_id: " . $row['profile_id'] . "\n";
    echo " - customer_id: " . $row['customer_id'] . "\n";
    echo " - full_name: " . $row['full_name'] . "\n";
    echo " - doc_count: " . $row['doc_count'] . "\n";
    echo " - approved_by: " . ($row['approved_by_name'] ?? 'NULL') . "\n";
    echo " - approved_at: " . $row['approved_at'] . "\n";
    echo "--------------------------\n";
}
?>
