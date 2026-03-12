<?php
// Migration: Rename is_co_guarantor to is_co_borrower in guarantors table
$conn = new mysqli('localhost', 'root', '', 'das_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if is_co_guarantor exists
$check = $conn->query("SHOW COLUMNS FROM guarantors LIKE 'is_co_guarantor'");
if ($check->num_rows > 0) {
    // Rename
    $sql = "ALTER TABLE guarantors CHANGE COLUMN is_co_guarantor is_co_borrower TINYINT(1) DEFAULT 0";
    if ($conn->query($sql) === TRUE) {
        echo "Successfully renamed 'is_co_guarantor' to 'is_co_borrower'.\n";
    } else {
        echo "Error renaming column: " . $conn->error . "\n";
    }
} else {
    // Check if is_co_borrower already exists
    $check2 = $conn->query("SHOW COLUMNS FROM guarantors LIKE 'is_co_borrower'");
    if ($check2->num_rows > 0) {
        echo "Column 'is_co_borrower' already exists.\n";
    } else {
        // Create it fresh if neither exists (fallback)
        $sql = "ALTER TABLE guarantors ADD COLUMN is_co_borrower TINYINT(1) DEFAULT 0";
        if ($conn->query($sql) === TRUE) {
            echo "Successfully added 'is_co_borrower' column.\n";
        } else {
            echo "Error adding column: " . $conn->error . "\n";
        }
    }
}

$conn->close();
?>
