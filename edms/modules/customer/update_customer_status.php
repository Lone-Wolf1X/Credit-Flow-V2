<?php
require_once '../../config/config.php';

$sql = "ALTER TABLE customers MODIFY COLUMN status ENUM('Pending', 'Approved', 'Returned', 'Draft', 'Pending Legal', 'Picked') DEFAULT 'Draft'";
if ($conn->query($sql)) {
    echo "SUCCESS: Updated customers status ENUM.<br>";
} else {
    echo "ERROR: " . $conn->error . "<br>";
}
?>
