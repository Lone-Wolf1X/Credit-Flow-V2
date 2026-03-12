<?php
$conn = new mysqli('localhost', 'root', '', 'das_db');
echo "=== das_generated_documents columns ===\n";
$result = $conn->query('SHOW COLUMNS FROM das_generated_documents');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}

echo "\n=== generated_documents columns ===\n";
$result = $conn->query('SHOW COLUMNS FROM generated_documents');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
