<?php
$conn = new mysqli('localhost', 'root', '', 'das_db');
$result = $conn->query('SHOW COLUMNS FROM generated_documents');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
