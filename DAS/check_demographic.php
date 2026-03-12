<?php
$conn = new mysqli('localhost', 'root', '', 'das_db');

echo "Table: demopnepal\n";
echo "-----------------\n";
try {
    $res = $conn->query("DESCRIBE demopnepal");
    if ($res) {
        while($row = $res->fetch_assoc()) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    } else {
        echo "Error: " . $conn->error;
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
?>
