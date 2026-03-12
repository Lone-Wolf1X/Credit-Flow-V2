<?php
require_once 'config/config.php';

// Add status and reviewed_at columns to application_reviewers table
$sql = "
ALTER TABLE application_reviewers 
ADD COLUMN IF NOT EXISTS status ENUM('Pending', 'Reviewed') DEFAULT 'Pending',
ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP NULL;
";

if ($conn->query($sql) === TRUE) {
    echo "Database updated successfully: Added status and reviewed_at to application_reviewers table.";
} else {
    echo "Error updating database: " . $conn->error;
}
?>