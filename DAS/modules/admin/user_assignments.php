<?php
/**
 * User Branch Assignment Management
 * Admin interface to assign users to branches
 */

require_once '../config/config.php';
require_once '../includes/DeputationManager.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = 'User Branch Assignments';
$success = '';
$error = '';

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_user'])) {
    $user_id = intval($_POST['user_id']);
    $branch_id = intval($_POST['branch_id']);
    $assignment_type = $_POST['assignment_type'];
    
    // Update user's primary branch
    $stmt = $das_conn->prepare("
        UPDATE users 
        SET primary_branch_id = ?, current_branch_id = ?
        WHERE id = ?
    ");
    $stmt->bind_param("iii", $branch_id, $branch_id, $user_id);
    
    if ($stmt->execute()) {
        // Create assignment record
        $admin_id = $_SESSION['user_id'];
        $start_date = date('Y-m-d');
        
        $stmt = $das_conn->prepare("
            INSERT INTO user_branch_assignments 
            (user_id, branch_id, assignment_type, start_date, status, is_current, approved_by, approved_at)
            VALUES (?, ?, 'primary', ?, 'active', TRUE, ?, NOW())
        ");
        $stmt->bind_param("iisi", $user_id, $branch_id, $start_date, $admin_id);
        $stmt->execute();
        
        $success = "User assigned to branch successfully!";
    } else {
        $error = "Failed to assign user: " . $stmt->error;
    }
}

// Fetch all users
$users = $das_conn->query("
    SELECT u.*, 
           pb.branch_name_en as primary_branch,
           cb.branch_name_en as current_branch
    FROM users u
    LEFT JOIN branch_profiles pb ON u.primary_branch_id = pb.id
    LEFT JOIN branch_profiles cb ON u.current_branch_id = cb.id
    ORDER BY u.full_name ASC
")->fetch_all(MYSQLI_ASSOC);

// Fetch all branches
$branches = $das_conn->query("
    SELECT * FROM branch_profiles 
    WHERE is_active = TRUE 
    ORDER BY branch_name_en ASC
")->fetch_all(MYSQLI_ASSOC);

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

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-people me-2"></i>User Branch Assignments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Primary Branch</th>
                            <th>Current Branch</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span class="badge bg-info"><?php echo $user['role']; ?></span></td>
                                <td>
                                    <?php if ($user['primary_branch']): ?>
                                        <?php echo htmlspecialchars($user['primary_branch']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['current_branch']): ?>
                                        <?php echo htmlspecialchars($user['current_branch']); ?>
                                        <?php if ($user['current_branch'] != $user['primary_branch']): ?>
                                            <span class="badge bg-warning text-dark">Deputed</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['primary_branch_id']): ?>
                                        <span class="badge bg-success">Assigned</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="assignUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>', <?php echo $user['primary_branch_id'] ?? 'null'; ?>)">
                                        <i class="bi bi-pencil"></i> Assign
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Assignment Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Assign User to Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="assign_user_id">
                    <input type="hidden" name="assignment_type" value="primary">
                    
                    <div class="alert alert-info">
                        <strong>User:</strong> <span id="assign_user_name"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Select Branch *</label>
                        <select name="branch_id" class="form-select" required>
                            <option value="">Choose branch...</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>">
                                    <?php echo htmlspecialchars($branch['branch_name_en']); ?>
                                    (<?php echo htmlspecialchars($branch['branch_code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="alert alert-warning">
                        <small><i class="bi bi-info-circle"></i> This will set the user's primary branch. They will be able to request transfers or deputations later.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="assign_user" class="btn btn-primary">Assign Branch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function assignUser(userId, userName, currentBranchId) {
    document.getElementById('assign_user_id').value = userId;
    document.getElementById('assign_user_name').textContent = userName;
    
    if (currentBranchId) {
        document.querySelector('select[name="branch_id"]').value = currentBranchId;
    }
    
    new bootstrap.Modal(document.getElementById('assignModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
