<?php
require_once '../config/config.php';
require_once '../includes/notification_system.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$userId = $_SESSION['user_id'];
$success = markAllAsRead($userId);

echo json_encode(['success' => $success]);
?>
