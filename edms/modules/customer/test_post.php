<?php
// Simple test to verify POST is working
session_start();

file_put_contents('debug_post.txt', date('Y-m-d H:i:s') . " - POST DATA:\n" . print_r($_POST, true) . "\n\n", FILE_APPEND);

if (isset($_POST['submit_profile_global'])) {
    echo "SUCCESS: POST received with submit_profile_global";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
} else {
    echo "FAIL: POST received but no submit_profile_global";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
}
?>
