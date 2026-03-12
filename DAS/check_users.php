<?php
require_once 'config/config.php';
$conn = new mysqli('localhost', 'root', '', 'das_db');

echo "=== USERS TABLE ===\n";
$result = $conn->query("SELECT id, full_name, role_id FROM users LIMIT 5");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
