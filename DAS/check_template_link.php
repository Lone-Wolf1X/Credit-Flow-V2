<?php
require_once 'config/config.php';
$profile_id = 2;
$conn = new mysqli('localhost', 'root', '', 'das_db');

echo "=== CHECKING SCHEME AND TEMPLATE LINKS ===\n";

// 1. Get Scheme ID
$res = $conn->query("SELECT loan_scheme_id FROM loan_details WHERE customer_profile_id = $profile_id");
if ($row = $res->fetch_assoc()) {
    $scheme_id = $row['loan_scheme_id'];
    echo "Scheme ID: $scheme_id\n";
    
    // 2. Check Linked Templates
    $sql = "
        SELECT t.id, t.template_name, t.file_path, t.is_active 
        FROM templates t
        JOIN loan_scheme_templates lst ON t.id = lst.template_id
        WHERE lst.scheme_id = $scheme_id
    ";
    $res = $conn->query($sql);
    echo "Linked Templates Found: " . $res->num_rows . "\n";
    while ($t = $res->fetch_assoc()) {
        print_r($t);
    }
} else {
    echo "No loan details found for profile $profile_id\n";
}
