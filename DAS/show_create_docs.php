<?php
require_once 'config/config.php';
$conn = new mysqli('localhost', 'root', '', 'das_db');
$result = $conn->query("SHOW CREATE TABLE generated_documents");
echo $result->fetch_row()[1];
