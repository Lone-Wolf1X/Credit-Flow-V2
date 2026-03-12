<?php
$c = new mysqli('localhost', 'root', '', 'das_db');
if ($c->connect_error) die("Connection failed: " . $c->connect_error);

$sql = "ALTER TABLE generated_documents 
    ADD COLUMN IF NOT EXISTS batch_id VARCHAR(50) DEFAULT NULL AFTER customer_profile_id,
    ADD COLUMN IF NOT EXISTS template_snapshot LONGTEXT DEFAULT NULL AFTER template_name,
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 AFTER status,
    ADD COLUMN IF NOT EXISTS parent_document_id INT(11) DEFAULT NULL AFTER is_active,
    ADD COLUMN IF NOT EXISTS file_name VARCHAR(255) DEFAULT NULL AFTER parent_document_id,
    ADD COLUMN IF NOT EXISTS file_size_kb DECIMAL(10,2) DEFAULT NULL AFTER file_name,
    ADD COLUMN IF NOT EXISTS loan_scheme_id INT(11) DEFAULT NULL AFTER batch_id";

if ($c->query($sql)) {
    echo "Table generated_documents updated successfully\n";
} else {
    echo "Error updating table: " . $c->error . "\n";
}
?>
