<?php
require_once 'config/config.php';
$conn = new mysqli('localhost', 'root', '', 'das_db');
$profile_id = 2; // Testing with known profile

// Check Borrowers
echo "=== BORROWERS ===\n";
$stmt = $conn->prepare("SELECT * FROM borrowers WHERE customer_profile_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->num_rows;
echo "Count: $count\n";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

// Check Guarantors (in case Co-Borrowers are here)
echo "\n=== GUARANTORS ===\n";
$stmt = $conn->prepare("SELECT * FROM guarantors WHERE customer_profile_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$result = $stmt->get_result();
echo "Count: " . $result->num_rows . "\n";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
