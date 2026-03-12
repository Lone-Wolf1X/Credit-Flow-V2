<?php
$conn = new mysqli('localhost', 'root', '', 'das_db');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$result = $conn->query("SHOW COLUMNS FROM authorized_persons");
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Table authorized_persons not found or error: " . $conn->error;
}
?>
