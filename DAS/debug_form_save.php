<?php
/**
 * Debug Form Save Issues
 * This script helps diagnose why forms are not saving in DAS 1
 */

session_start();
require_once 'config/config.php';

echo "<h1>DAS 1 Form Save Debug</h1>";
echo "<hr>";

// 1. Check Database Connection
echo "<h2>1. Database Connection</h2>";
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection FAILED: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    echo "<p>Database: das_db</p>";
}

// 2. Check Session Status
echo "<h2>2. Session Status</h2>";
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    echo "<p style='color: green;'>✅ User is logged in</p>";
    echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'N/A') . "</p>";
    echo "<p>Staff ID: " . ($_SESSION['staff_id'] ?? 'N/A') . "</p>";
    echo "<p>Full Name: " . ($_SESSION['full_name'] ?? 'N/A') . "</p>";
    echo "<p>Role: " . ($_SESSION['role_name'] ?? 'N/A') . "</p>";
} else {
    echo "<p style='color: red;'>❌ User is NOT logged in</p>";
    echo "<p>Please <a href='login.php'>login</a> first</p>";
}

// 3. Check Tables Exist
echo "<h2>3. Database Tables</h2>";
$tables = ['customer_profiles', 'borrowers', 'guarantors', 'collateral', 'loan_details', 'limit_details'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Table '$table' NOT found</p>";
    }
}

// 4. Check Recent Customer Profiles
echo "<h2>4. Recent Customer Profiles</h2>";
$result = $conn->query("SELECT id, customer_id, full_name, status, created_at FROM customer_profiles ORDER BY created_at DESC LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Customer ID</th><th>Name</th><th>Status</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['customer_id'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No customer profiles found</p>";
}

// 5. Check Error Logs
echo "<h2>5. Recent Error Logs</h2>";
$log_file = __DIR__ . '/FATAL_ERROR_LOG.txt';
if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    $recent_logs = array_slice(explode("\n", $logs), -20);
    echo "<pre>" . htmlspecialchars(implode("\n", $recent_logs)) . "</pre>";
} else {
    echo "<p>No fatal error log found</p>";
}

// 6. Test API Endpoint
echo "<h2>6. API Endpoint Test</h2>";
echo "<p>API endpoint: /Credit/DAS/modules/api/customer_api.php</p>";
echo "<p>To test form saving:</p>";
echo "<ol>";
echo "<li>Open browser developer tools (F12)</li>";
echo "<li>Go to Network tab</li>";
echo "<li>Navigate to a customer profile</li>";
echo "<li>Try to save a borrower/guarantor form</li>";
echo "<li>Check the request/response in Network tab</li>";
echo "</ol>";

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ul>";
echo "<li>If database connection failed, check XAMPP MySQL service</li>";
echo "<li>If user not logged in, login at <a href='login.php'>login page</a></li>";
echo "<li>If tables missing, run database migration scripts</li>";
echo "<li>Check browser console for JavaScript errors</li>";
echo "<li>Check Network tab for API response errors</li>";
echo "</ul>";
?>
