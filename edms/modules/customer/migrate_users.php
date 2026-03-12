<?php
require_once '../../config/config.php';

// 1. Drop and Re-create table to match Credit Flow structure but with EDMS roles
$conn->query("DROP TABLE IF EXISTS edms_users");

$sql_create = "CREATE TABLE edms_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    designation VARCHAR(100),
    role ENUM('Maker', 'Checker', 'Admin') NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_create) === TRUE) {
    echo "Table 'edms_users' re-created successfully.<br>";
} else {
    die("Error creating table: " . $conn->error);
}

// 2. Fetch users from Credit Flow DB
// Assuming $cf_conn is available or we connect manually
$cf_db = 'credit_flow_db'; // Adjust if defined constant
$cf_users_result = $conn->query("SELECT * FROM $cf_db.users");

if (!$cf_users_result) {
    // Try using a separate connection if cross-db query fails
    // But usually localized xampp setup allows it.
    echo "Failed to fetch from $cf_db.users: " . $conn->error . "<br>";
    echo " attempting to use 'users' table from current connection if mapped...<br>";
    // Fallback logic not needed if permission grants, let's assume it works or fail.
}

if ($cf_users_result && $cf_users_result->num_rows > 0) {
    $count = 0;
    while ($row = $cf_users_result->fetch_assoc()) {
        $staff_id = $row['staff_id'];
        $password = $row['password']; // Copy hashed password directly
        $full_name = $row['full_name'];
        $email = $row['email'];
        $designation = $row['designation'];
        $cf_role = $row['role'];
        
        // Map Role
        $edms_role = 'Maker'; // Default
        if ($cf_role === 'Initiator') $edms_role = 'Maker';
        elseif ($cf_role === 'Reviewer') $edms_role = 'Checker';
        elseif ($cf_role === 'Approver') $edms_role = 'Checker'; // Approver -> Checker
        elseif ($cf_role === 'Admin') $edms_role = 'Admin';
        
        // Insert
        $stmt = $conn->prepare("INSERT INTO edms_users (staff_id, password, full_name, email, designation, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $staff_id, $password, $full_name, $email, $designation, $edms_role);
        
        if ($stmt->execute()) {
            echo "Imported: $staff_id ($full_name) as $edms_role<br>";
            $count++;
        } else {
            echo "Failed to import $staff_id: " . $stmt->error . "<br>";
        }
    }
    echo "Migration Complete. Imported $count users.<br>";
} else {
    echo "No users found in credit_flow_db.users or Error.<br>";
}
?>
