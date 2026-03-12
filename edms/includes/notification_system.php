<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Create notification
 */
function createNotification($user_id, $customer_id, $cap_id, $message, $type = 'info')
{
    global $conn;

    // Generate link to customer profile with CAP ID tab
    $link = BASE_URL . "modules/customer/customer_profile.php?id=$customer_id&cap_id=$cap_id";

    $stmt = $conn->prepare("INSERT INTO notifications (user_id, customer_id, cap_id, message, type, link) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $user_id, $customer_id, $cap_id, $message, $type, $link);

    return $stmt->execute();
}

/**
 * Get unread notifications for user
 */
function getUnreadNotifications($user_id)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT n.*, c.customer_name, c.client_id
        FROM notifications n
        LEFT JOIN customers c ON n.customer_id = c.id
        WHERE n.user_id = ? AND n.is_read = 0
        ORDER BY n.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all notifications for user
 */
function getAllNotifications($user_id, $limit = 50)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT n.*, c.customer_name, c.client_id
        FROM notifications n
        LEFT JOIN customers c ON n.customer_id = c.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Mark notification as read
 */
function markAsRead($notification_id)
{
    global $conn;

    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $notification_id);

    return $stmt->execute();
}

/**
 * Mark all notifications as read for user
 */
function markAllAsRead($user_id)
{
    global $conn;

    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    return $stmt->execute();
}

/**
 * Get unread count
 */
function getUnreadCount($user_id)
{
    global $conn;

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc()['count'];
}

/**
 * Notify legal team about new CAP submission
 */
function notifyLegalTeam($customer_id, $cap_id, $customer_name)
{
    global $conn;

    // Get all Checkers (legal team) from EDMS users
    $reviewers = $conn->query("SELECT id FROM edms_users WHERE role = 'Checker' AND is_active = 1")->fetch_all(MYSQLI_ASSOC);

    $message = "New CAP ID $cap_id submitted for legal vetting - Customer: $customer_name";

    foreach ($reviewers as $reviewer) {
        createNotification($reviewer['id'], $customer_id, $cap_id, $message, 'new_submission');
    }
}

/**
 * Notify initiator about CAP return
 */
function notifyInitiator($initiator_id, $customer_id, $cap_id, $customer_name)
{
    $message = "CAP ID $cap_id has been returned for customer: $customer_name";
    createNotification($initiator_id, $customer_id, $cap_id, $message, 'returned');
}

/**
 * Notify initiator about CAP approval
 */
function notifyApproval($initiator_id, $customer_id, $cap_id, $customer_name)
{
    $message = "CAP ID $cap_id has been approved for customer: $customer_name";
    createNotification($initiator_id, $customer_id, $cap_id, $message, 'approved');
}
?>