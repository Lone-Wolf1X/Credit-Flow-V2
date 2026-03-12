// Notification JavaScript Functions

function toggleNotifications(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('show');

    // Close when clicking outside
    document.addEventListener('click', function closeDropdown(e) {
        if (!e.target.closest('.notification-bell')) {
            dropdown.classList.remove('show');
            document.removeEventListener('click', closeDropdown);
        }
    });
}

async function handleNotificationClick(event, notificationId, link) {
    try {
        // Mark as read
        await fetch(`${BASE_URL}ajax/mark_notification_read.php?id=${notificationId}`, {
            keepalive: true
        });

        // Redirect if link exists
        if (link && link !== '#') {
            window.location.href = link.startsWith('http') ? link : BASE_URL + link;
        } else {
            // Just reload to update badge
            location.reload();
        }
    } catch (error) {
        console.error('Error handling notification:', error);
    }
}

async function markAllRead(event) {
    event.stopPropagation();
    try {
        await fetch(`${BASE_URL}ajax/mark_all_notifications_read.php`, {
            keepalive: true
        });
        location.reload();
    } catch (error) {
        console.error('Error marking all as read:', error);
    }
}
