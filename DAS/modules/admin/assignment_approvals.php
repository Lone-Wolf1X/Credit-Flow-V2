<?php
/**
 * Transfer & Deputation Approval Interface
 * Admin interface to approve/reject transfer and deputation requests
 */

require_once '../config/config.php';
require_once '../includes/DeputationManager.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Transfer & Deputation Approvals';
$success = '';
$error = '';

$deputationMgr = new DeputationManager($das_conn);

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignment_id = intval($_POST['assignment_id']);
    $admin_id = $_SESSION['user_id'];
    
    if (isset($_POST['approve'])) {
        $result = $deputationMgr->approveDeputation($assignment_id, $admin_id);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['error'];
        }
    } elseif (isset($_POST['reject'])) {
        $reason = trim($_POST['rejection_reason']);
        $result = $deputationMgr->rejectDeputation($assignment_id, $reason);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['error'];
        }
    }
}

// Fetch pending requests
$pending_requests = $das_conn->query("
    SELECT 
        uba.*,
        u.full_name as user_name,
        u.email,
        pb.branch_name_en as from_branch,
        tb.branch_name_en as to_branch,
        tb.branch_name_np as to_branch_np
    FROM user_branch_assignments uba
    JOIN users u ON uba.user_id = u.id
    LEFT JOIN branch_profiles pb ON u.primary_branch_id = pb.id
    JOIN branch_profiles tb ON uba.branch_id = tb.id
    WHERE uba.status = 'pending'
    ORDER BY uba.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Fetch expiring deputations
$expiring_deputations = $deputationMgr->getExpiringDeputations(7);

include '../includes/header.php';
?>

<div class="container-fluid">
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Expiring Deputations Alert -->
    <?php if (!empty($expiring_deputations)): ?>
        <div class="alert alert-warning">
            <h6><i class="bi bi-exclamation-triangle"></i> Expiring Deputations (Next 7 Days)</h6>
            <ul class="mb-0">
                <?php foreach ($expiring_deputations as $dep): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($dep['user_name']); ?></strong> 
                        at <?php echo htmlspecialchars($dep['deputation_branch']); ?>
                        - Expires in <?php echo $dep['days_remaining']; ?> day(s)
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Pending Requests -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Pending Requests (<?php echo count($pending_requests); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($pending_requests)): ?>
                <p class="text-muted">No pending requests.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Period</th>
                                <th>Reason</th>
                                <th>Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_requests as $req): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($req['user_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($req['email']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($req['assignment_type'] == 'transfer'): ?>
                                            <span class="badge bg-primary">Transfer</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Deputation</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($req['from_branch'] ?? '-'); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($req['to_branch']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($req['to_branch_np'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($req['assignment_type'] == 'deputation'): ?>
                                            <?php echo date('M d', strtotime($req['start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($req['end_date'])); ?>
                                        <?php else: ?>
                                            From <?php echo date('M d, Y', strtotime($req['start_date'])); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars(substr($req['request_reason'], 0, 100)); ?>...</small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($req['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-success" 
                                                onclick="approveRequest(<?php echo $req['id']; ?>, '<?php echo htmlspecialchars($req['user_name']); ?>', '<?php echo $req['assignment_type']; ?>')">
                                            <i class="bi bi-check"></i> Approve
                                        </button>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="rejectRequest(<?php echo $req['id']; ?>, '<?php echo htmlspecialchars($req['user_name']); ?>')">
                                            <i class="bi bi-x"></i> Reject
                                        </button>
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

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Approve Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="assignment_id" id="approve_id">
                    <p>Are you sure you want to approve this <strong id="approve_type"></strong> request for <strong id="approve_user"></strong>?</p>
                    <div class="alert alert-info">
                        <small>This will update the user's branch assignment immediately.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="approve" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Reject Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="assignment_id" id="reject_id">
                    <p>Rejecting request for <strong id="reject_user"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection *</label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="reject" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function approveRequest(id, userName, type) {
    document.getElementById('approve_id').value = id;
    document.getElementById('approve_user').textContent = userName;
    document.getElementById('approve_type').textContent = type;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function rejectRequest(id, userName) {
    document.getElementById('reject_id').value = id;
    document.getElementById('reject_user').textContent = userName;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
