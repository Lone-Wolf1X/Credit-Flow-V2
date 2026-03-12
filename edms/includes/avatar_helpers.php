<?php
// Avatar and UI Helper Functions for EDMS

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
        'Maker' => '#7c3aed',      // Purple
        'Checker' => '#059669',    // Green
        'Legal' => '#2563eb',      // Blue
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
