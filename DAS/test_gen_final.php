<?php
require_once 'c:/xampp/htdocs/Credit/DAS/config/config.php';
require_once 'c:/xampp/htdocs/Credit/DAS/includes/document_generation.php';

// Mock session
$_SESSION['user_id'] = 1;

$profile_id = 5;
$template_id = 18;

echo "Starting generation test for Profile $profile_id, Template $template_id...\n";

// Force Approved status
$conn->query("UPDATE customer_profiles SET status = 'Approved' WHERE id = $profile_id");

// Mock removed - using real implementation
try {
    $result = generateDocument($profile_id, $template_id);
    print_r($result);
} catch (Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>
