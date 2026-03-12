<?php
// Avatar and Notification Helper Functions

/**
 * Get user initials from full name
 */
function getUserInitials($fullName)
{
    $names = explode(' ', trim($fullName));
    if (count($names) >= 2) {
        return strtoupper(substr($names[0], 0, 1) . substr($names[count($names) - 1], 0, 1));
    }
    return strtoupper(substr($fullName, 0, 2));
}

/**
 * Get avatar color based on user role or name
 */
function getAvatarColor($role = null, $name = null)
{
    $colors = [
        'Admin' => '#dc2626',      // Red
        'Approver' => '#059669',   // Green
        'Reviewer' => '#2563eb',   // Blue
        'Initiator' => '#7c3aed',  // Purple
    ];

    if ($role && isset($colors[$role])) {
        return $colors[$role];
    }

    // Generate color from name hash
    if ($name) {
        $hash = md5($name);
        $hue = hexdec(substr($hash, 0, 2)) % 360;
        return "hsl($hue, 60%, 45%)";
    }

    return '#1e3a8a'; // Default blue
}

/**
 * Generate avatar HTML
 */
function generateAvatar($fullName, $role = null, $size = 'normal')
{
    $initials = getUserInitials($fullName);
    $color = getAvatarColor($role, $fullName);
    $sizeClass = $size !== 'normal' ? $size : '';

    return "<div class='user-avatar $sizeClass' style='background-color: $color;'>$initials</div>";
}

/**
 * Get unread notification count for user
 */
function getUnreadNotificationCount($userId)
{
    global $conn;

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result['count'] ?? 0;
}

/**
 * Get recent notifications for user
 */
function getRecentNotifications($userId, $limit = 10)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT n.*, la.cap_id, la.applicant_name 
        FROM notifications n
        LEFT JOIN loan_applications la ON n.application_id = la.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Mark notification as read
 */
function markNotificationRead($notificationId)
{
    global $conn;

    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $notificationId);
    return $stmt->execute();
}

/**
 * Create notification
 */
function createNotification($userId, $applicationId, $type, $message, $link = null)
{
    global $conn;

    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, application_id, type, message, link, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iisss", $userId, $applicationId, $type, $message, $link);
    return $stmt->execute();
}

/**
 * Format time ago
 */
function timeAgo($datetime)
{
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}
?>