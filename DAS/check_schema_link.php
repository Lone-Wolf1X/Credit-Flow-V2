<?php
require_once 'config/config.php';
$conn = new mysqli('localhost', 'root', '', 'das_db');

echo "=== LOAN DETAILS COLUMNS ===\n";
$r = $conn->query("SHOW COLUMNS FROM loan_details");
while ($row = $r->fetch_assoc()) {
    echo $row['Field'] . "\n";
}

echo "\n=== CHECKING LINK FOR PROFILE 2 ===\n";
// Try to fetch scheme_id (guessing column name based on list above)
// I can't guess until I run it, so I'll fetch *
$res = $conn->query("SELECT * FROM loan_details WHERE customer_profile_id = 2");
if ($row = $res->fetch_assoc()) {
    print_r($row);
    
    // Assuming 'loan_scheme' holds the name or 'scheme_id' holds the ID
    $scheme_name_or_id = $row['loan_scheme'] ?? $row['scheme_id'] ?? null;
    
    if ($scheme_name_or_id) {
        echo "Found Scheme Identifier: $scheme_name_or_id\n";
        
        // Check if it maps to a scheme ID in loan_schemes
        $s = $conn->query("SELECT id FROM loan_schemes WHERE scheme_name = '$scheme_name_or_id' OR id = '$scheme_name_or_id'");
        if ($s_row = $s->fetch_assoc()) {
            $real_scheme_id = $s_row['id'];
            echo "Real Scheme ID: $real_scheme_id\n";
            
            // Check templates
            $t = $conn->query("SELECT * FROM loan_scheme_templates WHERE scheme_id = $real_scheme_id");
            echo "Linked Templates: " . $t->num_rows . "\n";
            while($tr = $t->fetch_assoc()){
                print_r($tr);
            }
        } else {
            echo "Scheme not found in loan_schemes table.\n";
        }
    }
} else {
    echo "Profile 2 loan details not found.\n";
}
