<?php
// Quick script to find valid profile and template IDs
$conn = new mysqli('localhost', 'root', '', 'das_db');

echo "=== Available Profiles ===\n";
$profiles = $conn->query("SELECT id, profile_id, created_at, status FROM customer_profiles ORDER BY id DESC LIMIT 5");
while ($p = $profiles->fetch_assoc()) {
    echo "Profile ID: {$p['id']} | Profile#: {$p['profile_id']} | Status: {$p['status']}\n";
}

echo "\n=== Available Templates ===\n";
$templates = $conn->query("SELECT id, template_name, template_code FROM templates ORDER BY id DESC LIMIT 5");
while ($t = $templates->fetch_assoc()) {
    echo "Template ID: {$t['id']} | Name: {$t['template_name']} | Code: {$t['template_code']}\n";
}

echo "\n=== Test URLs ===\n";
$profile = $conn->query("SELECT id FROM customer_profiles LIMIT 1")->fetch_assoc();
$template = $conn->query("SELECT id FROM templates LIMIT 1")->fetch_assoc();

if ($profile && $template) {
    $url = "http://localhost/Credit/DAS/generate_test.php?profile_id={$profile['id']}&template_id={$template['id']}";
    echo "Copy this URL to browser:\n$url\n";
}

$conn->close();
