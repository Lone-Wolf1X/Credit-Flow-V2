<?php
// Script to fix missing generated document records
// Fully clean version v2
require_once 'config/config.php';

$conn = new mysqli('localhost', 'root', '', 'das_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Configuration
$target_customer_id = '2025002'; 
$base_dir = __DIR__ . '/generated_documents';

echo "=== FIXING MISSING DOCUMENT RECORDS FOR $target_customer_id ===\n";

// 1. Get Profile Info
$stmt = $conn->prepare("SELECT id, full_name, customer_id FROM customer_profiles WHERE customer_id = ?");
$stmt->bind_param("s", $target_customer_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

if (!$profile) {
    die("Profile with Customer ID $target_customer_id not found in database.\n");
}

$profile_id = $profile['id'];
echo "Found Profile: " . $profile['full_name'] . " (ID: $profile_id)\n";

// 2. Get Scheme ID
$stmt = $conn->prepare("SELECT scheme_id FROM loan_details WHERE customer_profile_id = ? LIMIT 1");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$result = $stmt->get_result();
$loan_row = $result->fetch_assoc();
$scheme_id = $loan_row ? $loan_row['scheme_id'] : 1; 
echo "Using Scheme ID: $scheme_id\n";

// 3. Ensure User Exists in customers table 
$customer_pk = (int)$target_customer_id;

// Check collision on customer_id string (UNIQUE KEY)
$stmt_check = $conn->prepare("SELECT id FROM customers WHERE customer_id = ?");
$stmt_check->bind_param("s", $target_customer_id);
$stmt_check->execute();
$res_check = $stmt_check->get_result();
if ($row_c = $res_check->fetch_assoc()) {
    if ($row_c['id'] != $customer_pk) {
        echo "COLLISION: 'customers' table already has a row with customer_id='$target_customer_id' but ID=" . $row_c['id'] . "\n";
        echo "Deleting colliding row to force ID=$customer_pk...\n";
        $conn->query("SET FOREIGN_KEY_CHECKS=0");
        $conn->query("DELETE FROM customers WHERE id = " . $row_c['id']);
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
    } else {
        echo "Customer record with correct ID and customer_id already exists.\n";
    }
}

// Prepare to insert/update customer
echo "Ensuring customer record exists for ID $customer_pk...\n";

// Get valid user ID
$created_by = 1; 
$user_res = $conn->query("SELECT id FROM users LIMIT 1");
if ($row = $user_res->fetch_assoc()) {
    $created_by = $row['id'];
}
echo "Using generated_by/created_by User ID: $created_by\n";

try {
    $conn->query("SET FOREIGN_KEY_CHECKS=0"); // Disable FK checks globally for this op
    
    // Check if ID exists
    $check_id = $conn->query("SELECT id FROM customers WHERE id = $customer_pk");
    if ($check_id->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO customers (id, customer_id, customer_name, created_by, status) VALUES (?, ?, ?, ?, 'Active')");
        $stmt->bind_param("issi", $customer_pk, $target_customer_id, $profile['full_name'], $created_by);
        if ($stmt->execute()) {
            echo "SUCCESS: Created customer record.\n";
        } else {
             throw new Exception("Insert failed: " . $stmt->error);
        }
    } else {
        echo "Customer ID $customer_pk exists. Updating customer_id to ensure match...\n";
        $conn->query("UPDATE customers SET customer_id = '$target_customer_id' WHERE id = $customer_pk");
    }
    
    $conn->query("SET FOREIGN_KEY_CHECKS=1"); 
} catch (Exception $e) {
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    echo "WARNING: Customer logic failed: " . $e->getMessage() . "\n";
}


// 4. Find the directory
$folder_pattern = $target_customer_id . '_*';
$dirs = glob($base_dir . '/' . $folder_pattern, GLOB_ONLYDIR);

if (empty($dirs)) {
    die("No directory found matching pattern $base_dir/$folder_pattern\n");
}

$target_dir = $dirs[0];
$folder_name = basename($target_dir);
echo "Found Document Folder: $folder_name\n";

// 5. Scan files and insert if missing
$files = scandir($target_dir);
$count = 0;

foreach ($files as $file) {
    if ($file == '.' || $file == '..') continue;
    
    // Check extension
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext != 'docx' && $ext != 'pdf') continue;
    
    $relative_path = 'generated_documents/' . $folder_name . '/' . $file;
    
    // Check if already exists in DB
    $stmt = $conn->prepare("SELECT id FROM generated_documents WHERE file_path = ?");
    $stmt->bind_param("s", $relative_path);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        continue;
    }
    
    // Determine template name
    $name_parts = explode('_', pathinfo($file, PATHINFO_FILENAME));
    array_pop($name_parts); // Remove timestamp
    $template_name = ucwords(implode(' ', $name_parts));
    if (empty($template_name)) $template_name = "Generated Document";

    // Insert
    $doc_num = 'DOC-' . date('YmdHis') . '-' . $count;
    $stmt = $conn->prepare("
        INSERT INTO generated_documents 
        (customer_profile_id, customer_id, scheme_id, template_name, file_path, generated_by, generated_at, document_number, document_name)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
    ");
    $user_id = 1; 
    
    // Disable FK checks for document insert too just in case
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    $stmt->bind_param("iisssiss", $profile_id, $target_customer_id, $scheme_id, $template_name, $relative_path, $user_id, $doc_num, $template_name);
    
    if ($stmt->execute()) {
        echo "ADDED: $file as '$template_name'\n";
        $count++;
    } else {
        echo "ERROR: Failed to add $file: " . $stmt->error . "\n";
    }
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
}

echo "------------------------------------------------\n";
echo "Fixed $count missing document records.\n";
