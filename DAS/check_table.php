<?php
require_once 'config/config.php';

echo "=== generated_documents table structure ===\n";
$result = $conn->query('DESCRIBE generated_documents');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
