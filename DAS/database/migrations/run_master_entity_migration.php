<?php
require_once __DIR__ . '/../../config/config.php';

$conn = new mysqli('localhost', 'root', '', 'das_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = file_get_contents(__DIR__ . '/create_master_entities.sql');

if ($conn->multi_query($sql)) {
    echo "Migration executed successfully.\n";
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
} else {
    echo "Error executing migration: " . $conn->error;
}

$conn->close();
?>
