<?php
require_once '../../config/config.php';
require_once '../../includes/avatar_helpers.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$notificationId = intval($_GET['id']);
error_log("Marking notification $notificationId as read", 3, "../../debug_notifications.log");
$success = markNotificationRead($notificationId);
error_log("Success: " . ($success ? 'Yes' : 'No'), 3, "../../debug_notifications.log");

echo json_encode(['success' => $success]);
?>