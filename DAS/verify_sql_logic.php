<?php
$conn = new mysqli('localhost', 'root', '', 'das_db');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$profile_id = 1; // Rameshwar Prasad Sah
$latest_only = true;

echo "--- Verifying Document Query Logic ---\n";

$sql = "
    SELECT 
        gd.*,
        u.full_name as generated_by_name
    FROM generated_documents gd
    LEFT JOIN users u ON gd.generated_by = u.id
    WHERE gd.customer_profile_id = ? AND gd.is_active = TRUE
";

if ($latest_only) {
    $sql .= " AND (gd.batch_id <=> (
        SELECT batch_id FROM generated_documents 
        WHERE customer_profile_id = ? AND is_active = TRUE 
        ORDER BY generated_at DESC LIMIT 1
    ))";
}

$sql .= " ORDER BY gd.generated_at DESC";

$stmt = $conn->prepare($sql);
if ($latest_only) {
    $stmt->bind_param("ii", $profile_id, $profile_id);
} else {
    $stmt->bind_param("i", $profile_id);
}

$stmt->execute();
$documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "Found " . count($documents) . " documents for Profile ID $profile_id\n";
foreach ($documents as $doc) {
    // Template resolution logic
    if (empty($doc['template_name']) && !empty($doc['template_snapshot'])) {
        $snapshot = json_decode($doc['template_snapshot'], true);
        $doc['template_name'] = $snapshot['template_name'] ?? 'Unknown';
    }
    if (empty($doc['template_name'])) {
        $doc['template_name'] = 'Generated Document';
    }
    
    echo "  - [" . $doc['id'] . "] " . $doc['template_name'] . " (Path: " . $doc['file_path'] . ")\n";
}

if (count($documents) > 0) {
    echo "\n✅ SUCCESS: Document retrieval using unified table is working.\n";
} else {
    echo "\n⚠️ WARNING: No documents found (ensure data exists in generated_documents for ID $profile_id).\n";
}
?>
