<?php
session_start();
require_once '../../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit;
}

// Page variables
$pageTitle = 'Recent Activity';
$activeMenu = 'recent_activity';
$userName = $_SESSION['full_name'] ?? 'User';
$badgeText = $_SESSION['role_name'] ?? 'User';

// Fetch recent activities
$recent_activities = [];
$activities_stmt = $conn->prepare("SELECT action, description, created_at FROM audit_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$activities_stmt->bind_param("i", $_SESSION['user_id']);
$activities_stmt->execute();
$activities_result = $activities_stmt->get_result();
while ($row = $activities_result->fetch_assoc()) {
    $recent_activities[] = [
        'action' => $row['action'],
        'description' => $row['description'],
        'time' => date('M d, Y h:i A', strtotime($row['created_at']))
    ];
}

ob_start();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-activity me-2"></i>Recent Activity
            </h2>
            <p class="text-muted">Tracking your last 50 actions in the system.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if (empty($recent_activities)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>
                            <p class="text-muted mb-0">No activities found</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th width="200">Action</th>
                                        <th>Description</th>
                                        <th width="200">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_activities as $index => $activity): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <span class="badge bg-primary bg-opacity-10 text-primary">
                                                    <?php echo htmlspecialchars($activity['action']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                            <td class="text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                <?php echo htmlspecialchars($activity['time']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$mainContent = ob_get_clean();
include '../../Layout/layout_new.php';
?>
