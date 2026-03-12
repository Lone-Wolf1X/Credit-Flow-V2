<?php
session_start();
require_once '../../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit;
}

// Page variables for layout
$pageTitle = 'Checker Dashboard';
$activeMenu = 'dashboard';
$userName = $_SESSION['full_name'] ?? 'User';
$userAvatar = '../../asstes/images/img_avatar.png';
$badgeText = $_SESSION['role_name'] ?? 'Checker';

// Dashboard statistics - fetch from database
$pending_reviews_result = $conn->query("SELECT COUNT(*) as count FROM customer_profiles WHERE status = 'Submitted'");
$pending_reviews = $pending_reviews_result->fetch_assoc()['count'];

$approved_today_result = $conn->query("SELECT COUNT(*) as count FROM customer_profiles WHERE status = 'Approved' AND DATE(approved_at) = CURDATE()");
$approved_today = $approved_today_result->fetch_assoc()['count'];

$rejected_result = $conn->query("SELECT COUNT(*) as count FROM customer_profiles WHERE status = 'Rejected'");
$rejected = $rejected_result->fetch_assoc()['count'];

$total_reviewed_result = $conn->query("SELECT COUNT(*) as count FROM customer_profiles WHERE status IN ('Approved', 'Rejected')");
$total_reviewed = $total_reviewed_result->fetch_assoc()['count'];

// Get active admin messages
$messages_result = $conn->query("SELECT title, message, message_type FROM admin_messages WHERE is_active = 1 AND (end_date IS NULL OR end_date > NOW()) ORDER BY created_at DESC LIMIT 1");
$admin_message = $messages_result->fetch_assoc();

// Recent activities - fetch last 5 audit logs for current user
$recent_activities = [];
$activities_stmt = $conn->prepare("SELECT action, description, created_at FROM audit_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$activities_stmt->bind_param("i", $_SESSION['user_id']);
$activities_stmt->execute();
$activities_result = $activities_stmt->get_result();
while ($row = $activities_result->fetch_assoc()) {
    $recent_activities[] = [
        'description' => $row['action'] . ': ' . $row['description'],
        'time' => date('M d, Y h:i A', strtotime($row['created_at']))
    ];
}

// Pending profiles for review
$pending_profiles = [];
$pending_stmt = $conn->query("
    SELECT cp.id, cp.customer_id, cp.full_name, cp.submitted_at, u.full_name as submitted_by
    FROM customer_profiles cp
    LEFT JOIN users u ON cp.created_by = u.id
    WHERE cp.status = 'Submitted'
    ORDER BY cp.submitted_at DESC
    LIMIT 10
");

if ($pending_stmt) {
    while ($row = $pending_stmt->fetch_assoc()) {
        $pending_profiles[] = $row;
    }
}

// Start output buffering for main content
ob_start();
?>

<!-- Dashboard Header -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-clipboard-check me-2"></i>Checker Dashboard
            </h2>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($userName); ?>! Review pending documents below.</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <!-- Pending Reviews Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="text-white-50 mb-1 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Pending Reviews</h6>
                            <h2 class="mb-0 fw-bold"><?php echo $pending_reviews; ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-circle p-3">
                            <i class="bi bi-hourglass-split fs-2"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-white bg-opacity-25 me-2">
                            <i class="bi bi-exclamation-circle"></i> Action Required
                        </span>
                        <small class="text-white-50">Needs attention</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approved Today Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="text-white-50 mb-1 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Approved Today</h6>
                            <h2 class="mb-0 fw-bold"><?php echo $approved_today; ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-circle p-3">
                            <i class="bi bi-check-circle-fill fs-2"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-white bg-opacity-25 me-2">
                            <i class="bi bi-calendar-check"></i> Today
                        </span>
                        <small class="text-white-50">Great progress!</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rejected Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="text-white-50 mb-1 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Rejected</h6>
                            <h2 class="mb-0 fw-bold"><?php echo $rejected; ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-circle p-3">
                            <i class="bi bi-x-circle-fill fs-2"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-white bg-opacity-25 me-2">
                            <i class="bi bi-arrow-return-left"></i> Returned
                        </span>
                        <small class="text-white-50">For revision</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Reviewed Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="text-white-50 mb-1 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Reviewed</h6>
                            <h2 class="mb-0 fw-bold"><?php echo $total_reviewed; ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-circle p-3">
                            <i class="bi bi-clipboard-data fs-2"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-white bg-opacity-25 me-2">
                            <i class="bi bi-graph-up"></i> All Time
                        </span>
                        <small class="text-white-50">Total count</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Message Banner -->
    <?php if ($admin_message): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-<?php echo $admin_message['message_type']; ?> alert-dismissible fade show shadow-sm border-0" role="alert">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <?php
                            $icon_map = [
                                'info' => 'bi-info-circle-fill',
                                'warning' => 'bi-exclamation-triangle-fill',
                                'success' => 'bi-check-circle-fill',
                                'danger' => 'bi-x-circle-fill'
                            ];
                            $icon = $icon_map[$admin_message['message_type']] ?? 'bi-megaphone-fill';
                            ?>
                            <i class="bi <?php echo $icon; ?> fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-2">
                                <i class="bi bi-megaphone me-2"></i><?php echo htmlspecialchars($admin_message['title']); ?>
                            </h5>
                            <p class="mb-0"><?php echo htmlspecialchars($admin_message['message']); ?></p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Dashboard Info -->
    <div class="row">
        <div class="col-12 text-center py-5">
            <div class="text-muted">
                <i class="bi bi-check2-circle display-1 mb-3"></i>
                <p class="lead">Dashboard Overview</p>
                <p>Use the sidebar to view <a href="pending_tasks.php">Pending Tasks</a> or <a href="recent_activity.php">Recent Activity</a>.</p>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
}
</style>

<?php
$mainContent = ob_get_clean();

// Include the layout
include '../../Layout/layout_new.php';
?>
