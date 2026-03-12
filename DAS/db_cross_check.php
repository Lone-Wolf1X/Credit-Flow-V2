<?php
require_once 'config/config.php';

echo "--- Checking admin_db.provinces ---\n";
try {
    $res = $conn->query("SELECT * FROM admin_db.provinces WHERE is_active = 1 ORDER BY province_code");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            print_r($row);
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "\n--- Checking das_db.branchsol ---\n";
$res = $conn->query("SELECT * FROM branchsol LIMIT 10");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
}
?>
