<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

requireLogin();
requireRole('Admin');

$success = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $staff_id = sanitize($_POST['staff_id']);
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $contact_number = sanitize($_POST['contact_number']);
        $province = sanitize($_POST['province']);
        $branch = sanitize($_POST['branch']);
        $sol_id = sanitize($_POST['sol_id']);
        $designation = sanitize($_POST['designation']);
        $role = sanitize($_POST['role']);
        $password = password_hash($staff_id, PASSWORD_DEFAULT); // Default password is staff_id

        $stmt = $conn->prepare("INSERT INTO users (staff_id, password, full_name, email, contact_number, province, branch, sol_id, designation, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $staff_id, $password, $full_name, $email, $contact_number, $province, $branch, $sol_id, $designation, $role);

        if ($stmt->execute()) {
            $success = 'User added successfully!';
        } else {
            $error = 'Failed to add user. Staff ID may already exist.';
        }
    } elseif ($action === 'toggle_status') {
        $user_id = intval($_POST['user_id']);
        $stmt = $conn->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $success = 'User status updated!';
    }
}

// Get all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

include '../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="form-card">
            <h5 class="mb-3"><i class="fas fa-user-plus"></i> Add New User</h5>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">

                <div class="mb-3">
                    <label class="form-label">Staff ID <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="staff_id" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="full_name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="contact_number" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Province</label>
                    <input type="text" class="form-control" name="province">
                </div>

                <div class="mb-3">
                    <label class="form-label">Branch</label>
                    <input type="text" class="form-control" name="branch">
                </div>

                <div class="mb-3">
                    <label class="form-label">SOL ID</label>
                    <input type="text" class="form-control" name="sol_id">
                </div>

                <div class="mb-3">
                    <label class="form-label">Designation <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="designation" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select class="form-select" name="role" required>
                        <option value="">Select Role</option>
                        <option value="Initiator">Initiator</option>
                        <option value="Reviewer">Reviewer</option>
                        <option value="Approver">Approver</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>

                <div class="alert alert-info">
                    <small><i class="fas fa-info-circle"></i> Default password will be same as Staff ID</small>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-plus"></i> Add User
                </button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="table-card">
            <h5 class="mb-3"><i class="fas fa-users"></i> User List</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Designation</th>
                            <th>Role</th>
                            <th>Branch</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['staff_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['designation']); ?></td>
                                <td><span class="badge bg-primary"><?php echo $user['role']; ?></span></td>
                                <td><?php echo htmlspecialchars($user['branch'] ?: 'N/A'); ?></td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                            onclick="return confirmAction('Toggle user status?')">
                                            <i class="fas fa-toggle-<?php echo $user['is_active'] ? 'on' : 'off'; ?>"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>