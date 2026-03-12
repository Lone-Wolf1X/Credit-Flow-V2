<?php
require_once 'config/config.php';

$sql = "ALTER TABLE customer_documents ADD COLUMN is_draft TINYINT(1) DEFAULT 1";

if ($conn->query($sql) === TRUE) {
    echo "Column is_draft added successfully";
} else {
    echo "Error adding column: " . $conn->error;
}
?>
