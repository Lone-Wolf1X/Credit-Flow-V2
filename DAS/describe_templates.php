<?php
require_once 'c:/xampp/htdocs/Credit/DAS/config/config.php';
$res = $conn->query("DESCRIBE templates");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        echo $r['Field'] . "\n";
    }
} else {
    echo "Query failed: " . $conn->error;
}
?>
