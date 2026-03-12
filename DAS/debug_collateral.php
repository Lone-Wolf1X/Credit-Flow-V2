<?php
require_once 'config/config.php';
require_once 'includes/DocumentDataFetcher.php';

// Assuming profile_id is 1 based on context, or getting from GET
$profile_id = $_GET['id'] ?? 1;

$conn = new mysqli('localhost', 'root', '', 'das_db');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

echo "DEBUGGING PROFILE ID: $profile_id\n";

$fetcher = new DocumentDataFetcher($conn, $profile_id);
$data = $fetcher->fetchAll();

echo "\n--- COLLATERAL DATA ---\n";
if (empty($data['collateral'])) {
    echo "NO COLLATERAL DATA FOUND!\n";
} else {
    print_r($data['collateral']);
}

echo "\n--- BORROWER DATA (For Verification) ---\n";
print_r($data['borrowers']);
?>
