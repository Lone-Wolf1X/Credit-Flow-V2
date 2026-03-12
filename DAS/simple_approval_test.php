<?php
/**
 * Simple Direct Approval Test
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli('localhost', 'root', '', 'das_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== SIMPLE APPROVAL TEST ===\n\n";

$profile_id = 5;
$user_id = 1;

// Step 1: Check current status
echo "1. Checking Profile #$profile_id current status...\n";
$result = $conn->query("SELECT id, customer_id, full_name, status FROM customer_profiles WHERE id = $profile_id");
$profile = $result->fetch_assoc();
echo "   Status: {$profile['status']}\n";
echo "   Customer: {$profile['customer_id']} - {$profile['full_name']}\n\n";

// Step 2: Check for loan details
echo "2. Checking loan details...\n";
$result = $conn->query("SELECT id, scheme_id FROM loan_details WHERE customer_profile_id = $profile_id");
if ($result->num_rows > 0) {
    $loan = $result->fetch_assoc();
    $scheme_id = $loan['scheme_id'];
    echo "   ✓ Loan details found - Scheme ID: $scheme_id\n\n";
} else {
    die("   ✗ No loan details found!\n");
}

// Step 3: Check for templates
echo "3. Checking templates for scheme $scheme_id...\n";
$result = $conn->query("SELECT id, template_name FROM templates WHERE scheme_id = $scheme_id");
if ($result->num_rows > 0) {
    echo "   ✓ Templates found:\n";
    $templates = [];
    while ($tpl = $result->fetch_assoc()) {
        $templates[] = $tpl;
        echo "      - Template #{$tpl['id']}: {$tpl['template_name']}\n";
    }
    echo "\n";
} else {
    echo "   ⚠ No templates found for this scheme\n\n";
    $templates = [];
}

// Step 4: Try to update profile status
echo "4. Attempting to update profile status to 'Approved'...\n";
$stmt = $conn->prepare("UPDATE customer_profiles SET status = 'Approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
$stmt->bind_param("ii", $user_id, $profile_id);

if ($stmt->execute()) {
    echo "   ✓ Profile status updated successfully!\n";
    echo "   Affected rows: " . $stmt->affected_rows . "\n\n";
} else {
    echo "   ✗ Failed to update profile status\n";
    echo "   Error: " . $stmt->error . "\n\n";
    die();
}

// Step 5: Verify update
echo "5. Verifying update...\n";
$result = $conn->query("SELECT status, approved_by, approved_at FROM customer_profiles WHERE id = $profile_id");
$profile = $result->fetch_assoc();
echo "   Status: {$profile['status']}\n";
echo "   Approved By: {$profile['approved_by']}\n";
echo "   Approved At: {$profile['approved_at']}\n\n";

// Step 6: Check generated_documents table structure
echo "6. Checking generated_documents table...\n";
$result = $conn->query("SHOW COLUMNS FROM generated_documents");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}
echo "   Columns: " . implode(", ", $columns) . "\n\n";

echo "=== TEST COMPLETE ===\n";
echo "\nNEXT STEPS:\n";
echo "- Profile #$profile_id is now marked as Approved\n";
echo "- Document generation needs to be tested separately\n";
echo "- Check why approval API was failing\n";

$conn->close();
?>
