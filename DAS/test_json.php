<?php
// Test API Response Simulation
ob_start();
echo "Some warning or HTML junk<br>";
$data = ['success' => true, 'message' => 'Clean JSON'];

ob_clean();
echo json_encode($data);
?>
