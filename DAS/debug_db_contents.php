<?php
require_once 'config/config.php';
$profile_id = 1;
$res = $conn->query("SELECT id, batch_id, generated_at, is_active FROM das_generated_documents WHERE customer_profile_id = $profile_id ORDER BY generated_at DESC");
echo "Total documents: " . $res->num_rows . "\n";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
