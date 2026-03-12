<?php
require_once 'config/config.php';
$conn = new mysqli('localhost', 'root', '', 'das_db');
$result = $conn->query("SELECT id, scheme_name, template_folder_path FROM loan_schemes");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Scheme: " . $row['scheme_name'] . " | Path: " . $row['template_folder_path'] . "\n";
}
