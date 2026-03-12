<?php
$conn = new mysqli('localhost', 'root', '', 'das_db');
$result = $conn->query('DESCRIBE borrowers');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}
?>
