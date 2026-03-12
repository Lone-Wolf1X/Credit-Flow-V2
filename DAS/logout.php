<?php
session_start();
require_once 'config/config.php';

// Log audit if user is logged in
if (isset($_SESSION['user_id'])) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'Logout', 'User logged out', ?)");
    $stmt->bind_param("is", $_SESSION['user_id'], $ip_address);
    $stmt->execute();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>
