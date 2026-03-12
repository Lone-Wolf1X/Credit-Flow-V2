<?php
$conn = new mysqli('localhost', 'root', '', 'das_db');
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

echo "--- loan_details columns ---\n";
$result = $conn->query("DESCRIBE loan_details");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " | " . $row['Type'] . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
?>
