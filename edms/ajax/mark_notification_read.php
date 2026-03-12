<?php
require_once '../config/config.php';
require_once '../includes/notification_system.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$notificationId = intval($_GET['id']);
$success = markAsRead($notificationId);

echo json_encode(['success' => $success]);
?>
