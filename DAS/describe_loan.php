<?php
require_once 'c:/xampp/htdocs/Credit/DAS/config/config.php';
$res = $conn->query("DESCRIBE loan_details");
while ($r = $res->fetch_assoc()) echo $r['Field'] . "\n";
?>
