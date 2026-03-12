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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/avatar_notifications.css?v=<?php echo time(); ?>">
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/notifications.js?v=<?php echo time(); ?>" defer></script>
</head>

<body>
    <div class="layout-wrapper" id="layoutWrapper">
        <!-- Sidebar -->
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content flex-grow-1" id="mainContent">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top px-4 py-2 shadow-sm">
                <div class="d-flex align-items-center w-100">
                    <button class="btn btn-light border-0 me-3" id="sidebarToggle">
                        <i class="fas fa-bars fa-lg text-secondary"></i>
                    </button>
                    
                    <h5 class="mb-0 fw-bold text-dark d-none d-md-block">
                        <?php echo ucfirst(str_replace('_', ' ', basename($_SERVER['PHP_SELF'], '.php'))); ?>
                    </h5>

                    <div class="ms-auto d-flex align-items-center gap-4">
                        <!-- Notification Bell -->
                        <?php $unreadCount = getUnreadCount($current_user['id']); ?>
                        <div class="notification-container position-relative">
                            <i class="fas fa-bell fa-lg text-secondary cursor-pointer" onclick="toggleNotifications(event)" style="cursor: pointer; transition: transform 0.2s;"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light">
                                    <?php echo $unreadCount; ?>
                                </span>
                            <?php endif; ?>
                            
                            <!-- Dropdown -->
                            <div class="notification-dropdown shadow-lg border-0" id="notificationDropdown">
                                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light rounded-top">
                                    <h6 class="mb-0 fw-bold text-dark">Notifications</h6>
                                    <?php if ($unreadCount > 0): ?>
                                        <small class="text-primary cursor-pointer" onclick="markAllRead(event)">Mark all read</small>
                                    <?php endif; ?>
                                </div>
                                <div class="list-group list-group-flush" style="max-height: 350px; overflow-y: auto;">
                                    <?php
                                    $notifications = getAllNotifications($current_user['id'], 8);
                                    if (empty($notifications)):
                                    ?>
                                        <div class="text-center p-4 text-muted">
                                            <i class="fas fa-bell-slash fa-2x mb-2 opacity-25"></i>
                                            <p class="small mb-0">No new notifications</p>
                                        </div>
                                    <?php else: foreach ($notifications as $notif): ?>
                                        <div class="list-group-item list-group-item-action py-3 px-3 <?php echo $notif['is_read'] ? '' : 'bg-blue-soft'; ?>"
                                            onclick="handleNotificationClick(event, <?php echo $notif['id']; ?>, '<?php echo $notif['link'] ?? '#'; ?>')" style="cursor: pointer;">
                                            <div class="d-flex w-100 justify-content-between mb-1">
                                                <small class="fw-bold text-primary"><?php echo htmlspecialchars($notif['type']); ?></small>
                                                <small class="text-muted" style="font-size: 0.7em;"><?php echo timeAgo($notif['created_at']); ?></small>
                                            </div>
                                            <p class="mb-0 small text-secondary text-truncate" style="max-width: 250px;"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        </div>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- User Profile Dropdown -->
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php echo generateAvatar($current_user['full_name'], $current_user['role']); ?>
                                <div class="ms-2 d-none d-lg-block text-start">
                                    <div class="fw-bold small lh-1"><?php echo htmlspecialchars($current_user['full_name']); ?></div>
                                    <div class="text-muted smallest lh-1 mt-1"><?php echo htmlspecialchars($current_user['designation']); ?></div>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 animated--fade-in-up" aria-labelledby="userDropdown">
                                <li><h6 class="dropdown-header">Account</h6></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/user/profile.php"><i class="fas fa-user-circle me-2 text-muted"></i> My Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Page Content Wrapper -->
            <div class="p-4 page-content-container">