<?php
/**
 * Deputation Request Form
 * Allows users to request temporary deputation to another branch
 */

require_once '../config/config.php';
require_once '../includes/DeputationManager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Request Deputation';
$success = '';
$error = '';
$user_id = $_SESSION['user_id'];

$deputationMgr = new DeputationManager($das_conn);

// Get user's current branch
$user_stmt = $das_conn->prepare("
    SELECT u.*, pb.branch_name_en as primary_branch
    FROM users u
    LEFT JOIN branch_profiles pb ON u.primary_branch_id = pb.id
    WHERE u.id = ?
");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Handle deputation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_deputation'])) {
    $to_branch_id = intval($_POST['to_branch_id']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = trim($_POST['reason']);
    
    if (empty($reason)) {
        $error = "Please provide a reason for deputation.";
    } elseif ($to_branch_id == $user['primary_branch_id']) {
        $error = "Cannot request deputation to your primary branch.";
    } else {
        $result = $deputationMgr->requestDeputation($user_id, $to_branch_id, $start_date, $end_date, $reason);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['error'];
        }
    }
}

// Fetch all branches
$branches = $das_conn->query("
    SELECT * FROM branch_profiles 
    WHERE is_active = TRUE 
    ORDER BY branch_name_en ASC
")->fetch_all(MYSQLI_ASSOC);

// Fetch user's deputation history
$deputation_history = $deputationMgr->getDeputationHistory($user_id);

// Check for active deputation
$active_deputation = $deputationMgr->getActiveDeputation($user_id);

include '../includes/header.php';
?>

<div class="container">
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

    <!-- Current Assignment -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-building me-2"></i>Current Assignment</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <strong>Primary Branch:</strong><br>
                    <?php echo htmlspecialchars($user['primary_branch'] ?? 'Not Assigned'); ?>
                </div>
                <div class="col-md-6">
                    <?php if ($active_deputation): ?>
                        <div class="alert alert-warning mb-0">
                            <strong><i class="bi bi-clock"></i> Active Deputation:</strong><br>
                            <?php echo htmlspecialchars($active_deputation['branch_name_en']); ?><br>
                            <small>Until: <?php echo date('M d, Y', strtotime($active_deputation['end_date'])); ?></small>
                        </div>
                    <?php else: ?>
                        <span class="text-muted">No active deputation</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Deputation Request Form -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Request Temporary Deputation</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Deputation To Branch *</label>
                        <select name="to_branch_id" class="form-select" required>
                            <option value="">Select destination branch...</option>
                            <?php foreach ($branches as $branch): ?>
                                <?php if ($branch['id'] != $user['primary_branch_id']): ?>
                                    <option value="<?php echo $branch['id']; ?>">
                                        <?php echo htmlspecialchars($branch['branch_name_en']); ?>
                                        - <?php echo htmlspecialchars($branch['district_np'] ?? ''); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Start Date *</label>
                        <input type="date" name="start_date" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label class="form-label">End Date *</label>
                        <input type="date" name="end_date" class="form-control" 
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Purpose/Reason for Deputation *</label>
                    <textarea name="reason" class="form-control" rows="4" 
                              placeholder="Please provide detailed purpose for requesting deputation..." required></textarea>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> <strong>Note:</strong> 
                    <ul class="mb-0">
                        <li>Deputation is temporary - you will return to your primary branch after the end date</li>
                        <li>Documents generated during deputation will use the deputation branch details</li>
                        <li>This request requires admin approval</li>
                    </ul>
                </div>
                
                <button type="submit" name="request_deputation" class="btn btn-primary">
                    <i class="bi bi-send"></i> Submit Deputation Request
                </button>
            </form>
        </div>
    </div>

    <!-- Deputation History -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Deputation History</h5>
        </div>
        <div class="card-body">
            <?php if (empty($deputation_history)): ?>
                <p class="text-muted">No deputation requests yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>To Branch</th>
                                <th>Period</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Approved By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deputation_history as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['branch_name_en']); ?></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($item['start_date'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($item['end_date'])); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badge_class = [
                                            'pending' => 'bg-warning',
                                            'active' => 'bg-success',
                                            'expired' => 'bg-secondary',
                                            'rejected' => 'bg-danger',
                                            'cancelled' => 'bg-secondary'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $badge_class[$item['status']] ?? 'bg-secondary'; ?>">
                                            <?php echo ucfirst($item['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($item['approved_by_name'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
