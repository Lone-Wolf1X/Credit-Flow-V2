<?php
require_once '../../config/config.php';

// Create Table
$sql = "CREATE TABLE IF NOT EXISTS edms_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    designation VARCHAR(100) DEFAULT 'Staff',
    role ENUM('Maker', 'Checker', 'Admin') NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'edms_users' created successfully.<br>";
} else {
    die("Error creating table: " . $conn->error);
}

// Seed Users
$users = [
    ['maker1', '123', 'John Maker', 'Credit Officer', 'Maker'],
    ['checker1', '123', 'Jane Checker', 'Legal Officer', 'Checker'],
    ['admin1', '123', 'Super Admin', 'System Admin', 'Admin']
];

foreach ($users as $u) {
    $username = $u[0];
    $password = password_hash($u[1], PASSWORD_DEFAULT);
    $full_name = $u[2];
    $designation = $u[3];
    $role = $u[4];

    // Check if exists
    $check = $conn->query("SELECT id FROM edms_users WHERE username = '$username'");
    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO edms_users (username, password, full_name, designation, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $password, $full_name, $designation, $role);
        if ($stmt->execute()) {
            echo "User '$username' ($role) created.<br>";
        } else {
            echo "Error creating '$username': " . $stmt->error . "<br>";
        }
    } else {
        echo "User '$username' already exists.<br>";
    }
}
?>
