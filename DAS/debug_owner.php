<?php
require_once 'config/config.php';
require_once 'includes/DocumentDataFetcher.php';
require_once 'includes/PlaceholderMapper.php';

$profile_id = $_GET['id'] ?? 1;

$conn = new mysqli('localhost', 'root', '', 'das_db');
if ($conn->connect_error) die("Connection failed");

$fetcher = new DocumentDataFetcher($conn, $profile_id);
$data = $fetcher->fetchAll();

if (!empty($data['collateral'])) {
    $collateral = $data['collateral'][0];
    echo "COLLATERAL OWNER: " . $collateral['owner_type'] . " ID " . $collateral['owner_id'] . "\n";
    
    // Use PlaceholderMapper to test getCollateralOwner logic
    $mapper = new PlaceholderMapper($data);
    
    // We can't access private method, so let's replicate logic manually or make it public/helper
    // Manual replication:
    $owner = null;
    if ($collateral['owner_type'] == 'Borrower') {
        foreach ($data['borrowers'] as $b) {
            if ($b['id'] == $collateral['owner_id']) $owner = $b;
        }
    } else {
        foreach ($data['guarantors'] as $g) {
            if ($g['id'] == $collateral['owner_id']) $owner = $g;
        }
    }
    
    if ($owner) {
        echo "OWNER FOUND:\n";
        print_r($owner);
    } else {
        echo "OWNER NOT FOUND in fetched data!\n";
        echo "Available Guarantors:\n";
        print_r($data['guarantors']);
    }
} else {
    echo "NO COLLATERAL DATA\n";
}
?>
