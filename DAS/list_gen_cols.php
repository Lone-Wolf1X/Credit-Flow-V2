<?php
$conn = new mysqli('localhost', 'root', '', 'das_db');
$res = $conn->query("SHOW COLUMNS FROM generated_documents");
while ($row = $res->fetch_assoc()) {
    echo "{$row['Field']}\n";
}
?>
