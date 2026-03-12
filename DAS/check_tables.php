<?php
$conn = new mysqli('localhost', 'root', '', 'das_db');
$tables = ['generated_documents', 'das_generated_documents'];
foreach ($tables as $table) {
    echo "--- $table Columns ---\n";
    $res = $conn->query("SHOW COLUMNS FROM $table");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo "  {$row['Field']} ({$row['Type']})\n";
        }
    } else {
        echo "  Table does not exist or error.\n";
    }
    echo "\n";
}
?>
