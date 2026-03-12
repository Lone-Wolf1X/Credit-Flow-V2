<?php
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$current_user = getCurrentUser();
require_once __DIR__ . '/avatar_helpers.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/avatar_notifications.css?v=<?php echo time(); ?>">
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
</head>

<body>
    <div class="sidebar">
        <div class="brand">
            <i class="fas fa-university"></i> Credit Flow
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'initiate_loan.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>modules/initiator/initiate_loan.php">
                    <i class="fas fa-plus-circle"></i> Initiate Loan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_applications.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>modules/initiator/my_applications.php">
                    <i class="fas fa-list"></i> My Applications
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'returned_applications.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>modules/initiator/returned_applications.php">
                    <i class="fas fa-undo"></i> Returned Applications
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pending_reviews.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>modules/reviewer/pending_reviews.php">
                    <i class="fas fa-tasks"></i> Pending Reviews
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pending_approvals.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>modules/approver/pending_approvals.php">
                    <i class="fas fa-check-circle"></i> Pending Approvals
                </a>
            </li>

            <?php if ($current_user['role'] === 'Admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'active' : ''; ?>"
                        href="<?php echo BASE_URL; ?>modules/admin/user_management.php">
                        <i class="fas fa-users"></i> User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'matrix_management.php' ? 'active' : ''; ?>"
                        href="<?php echo BASE_URL; ?>modules/admin/matrix_management.php">
                        <i class="fas fa-sitemap"></i> Escalation Matrix
                    </a>
                </li>
            <?php endif; ?>

            <li class="nav-item mt-auto">
                <a class="nav-link" href="<?php echo BASE_URL; ?>logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><?php echo ucfirst(str_replace('_', ' ', basename($_SERVER['PHP_SELF'], '.php'))); ?></h1>
                </div>
                <div class="col-md-6">
                    <div class="user-info-container" style="justify-content: flex-end;">
                        <!-- Notification Bell -->
                        <?php $unreadCount = getUnreadNotificationCount($current_user['id']); ?>
                        <div class="notification-bell" onclick="toggleNotifications(event)">
                            <i class="fas fa-bell"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span class="notification-badge"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>

                            <!-- Notification Dropdown -->
                            <div class="notification-dropdown" id="notificationDropdown">
                                <div class="notification-header">
                                    Notifications
                                    <?php if ($unreadCount > 0): ?>
                                        <span class="float-end text-primary" style="cursor:pointer; font-size:0.85rem;"
                                            onclick="markAllRead(event)">Mark all read</span>
                                    <?php endif; ?>
                                </div>
                                <?php
                                $notifications = getRecentNotifications($current_user['id'], 10);
                                if (empty($notifications)):
                                    ?>
                                    <div class="notification-empty">
                                        <i class="fas fa-bell-slash" style="font-size:2rem; margin-bottom:10px;"></i><br>
                                        No notifications
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notif): ?>
                                        <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>"
                                            onclick="handleNotificationClick(event, <?php echo $notif['id']; ?>, '<?php echo $notif['link'] ?? '#'; ?>')">
                                            <div class="notification-title">
                                                <?php echo htmlspecialchars($notif['type']); ?>
                                            </div>
                                            <div class="notification-message">
                                                <?php echo htmlspecialchars($notif['message']); ?>
                                                <?php if ($notif['cap_id']): ?>
                                                    <br><strong>CAP ID:</strong> <?php echo htmlspecialchars($notif['cap_id']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="notification-time">
                                                <?php echo timeAgo($notif['created_at']); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- User Avatar and Info -->
                        <?php echo generateAvatar($current_user['full_name'], $current_user['role']); ?>
                        <div class="user-details">
                            <div class="user-name"><?php echo htmlspecialchars($current_user['full_name']); ?></div>
                            <div class="user-role"><?php echo htmlspecialchars($current_user['designation']); ?> |
                                <?php echo htmlspecialchars($current_user['role']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>