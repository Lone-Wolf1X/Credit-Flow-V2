<?php
$conn = new mysqli('localhost', 'root', '', 'das_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Create legal_heirs table
$sql_create = "CREATE TABLE IF NOT EXISTS legal_heirs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_profile_id INT NOT NULL,
    collateral_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    relation VARCHAR(100),
    father_name VARCHAR(255),
    grandfather_name VARCHAR(255),
    date_of_birth VARCHAR(50),
    citizenship_no VARCHAR(100),
    citizenship_issue_date VARCHAR(50),
    citizenship_issue_district VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (customer_profile_id),
    INDEX (collateral_id)
)";

if ($conn->query($sql_create) === TRUE) {
    echo "Table 'legal_heirs' created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// 2. Add is_legal_heir_applicable to collateral table
// Check if column exists first
$check_col = $conn->query("SHOW COLUMNS FROM collateral LIKE 'is_legal_heir_applicable'");
if ($check_col->num_rows == 0) {
    $sql_alter = "ALTER TABLE collateral ADD COLUMN is_legal_heir_applicable TINYINT(1) DEFAULT 0 AFTER owner_type";
    if ($conn->query($sql_alter) === TRUE) {
        echo "Column 'is_legal_heir_applicable' added to 'collateral' table.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "Column 'is_legal_heir_applicable' already exists.\n";
}

$conn->close();
