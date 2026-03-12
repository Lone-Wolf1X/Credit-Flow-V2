<?php
require_once 'c:/xampp/htdocs/Credit/Admin/config.php';

echo "--- demopnepal (First 5 rows) ---\n";
$res = $admin_conn->query('SELECT * FROM demopnepal LIMIT 5');
while($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n--- branch_profiles (First 2 rows) ---\n";
// Use das_conn as branch_profiles is in das_db (based on earlier context) -> Wait, config.php says branch_profiles is in das_db usually?
// Let's check where it is. branch_profiles.php uses $das_conn.
$res2 = $das_conn->query('SELECT id, sol_id, branch_name_en FROM branch_profiles LIMIT 2');
if($res2) {
    while($row = $res2->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error fetching branch_profiles: " . $das_conn->error;
}
?>
