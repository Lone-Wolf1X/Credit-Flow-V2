<?php
/**
 * Debug script to check why General and Security upload rows are not displaying
 */
require_once '../../config/config.php';
require_once '../../includes/functions.php';

requireLogin();

$id = intval($_GET['id'] ?? 0);
$customer = getCustomer($id);

if (!$customer) {
    die("Customer not found");
}

echo "<h2>EDMS Upload Display Diagnostic</h2>";
echo "<hr>";

// Check session and role
echo "<h3>1. Session Information</h3>";
echo "<pre>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "</pre>";

// Check role logic
$edms_role = $_SESSION['role'] ?? 'Maker';
$is_maker = ($edms_role === 'Maker' || $edms_role === 'Admin');
$is_checker = ($edms_role === 'Checker' || $edms_role === 'Admin');

echo "<h3>2. Role Logic</h3>";
echo "<pre>";
echo "EDMS Role: $edms_role\n";
echo "Is Maker: " . ($is_maker ? 'YES' : 'NO') . "\n";
echo "Is Checker: " . ($is_checker ? 'YES' : 'NO') . "\n";
echo "</pre>";

// Check can_edit
$can_edit = $is_maker;

echo "<h3>3. Edit Permission</h3>";
echo "<pre>";
echo "Can Edit: " . ($can_edit ? 'YES' : 'NO') . "\n";
echo "</pre>";

// Check customer status
echo "<h3>4. Customer Information</h3>";
echo "<pre>";
echo "Customer ID: " . $customer['id'] . "\n";
echo "Customer Name: " . $customer['customer_name'] . "\n";
echo "Customer Status: " . $customer['status'] . "\n";
echo "</pre>";

// Check if upload rows should be visible
echo "<h3>5. Upload Row Visibility</h3>";
echo "<pre>";
if ($can_edit) {
    echo "✓ Upload rows SHOULD BE VISIBLE\n";
    echo "  - General Documents tab should show upload row\n";
    echo "  - Security Documents tab should show upload row\n";
} else {
    echo "✗ Upload rows WILL NOT BE VISIBLE\n";
    echo "  Reason: User is not a Maker or Admin\n";
    echo "  Current role: $edms_role\n";
}
echo "</pre>";

// Get documents
function getCustomerDocuments($customer_id, $category, $cap_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT cd.*, u.full_name as uploaded_by_name 
                           FROM customer_documents cd 
                           LEFT JOIN edms_users u ON cd.uploaded_by = u.id 
                           WHERE cd.customer_id = ? AND cd.document_category = ? AND cd.cap_id = ? 
                           ORDER BY cd.uploaded_at DESC");
    $stmt->bind_param("iss", $customer_id, $category, $cap_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$general_docs = getCustomerDocuments($id, 'General', 'General');
$security_docs = getCustomerDocuments($id, 'Security', 'Security');

echo "<h3>6. Existing Documents</h3>";
echo "<pre>";
echo "General Documents: " . count($general_docs) . " found\n";
echo "Security Documents: " . count($security_docs) . " found\n";
echo "</pre>";

echo "<hr>";
echo "<h3>Conclusion</h3>";
if (!$can_edit) {
    echo "<p style='color: red; font-weight: bold;'>⚠️ ISSUE FOUND: User does not have Maker or Admin role!</p>";
    echo "<p>Current role: <strong>$edms_role</strong></p>";
    echo "<p>To fix: Ensure the user is assigned 'Maker' or 'Admin' role in the database.</p>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✓ Permissions are correct. Upload rows should be visible.</p>";
    echo "<p>If upload rows are still not showing, check:</p>";
    echo "<ul>";
    echo "<li>Browser console for JavaScript errors</li>";
    echo "<li>CSS that might be hiding the rows</li>";
    echo "<li>Network tab to ensure page is loading completely</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='customer_profile.php?id=$id'>← Back to Customer Profile</a></p>";
?>
