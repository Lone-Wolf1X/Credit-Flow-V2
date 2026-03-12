<?php
/**
 * Transfer Request Form
 * Allows users to request permanent transfer to another branch
 */

require_once '../config/config.php';
require_once '../includes/DeputationManager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Request Transfer';
$success = '';
$error = '';
$user_id = $_SESSION['user_id'];

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

// Handle transfer request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_transfer'])) {
    $to_branch_id = intval($_POST['to_branch_id']);
    $reason = trim($_POST['reason']);
    
    if (empty($reason)) {
        $error = "Please provide a reason for transfer.";
    } elseif ($to_branch_id == $user['primary_branch_id']) {
        $error = "You are already assigned to this branch.";
    } else {
        // Create transfer request
        $start_date = $_POST['start_date'];
        
        $stmt = $das_conn->prepare("
            INSERT INTO user_branch_assignments 
            (user_id, branch_id, assignment_type, start_date, status, requested_by, request_reason)
            VALUES (?, ?, 'transfer', ?, 'pending', ?, ?)
        ");
        $stmt->bind_param("iisis", $user_id, $to_branch_id, $start_date, $user_id, $reason);
        
        if ($stmt->execute()) {
            $success = "Transfer request submitted successfully! Awaiting admin approval.";
        } else {
            $error = "Failed to submit request: " . $stmt->error;
        }
    }
}

// Fetch all branches
$branches = $das_conn->query("
    SELECT * FROM branch_profiles 
    WHERE is_active = TRUE 
    ORDER BY branch_name_en ASC
")->fetch_all(MYSQLI_ASSOC);

// Fetch user's transfer history
$history = $das_conn->prepare("
    SELECT uba.*, b.branch_name_en, b.branch_name_np,
           app.full_name as approved_by_name
    FROM user_branch_assignments uba
    JOIN branch_profiles b ON uba.branch_id = b.id
    LEFT JOIN users app ON uba.approved_by = app.id
    WHERE uba.user_id = ? AND uba.assignment_type = 'transfer'
    ORDER BY uba.created_at DESC
");
$history->bind_param("i", $user_id);
$history->execute();
$transfer_history = $history->get_result()->fetch_all(MYSQLI_ASSOC);

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
                    <strong>Province:</strong> <?php echo htmlspecialchars($user['province'] ?? '-'); ?><br>
                    <strong>District:</strong> <?php echo htmlspecialchars($user['district'] ?? '-'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer Request Form -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Request Permanent Transfer</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Transfer To Branch *</label>
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
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Effective From *</label>
                        <input type="date" name="start_date" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Reason for Transfer *</label>
                    <textarea name="reason" class="form-control" rows="4" 
                              placeholder="Please provide detailed reason for requesting transfer..." required></textarea>
                </div>
                
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle"></i> <strong>Note:</strong> Permanent transfer will change your primary branch. This request requires admin approval.
                </div>
                
                <button type="submit" name="request_transfer" class="btn btn-primary">
                    <i class="bi bi-send"></i> Submit Transfer Request
                </button>
            </form>
        </div>
    </div>

    <!-- Transfer History -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Transfer History</h5>
        </div>
        <div class="card-body">
            <?php if (empty($transfer_history)): ?>
                <p class="text-muted">No transfer requests yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>To Branch</th>
                                <th>Start Date</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Approved By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transfer_history as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['branch_name_en']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($item['start_date'])); ?></td>
                                    <td>
                                        <?php
                                        $badge_class = [
                                            'pending' => 'bg-warning',
                                            'active' => 'bg-success',
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
