<?php
// Migration: Add is_co_guarantor column to guarantors table
$conn = new mysqli('localhost', 'root', '', 'das_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Check if column exists
$result = $conn->query("SHOW COLUMNS FROM guarantors LIKE 'is_co_guarantor'");

if ($result->num_rows == 0) {
    // 2. Add column if missing
    $sql = "ALTER TABLE guarantors ADD COLUMN is_co_guarantor TINYINT(1) DEFAULT 0 AFTER reissue_count";
    // If reissue_count doesn't exist, just add at end
    // Let's just add at the end to be safe or check another known column
    
    // Safer approach: Add after 'updated_at' or simply add it
    $sql = "ALTER TABLE guarantors ADD COLUMN is_co_guarantor TINYINT(1) DEFAULT 0";
    
    if ($conn->query($sql) === TRUE) {
        echo "Successfully added 'is_co_guarantor' column to 'guarantors' table.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "Column 'is_co_guarantor' already exists.\n";
}

$conn->close();
?>
