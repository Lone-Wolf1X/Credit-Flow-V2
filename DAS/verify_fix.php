<?php
// Quick verification script
require_once 'config/config.php';

// Check if column exists
$result = $conn->query("SHOW COLUMNS FROM borrowers LIKE 'ctz_reissued'");
if ($result->num_rows > 0) {
    echo "SUCCESS: ctz_reissued column exists in borrowers table\n";
} else {
    echo "FAILED: ctz_reissued column is missing\n";
}

$result = $conn->query("SHOW COLUMNS FROM guarantors LIKE 'ctz_reissued'");
if ($result->num_rows > 0) {
    echo "SUCCESS: ctz_reissued column exists in guarantors table\n";
} else {
    echo "FAILED: ctz_reissued column is missing\n";
}
?>
