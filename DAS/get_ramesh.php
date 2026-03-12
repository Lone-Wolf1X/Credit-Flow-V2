<?php
include __DIR__ . '/config/config.php';
$conn = new mysqli('localhost', 'root', '', 'das_db');

$sql = "SELECT id, full_name, customer_id FROM customer_profiles WHERE full_name LIKE '%Ramesh Mahato%'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Found Profile: ID=" . $row["id"] . " - Name: " . $row["full_name"] . " - CustID: " . $row["customer_id"] . "\n";
        
        // Also check family details
        $famSql = "SELECT * FROM family_details WHERE person_id IN (SELECT id FROM borrowers WHERE customer_profile_id = " . $row["id"] . ")";
        $famResult = $conn->query($famSql);
        echo "Family Members for this profile:\n";
        while($fam = $famResult->fetch_assoc()) {
             echo " - " . $fam['relation'] . ": " . $fam['name'] . "\n";
        }
    }
} else {
    echo "No results found for Ramesh Mahato";
}
?>
