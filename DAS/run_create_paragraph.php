<?php
$conn = new mysqli('localhost', 'root', '', 'das_db');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$sql = file_get_contents('create_paragraph_table.sql');
if ($conn->query($sql) === TRUE) {
    echo "Table 'doc_paragraphs' created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
