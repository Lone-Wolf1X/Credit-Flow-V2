<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

requireLogin();

$user = getCurrentUser();
$role = $_SESSION['role'];

// Get dashboard statistics based on role
$stats = [];

// Get unified dashboard statistics
$stats = [];

// 1. Initiator Stats
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM loan_applications WHERE initiator_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats['initiated_total'] = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as pending FROM loan_applications WHERE initiator_id = ? AND status = 'Initiated'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats['initiated_pending'] = $stmt->get_result()->fetch_assoc()['pending'];

$stmt = $conn->prepare("SELECT COUNT(*) as returned FROM loan_applications WHERE initiator_id = ? AND status = 'Returned'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats['initiated_returned'] = $stmt->get_result()->fetch_assoc()['returned'];

$stmt = $conn->prepare("SELECT COUNT(*) as approved FROM loan_applications WHERE initiator_id = ? AND status = 'Approved'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats['initiated_approved'] = $stmt->get_result()->fetch_assoc()['approved'];

// 2. Reviewer Stats
$stmt = $conn->prepare("
    SELECT COUNT(*) as pending 
    FROM loan_applications la 
    JOIN application_reviewers ar ON la.id = ar.application_id 
    WHERE ar.reviewer_id = ? AND la.current_stage = 'Reviewer'
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats['review_pending'] = $stmt->get_result()->fetch_assoc()['pending'];

// 3. Approver Stats
$stmt = $conn->prepare("SELECT COUNT(*) as pending FROM loan_applications WHERE approver_id = ? AND current_stage = 'Approver'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats['approval_pending'] = $stmt->get_result()->fetch_assoc()['pending'];


// Get consolidated recent activity (Initiated, Review, or Approve)
$stmt = $conn->prepare("
    SELECT la.*, GROUP_CONCAT(ar.reviewer_id) as reviewer_ids
    FROM loan_applications la 
    LEFT JOIN application_reviewers ar ON la.id = ar.application_id
    WHERE la.initiator_id = ? 
       OR ar.reviewer_id = ?
       OR la.approver_id = ?
    GROUP BY la.id
    ORDER BY la.updated_at DESC 
    LIMIT 10
");
$stmt->bind_param("iii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$recent_applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


include 'includes/header.php';
?>

<div class="row mb-4">
    <!-- Action Cards -->
    <div class="col-md-4">
        <div class="stat-card" style="border-left-color: #3b82f6;">
            <i class="fas fa-file-alt icon"></i>
            <h3><?php echo $stats['initiated_total']; ?></h3>
            <p>My Applications</p>
            <div class="mt-2 text-muted small">
                Pending: <strong><?php echo $stats['initiated_pending']; ?></strong> |
                Returned: <strong><?php echo $stats['initiated_returned']; ?></strong>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card" style="border-left-color: #f59e0b;">
            <i class="fas fa-tasks icon"></i>
            <h3><?php echo $stats['review_pending']; ?></h3>
            <p>Pending Reviews</p>
            <div class="mt-2">
                <a href="modules/reviewer/pending_reviews.php" class="btn btn-sm btn-outline-warning">Go to Reviews</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card" style="border-left-color: #10b981;">
            <i class="fas fa-check-circle icon"></i>
            <h3><?php echo $stats['approval_pending']; ?></h3>
            <p>Pending Approvals</p>
            <div class="mt-2">
                <a href="modules/approver/pending_approvals.php" class="btn btn-sm btn-outline-success">Go to
                    Approvals</a>
            </div>
        </div>
    </div>
</div>

<div class="table-card">
    <h4 class="mb-3">Recent Activity</h4>

    <?php if (empty($recent_applications)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No recent activity found.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>CAP ID</th>
                        <th>Applicant Name</th>
                        <th>Loan Type</th>
                        <th>Proposed Limit</th>
                        <th>Status</th>
                        <th>Stage</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_applications as $app): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($app['cap_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($app['applicant_name']); ?></td>
                            <td><?php echo htmlspecialchars($app['loan_type']); ?></td>
                            <td><?php echo formatCurrency($app['proposed_limit']); ?></td>
                            <td><span class="<?php echo getStatusBadge($app['status']); ?>"><?php echo $app['status']; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($app['current_stage']); ?></td>
                            <td><?php echo date('d M Y', strtotime($app['created_at'])); ?></td>
                            <td>
                                <?php
                                $reviewers = htmlspecialchars($app['reviewer_ids'] ?? '');
                                $is_assigned_reviewer = in_array($_SESSION['user_id'], explode(',', $reviewers));
                                ?>
                                <?php if ($app['current_stage'] == 'Reviewer' && $is_assigned_reviewer): ?>
                                    <a href="modules/reviewer/review_application.php?id=<?php echo $app['id']; ?>"
                                        class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Review
                                    </a>
                                <?php elseif ($app['current_stage'] == 'Approver' && $app['approver_id'] == $_SESSION['user_id']): ?>
                                    <a href="modules/approver/approve_application.php?id=<?php echo $app['id']; ?>"
                                        class="btn btn-sm btn-success">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                <?php else: ?>
                                    <a href="view_application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>