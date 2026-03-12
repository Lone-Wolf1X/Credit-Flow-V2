<?php
$conn = new mysqli('localhost', 'root', '', 'das_db');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$result = $conn->query("SHOW TABLES LIKE 'doc_paragraphs'");
if ($result->num_rows > 0) {
    echo "Table 'doc_paragraphs' exists.";
    $res = $conn->query("DESCRIBE doc_paragraphs");
    while($row = $res->fetch_assoc()) {
        echo "\nField: " . $row['Field'] . " - Type: " . $row['Type'];
    }
} else {
    echo "Table 'doc_paragraphs' does not exist.";
}
?>
