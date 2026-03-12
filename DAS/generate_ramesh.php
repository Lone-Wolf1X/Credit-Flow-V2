<?php
include __DIR__ . '/config/config.php';
$conn = new mysqli('localhost', 'root', '', 'das_db');

// Search in customer_profiles first
$sql = "SELECT id, full_name, customer_id FROM customer_profiles WHERE full_name LIKE '%Ramesh Mahato%'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Found in Customer Profiles: ID=" . $row["id"] . "\n";
        generateDoc($row['id'], $conn);
        exit;
    }
} 

// Search in borrowers if not found in profiles
$sql = "SELECT customer_profile_id, full_name FROM borrowers WHERE full_name LIKE '%Ramesh Mahato%' OR full_name_en LIKE '%Ramesh Mahato%'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Found in Borrowers: ProfileID=" . $row["customer_profile_id"] . "\n";
        generateDoc($row['customer_profile_id'], $conn);
        exit;
    }
} else {
    echo "No results found for Ramesh Mahato in Profiles or Borrowers.\n";
}

function generateDoc($id, $conn) {
    echo "Generating document for Profile ID: $id\n";
    require_once __DIR__ . '/includes/DocumentGenerator.php';
    $generator = new DocumentGenerator($conn);
    $result = $generator->generateMortgageDeed($id);
    if ($result['success']) {
        echo "Generation SUCCESS: " . $result['path'] . "\n";
    } else {
        echo "Generation FAILED: " . $result['error'] . "\n";
    }
}
?>
