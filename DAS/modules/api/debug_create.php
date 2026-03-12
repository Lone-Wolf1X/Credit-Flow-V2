<?php
// Mock Session
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1; // Assuming 1 is Admin

// Mock POSt Data
$_POST['action'] = 'create_customer';
$_POST['customer_type'] = 'Individual';
$_POST['full_name'] = 'Auto Test User';
$_POST['email'] = 'auto@test.com';
$_POST['contact'] = '9812345678';

// Mock Server vars
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Include the API
require 'customer_api.php';
?>
