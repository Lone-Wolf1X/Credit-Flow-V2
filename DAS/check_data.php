<?php
// Quick test to check loan schemes and templates
$conn = new mysqli('localhost', 'root', '', 'das_db');

echo "<h2>Loan Schemes</h2>";
$schemes = $conn->query("SELECT id, scheme_name, scheme_code, template_folder_path FROM loan_schemes LIMIT 5");
while ($row = $schemes->fetch_assoc()) {
    echo "ID: {$row['id']} - {$row['scheme_name']} ({$row['scheme_code']}) - Folder: {$row['template_folder_path']}<br>";
}

echo "<h2>Templates</h2>";
$templates = $conn->query("SELECT id, template_name, file_path, scheme_id FROM templates LIMIT 10");
while ($row = $templates->fetch_assoc()) {
    echo "ID: {$row['id']} - {$row['template_name']} - Scheme: {$row['scheme_id']} - Path: {$row['file_path']}<br>";
}

echo "<h2>Customer Profiles</h2>";
$profiles = $conn->query("SELECT id, status FROM customer_profiles LIMIT 5");
while ($row = $profiles->fetch_assoc()) {
    echo "ID: {$row['id']} - Status: {$row['status']}<br>";
}

echo "<h2>Borrowers</h2>";
$borrowers = $conn->query("SELECT id, customer_profile_id, full_name_en FROM borrowers LIMIT 5");
while ($row = $borrowers->fetch_assoc()) {
    echo "ID: {$row['id']} - Profile: {$row['customer_profile_id']} - Name: {$row['full_name_en']}<br>";
}
?>
