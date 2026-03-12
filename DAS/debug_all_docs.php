<?php
require_once 'config/config.php';
$res = $conn->query("SELECT id, customer_profile_id, batch_id, generated_at, is_active FROM das_generated_documents ORDER BY id DESC LIMIT 20");
echo "Last 20 documents:\n";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
