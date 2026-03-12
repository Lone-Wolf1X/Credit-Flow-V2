<?php
require_once 'config/config.php';
$conn = new mysqli('localhost', 'root', '', 'das_db');

echo "=== AVAILABLE TEMPLATES ===\n";
$res = $conn->query("SELECT * FROM templates");
$mortgage_template_id = null;

while ($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['template_name'] . " | Path: " . $row['file_path'] . "\n";
    // Match 'mortgage_deed' in path
    if (strpos($row['file_path'], 'mortgage_deed') !== false) {
        $mortgage_template_id = $row['id'];
        break; // Found one, use it
    }
}

if ($mortgage_template_id) {
    echo "\nFound Mortgage Deed Template ID: $mortgage_template_id\n";
    $scheme_id = 8;
    
    // Check if link exists
    $check = $conn->query("SELECT * FROM loan_scheme_templates WHERE scheme_id = $scheme_id AND template_id = $mortgage_template_id");
    if ($check->num_rows == 0) {
        echo "Link missing. Creating link...\n";
        $stmt = $conn->prepare("INSERT INTO loan_scheme_templates (scheme_id, template_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $scheme_id, $mortgage_template_id);
        if ($stmt->execute()) {
            echo "SUCCESS: Template linked to Scheme 8.\n";
        } else {
            echo "ERROR: Failed to link template: " . $conn->error . "\n";
        }
    } else {
        echo "Link already exists.\n";
    }
    
    // Reset Profile Status
    $profile_id = 2;
    echo "Resetting Profile $profile_id to 'Pending'...\n";
    $conn->query("UPDATE customer_profiles SET status='Pending' WHERE id=$profile_id");
    echo "Profile Status Reset.\n";
    
} else {
    echo "\nERROR: Mortgage Deed template not found in database.\n";
}
