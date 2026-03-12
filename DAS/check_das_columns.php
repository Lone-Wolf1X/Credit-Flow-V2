<?php
$conn = new mysqli('localhost', 'root', '', 'das_db');
echo "--- das_generated_documents Columns ---\n";
$res = $conn->query("SHOW COLUMNS FROM das_generated_documents");
while ($row = $res->fetch_assoc()) {
    echo "  {$row['Field']} ({$row['Type']})\n";
}
?>
