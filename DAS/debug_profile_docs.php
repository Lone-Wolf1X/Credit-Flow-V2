<?php
// Debug script to check profile status and generated documents
require_once 'config/config.php';

$search_id = '2025002'; // Treating as string effectively
$conn = new mysqli('localhost', 'root', '', 'das_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== CHECKING PROFILE BY PRIMARY KEY ID ===\n";
$stmt = $conn->prepare("SELECT id, status, customer_id FROM customer_profiles WHERE id = ?");
$stmt->bind_param("i", $search_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

if ($profile) {
    echo "FOUND BY ID!\n";
    echo "Profile ID: " . $profile['id'] . "\n";
    echo "Customer ID: " . $profile['customer_id'] . "\n";
    echo "Status: " . $profile['status'] . "\n";
} else {
    echo "Not found by Primary Key ID.\n";
}

echo "\n=== CHECKING PROFILE BY CUSTOMER_ID STRING ===\n";
$stmt = $conn->prepare("SELECT id, status, customer_id FROM customer_profiles WHERE customer_id = ?");
$stmt->bind_param("s", $search_id);
$stmt->execute();
$result = $stmt->get_result();
$profile_by_cust = $result->fetch_assoc();

$real_profile_id = null;

if ($profile_by_cust) {
    echo "FOUND BY CUSTOMER_ID!\n";
    echo "Profile ID: " . $profile_by_cust['id'] . "\n";
    echo "Customer ID: " . $profile_by_cust['customer_id'] . "\n";
    echo "Status: " . $profile_by_cust['status'] . "\n";
    $real_profile_id = $profile_by_cust['id'];
} else {
    echo "Not found by Customer ID either.\n";
}

if ($real_profile_id) {
    echo "\n=== CHECKING GENERATED DOCUMENTS FOR PROFILE ID $real_profile_id ===\n";
    $stmt = $conn->prepare("SELECT * FROM generated_documents WHERE customer_profile_id = ?");
    $stmt->bind_param("i", $real_profile_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "Doc ID: " . $row['id'] . "\n";
            echo "Template: " . $row['template_name'] . "\n";
            echo "Path: " . $row['file_path'] . "\n";
            echo "Generated At: " . $row['generated_at'] . "\n";
            echo "-------------------\n";
        }
    } else {
        echo "No generated documents found for Profile ID $real_profile_id in database.\n";
    }
}

echo "\n=== LISTING PHYSICAL FILES IN generated_documents/ ===\n";
$dir = __DIR__ . '/generated_documents';
if (is_dir($dir)) {
    $files = scandir($dir);
    echo "Files found: " . count($files) . "\n";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo $file . "\n";
        }
    }
} else {
    echo "Directory not found: $dir\n";
}
