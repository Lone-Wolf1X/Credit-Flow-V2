<?php
require_once 'config/config.php';
require_once 'includes/DocumentDataFetcher.php';

$profile_id = $_GET['id'] ?? 1;

$conn = new mysqli('localhost', 'root', '', 'das_db');
if ($conn->connect_error) die("Connection failed");

$fetcher = new DocumentDataFetcher($conn, $profile_id);
$data = $fetcher->fetchAll();

echo "COLLATERAL COUNT: " . count($data['collateral']) . "\n";
if (!empty($data['collateral'])) {
    print_r($data['collateral'][0]);
} else {
    echo "NO COLLATERAL DATA\n";
}
?>
