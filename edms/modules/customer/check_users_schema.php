<?php
require_once '../../config/config.php';

// We need to connect to credit flow db
// config.php likely has $conn for edms_db. 
// We need to check if $cf_conn is available or create a new connection.
// Usually config.php in this project connects to both or one.
// Let's assume we can query `credit_flow_db.users` if the user has access.

$dbname = 'credit_flow_db'; 
// Note: In previous context, CF_DB_NAME might be defined.

echo "Table Schema for credit_flow_db.users:\n";
$result = $conn->query("DESCRIBE $dbname.users");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn->error;
    // Try without db name check if config connects to it
    $result = $cf_conn->query("DESCRIBE users"); // assuming $cf_conn exists from login.php context
     if ($result) {
        while ($row = $result->fetch_assoc()) {
            print_r($row);
        }
    } else {
         echo "Error CF: " . $cf_conn->error;
    }
}
?>
