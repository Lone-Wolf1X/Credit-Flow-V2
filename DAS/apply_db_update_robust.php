<?php
require_once 'config/config.php';

$sql_file = 'update_branch_system.sql';
if (!file_exists($sql_file)) {
    die("Error: SQL file not found.");
}

$sql = file_get_contents($sql_file);

// Split by semicolon, but handle cases where semicolon is inside quotes if needed (simple split works for this file)
$queries = explode(';', $sql);

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        try {
            if ($conn->query($query) === TRUE) {
                echo "Success: " . substr($query, 0, 50) . "...\n";
            } else {
                // Ignore "duplicate column" errors for ALTER TABLE
                if (stripos($conn->error, "Duplicate column") !== false) {
                    echo "Skipped (Duplicate Column): " . substr($query, 0, 50) . "...\n";
                } elseif (stripos($conn->error, "Table") !== false && stripos($conn->error, "already exists") !== false) {
                    echo "Skipped (Table Exists): " . substr($query, 0, 50) . "...\n";
                } else {
                    echo "Error executing query: " . $conn->error . "\nFull Query: $query\n";
                }
            }
        } catch (Exception $e) {
            echo "Exception: " . $e->getMessage() . "\n";
        }
    }
}
echo "Database Update Routine Completed.\n";
?>
