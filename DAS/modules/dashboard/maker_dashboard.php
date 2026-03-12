<?php
session_start();
require_once '../../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit;
}

// Page variables for layout
$pageTitle = 'Maker Dashboard';
$activeMenu = 'dashboard';
$userName = $_SESSION['full_name'] ?? 'User';
$userAvatar = '../../asstes/images/img_avatar.png';
$badgeText = $_SESSION['role_name'] ?? 'Maker';

// Dashboard statistics - USER-SPECIFIC (filter by created_by)
$user_id = $_SESSION['user_id'];

// Total customers created by this user
$total_customers_stmt = $conn->prepare("SELECT COUNT(*) as count FROM customer_profiles WHERE created_by = ?");
$total_customers_stmt->bind_param("i", $user_id);
$total_customers_stmt->execute();
$total_customers = $total_customers_stmt->get_result()->fetch_assoc()['count'];

// Documents generated for profiles created by this user
$documents_generated_stmt = $conn->prepare("SELECT COUNT(*) as count FROM generated_documents gd INNER JOIN customer_profiles cp ON gd.customer_profile_id = cp.id WHERE cp.created_by = ?");
$documents_generated_stmt->bind_param("i", $user_id);
$documents_generated_stmt->execute();
$documents_generated = $documents_generated_stmt->get_result()->fetch_assoc()['count'];

// Pending approvals - profiles submitted by this user
$pending_approvals_stmt = $conn->prepare("SELECT COUNT(*) as count FROM customer_profiles WHERE status = 'Submitted' AND created_by = ?");
$pending_approvals_stmt->bind_param("i", $user_id);
$pending_approvals_stmt->execute();
$pending_approvals = $pending_approvals_stmt->get_result()->fetch_assoc()['count'];

// Total approved profiles created by this user
$total_approved_stmt = $conn->prepare("SELECT COUNT(*) as count FROM customer_profiles WHERE status = 'Approved' AND created_by = ?");
$total_approved_stmt->bind_param("i", $user_id);
$total_approved_stmt->execute();
$total_approved = $total_approved_stmt->get_result()->fetch_assoc()['count'];

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

// Get active admin messages
$messages_result = $conn->query("SELECT title, message, message_type FROM admin_messages WHERE is_active = 1 AND (end_date IS NULL OR end_date > NOW()) ORDER BY created_at DESC LIMIT 1");
$admin_message = $messages_result->fetch_assoc();

// Start output buffering for main content
ob_start();
?>

<!-- Dashboard Header -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-speedometer2 me-2"></i>Maker Dashboard
            </h2>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($userName); ?>! Here's your overview.</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <!-- Total Customers Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="text-white-50 mb-1 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Customers</h6>
                            <h2 class="mb-0 fw-bold"><?php echo $total_customers; ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-circle p-3">
                            <i class="bi bi-people-fill fs-2"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-white bg-opacity-25 me-2">
                            <i class="bi bi-arrow-up"></i> 12%
                        </span>
                        <small class="text-white-50">vs last month</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Generated Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="text-white-50 mb-1 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Documents Generated</h6>
                            <h2 class="mb-0 fw-bold"><?php echo $documents_generated; ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-circle p-3">
                            <i class="bi bi-file-earmark-text-fill fs-2"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-white bg-opacity-25 me-2">
                            <i class="bi bi-arrow-up"></i> 8%
                        </span>
                        <small class="text-white-50">vs last month</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Approvals Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="text-white-50 mb-1 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Pending Approvals</h6>
                            <h2 class="mb-0 fw-bold"><?php echo $pending_approvals; ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-circle p-3">
                            <i class="bi bi-clock-history fs-2"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-white bg-opacity-25 me-2">
                            <i class="bi bi-dash"></i> 0%
                        </span>
                        <small class="text-white-50">No change</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Approved Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="text-white-50 mb-1 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Approved</h6>
                            <h2 class="mb-0 fw-bold"><?php echo $total_approved; ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-circle p-3">
                            <i class="bi bi-check-circle-fill fs-2"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-white bg-opacity-25 me-2">
                            <i class="bi bi-check-circle"></i> Approved
                        </span>
                        <small class="text-white-50">Your profiles</small>
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


    <!-- Recent Customers Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-person-lines-fill text-primary me-2"></i>Recent Customers
                        </h5>
                        <a href="../customer/customer_list.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-list-ul me-1"></i>View All
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0">Customer ID</th>
                                    <th class="border-0">Name</th>
                                    <th class="border-0">Type</th>
                                    <th class="border-0">Contact</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0">Created Date</th>
                                    <th class="border-0">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch recent customers created by this user
                                $recent_customers_query = "SELECT cp.*, u.full_name as created_by_name 
                                                          FROM customer_profiles cp 
                                                          LEFT JOIN users u ON cp.created_by = u.id 
                                                          WHERE cp.created_by = ? 
                                                          ORDER BY cp.created_at DESC 
                                                          LIMIT 10";
                                $stmt = $conn->prepare($recent_customers_query);
                                $stmt->bind_param("i", $_SESSION['user_id']);
                                $stmt->execute();
                                $recent_customers = $stmt->get_result();
                                
                                if ($recent_customers->num_rows > 0):
                                    while ($customer = $recent_customers->fetch_assoc()):
                                        $statusColors = [
                                            'Draft' => 'warning',
                                            'Submitted' => 'info',
                                            'Approved' => 'success',
                                            'Rejected' => 'danger'
                                        ];
                                        $statusColor = $statusColors[$customer['status']] ?? 'secondary';
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($customer['customer_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $customer['customer_type'] == 'Individual' ? 'info' : 'warning'; ?>">
                                            <?php echo htmlspecialchars($customer['customer_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($customer['contact']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $statusColor; ?>">
                                            <?php echo htmlspecialchars($customer['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                                    <td>
                                        <a href="../customer/customer_profile.php?id=<?php echo $customer['id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="bi bi-inbox display-4 text-muted d-block mb-3"></i>
                                        <p class="text-muted mb-2">No customers created yet</p>
                                        <a href="../customer/create_customer.php" class="btn btn-primary">
                                            <i class="bi bi-plus-circle me-2"></i>Create Your First Customer
                                        </a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Info -->
    <div class="row">
        <div class="col-12 text-center py-5">
            <div class="text-muted">
                <i class="bi bi-speedometer2 display-1 mb-3"></i>
                <p class="lead">Dashboard Overview</p>
                <p>Use the sidebar to view <a href="pending_tasks.php">Pending Tasks</a> or <a href="recent_activity.php">Recent Activity</a>.</p>
            </div>
        </div>
    </div>
</div>

<style>
.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
}

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
