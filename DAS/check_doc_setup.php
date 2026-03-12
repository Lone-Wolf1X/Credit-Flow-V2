<?php
require_once 'C:/xampp/htdocs/Credit/DAS/config/config.php';

echo "=== CHECKING DOCUMENT GENERATION SETUP ===\n\n";

// 1. Check templates
echo "1. TEMPLATES IN DATABASE:\n";
$result = $conn->query("SELECT * FROM templates");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "  - ID: {$row['id']}, Name: " . ($row['template_name'] ?? 'N/A') . "\n";
        echo "    Scheme ID: " . ($row['scheme_id'] ?? 'NULL') . "\n";
        echo "    Columns: " . implode(', ', array_keys($row)) . "\n\n";
    }
} else {
    echo "  ✗ NO TEMPLATES FOUND\n";
}

// 2. Check loan schemes
echo "\n2. LOAN SCHEMES:\n";
$result = $conn->query("SELECT id, scheme_name, scheme_code FROM loan_schemes");
while($row = $result->fetch_assoc()) {
    echo "  - ID: {$row['id']}, Name: {$row['scheme_name']}, Code: {$row['scheme_code']}\n";
}

// 3. Check profile 1's loan details
echo "\n3. PROFILE 1 LOAN DETAILS:\n";
$result = $conn->query("SELECT ld.*, ls.scheme_name FROM loan_details ld LEFT JOIN loan_schemes ls ON ld.scheme_id = ls.id WHERE ld.customer_profile_id = 1");
if ($result->num_rows > 0) {
    $loan = $result->fetch_assoc();
    echo "  - Scheme ID: {$loan['scheme_id']}\n";
    echo "  - Scheme Name: {$loan['scheme_name']}\n";
    echo "  - Loan Type: {$loan['loan_type']}\n";
} else {
    echo "  ✗ NO LOAN DETAILS FOR PROFILE 1\n";
}

// 4. Check if templates exist for this scheme
echo "\n4. TEMPLATES FOR THIS SCHEME:\n";
if (isset($loan['scheme_id'])) {
    $result = $conn->query("SELECT id, template_name FROM templates WHERE scheme_id = {$loan['scheme_id']}");
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "  ✓ Template ID: {$row['id']}, Name: {$row['template_name']}\n";
        }
    } else {
        echo "  ✗ NO TEMPLATES LINKED TO SCHEME ID {$loan['scheme_id']}\n";
    }
}

// 5. Check profile status
echo "\n5. PROFILE 1 STATUS:\n";
$result = $conn->query("SELECT id, customer_id, status FROM customer_profiles WHERE id = 1");
$profile = $result->fetch_assoc();
echo "  - Customer ID: {$profile['customer_id']}\n";
echo "  - Status: {$profile['status']}\n";

echo "\n=== END OF CHECK ===\n";
?>
