<?php
session_start();
require_once '../../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit;
}

// Get user details with branch information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT u.*, 
           pb.branch_name_en as primary_branch_name,
           pb.branch_name_np as primary_branch_name_np,
           pb.province_np as branch_province,
           pb.district_np as branch_district,
           cb.branch_name_en as current_branch_name,
           cb.branch_name_np as current_branch_name_np
    FROM users u
    LEFT JOIN branch_profiles pb ON u.primary_branch_id = pb.id
    LEFT JOIN branch_profiles cb ON u.current_branch_id = cb.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Check for active deputation
require_once '../../includes/DeputationManager.php';
$deputationMgr = new DeputationManager($conn);
$active_deputation = $deputationMgr->getActiveDeputation($user_id);

// Handle profile update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $designation = trim($_POST['designation']);
    
    if (empty($full_name)) {
        $error = 'Full name is required';
    } else {
        $update_stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, designation = ? WHERE id = ?");
        $update_stmt->bind_param("ssssi", $full_name, $email, $phone, $designation, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $success = 'Profile updated successfully!';
            
            // Log audit
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $audit_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'Profile Update', 'User updated profile information', ?)");
            $audit_stmt->bind_param("is", $user_id, $ip_address);
            $audit_stmt->execute();
            
            // Refresh user data
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            $error = 'Failed to update profile';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Verify current password
        if (password_verify($current_password, $user['password']) || $current_password === $user['staff_id']) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $pwd_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $pwd_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($pwd_stmt->execute()) {
                $success = 'Password changed successfully!';
                
                // Log audit
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                $audit_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'Password Change', 'User changed password', ?)");
                $audit_stmt->bind_param("is", $user_id, $ip_address);
                $audit_stmt->execute();
            } else {
                $error = 'Failed to change password';
            }
        } else {
            $error = 'Current password is incorrect';
        }
    }
}

// Handle Transfer Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_transfer'])) {
    $to_branch_id = $_POST['to_branch_id'];
    $reason = trim($_POST['reason']);
    $from_branch_id = $user['current_branch_id'] ?? $user['primary_branch_id'];
    
    if (empty($to_branch_id) || empty($reason)) {
        $error = 'Branch and Reason are required for transfer.';
    } elseif ($to_branch_id == $from_branch_id) {
        $error = 'Cannot transfer to the same branch.';
    } else {
        // Check if pending request exists
        $check = $conn->prepare("SELECT id FROM user_branch_transfers WHERE user_id = ? AND status = 'Pending'");
        $check->bind_param("i", $user_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'You already have a pending transfer request.';
        } else {
            $stmt = $conn->prepare("INSERT INTO user_branch_transfers (user_id, from_branch_id, to_branch_id, reason) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $user_id, $from_branch_id, $to_branch_id, $reason);
            if ($stmt->execute()) {
                $success = 'Transfer request submitted successfully!';
            } else {
                $error = 'Failed to submit request: ' . $conn->error;
            }
        }
    }
}

// Fetch Active Branches for Dropdown
$branches = $conn->query("SELECT id, branch_name_en as branch_name, branch_name_np, province_np FROM branch_profiles WHERE is_active = 1 ORDER BY branch_name_en")->fetch_all(MYSQLI_ASSOC);

// Check Transfer Status
$transfer_status = null;
$ts_query = $conn->prepare("SELECT t.*, b.branch_name_en as target_branch, b.branch_name_np as target_branch_np FROM user_branch_transfers t JOIN branch_profiles b ON t.to_branch_id = b.id WHERE t.user_id = ? ORDER BY t.requested_at DESC LIMIT 1");
$ts_query->bind_param("i", $user_id);
$ts_query->execute();
$transfer_request = $ts_query->get_result()->fetch_assoc();

// Get user statistics
$stats_query = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM customers WHERE created_by = ?) as total_customers,
        (SELECT COUNT(*) FROM generated_documents WHERE generated_by = ?) as total_documents,
        (SELECT COUNT(*) FROM audit_logs WHERE user_id = ?) as total_activities
");
$stats_query->bind_param("iii", $user_id, $user_id, $user_id);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();

// Page variables for layout
$pageTitle = 'My Profile';
$activeMenu = 'profile';
$userName = $_SESSION['full_name'] ?? 'User';
$userAvatar = '../../asstes/images/img_avatar.png';
$badgeText = $_SESSION['role_name'] ?? 'User';

// Start output buffering for main content
ob_start();
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

    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-person-circle me-2"></i>My Profile
            </h2>
            <p class="text-muted">Manage your account settings and preferences</p>
        </div>
    </div>

    <div class="row">
        <!-- Profile Card -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img src="<?php echo $userAvatar; ?>" alt="Profile" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #f0f0f0;">
                    </div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                    <p class="text-muted mb-2"><?php echo htmlspecialchars($user['designation']); ?></p>
                    <span class="badge bg-primary px-3 py-2"><?php echo htmlspecialchars($user['role']); ?></span>
                    
                    <hr class="my-4">
                    
                    <div class="text-start">
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Staff ID</small>
                            <strong><?php echo htmlspecialchars($user['staff_id']); ?></strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Email</small>
                            <strong><?php echo htmlspecialchars($user['email'] ?? 'Not set'); ?></strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Phone</small>
                            <strong><?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Province</small>
                            <strong><?php echo htmlspecialchars($user['province'] ?? $user['branch_province'] ?? 'Not set'); ?></strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">District</small>
                            <strong><?php echo htmlspecialchars($user['district'] ?? $user['branch_district'] ?? 'Not set'); ?></strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">SOL ID</small>
                            <strong><?php echo htmlspecialchars($user['sol_id'] ?? 'Not set'); ?></strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Primary Branch</small>
                            <strong><?php echo htmlspecialchars($user['primary_branch_name'] ?? 'Not assigned'); ?></strong>
                            <?php if (!empty($user['primary_branch_name_np'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($user['primary_branch_name_np']); ?></small>
                            <?php endif; ?>
                        </div>
                        <?php if ($active_deputation): ?>
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Current Assignment</small>
                                <div class="alert alert-warning mb-0 py-2 px-3">
                                    <strong><i class="bi bi-clock"></i> On Deputation</strong><br>
                                    <small><?php echo htmlspecialchars($active_deputation['branch_name_en']); ?></small><br>
                                    <small class="text-muted">Until: <?php echo date('M d, Y', strtotime($active_deputation['end_date'])); ?></small>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Last Login</small>
                            <strong><?php echo $user['last_login'] ? date('M d, Y h:i A', strtotime($user['last_login'])) : 'Never'; ?></strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Member Since</small>
                            <strong><?php echo date('M d, Y', strtotime($user['created_at'])); ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>My Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <small class="text-muted d-block">Customers Created</small>
                            <h4 class="mb-0"><?php echo $stats['total_customers']; ?></h4>
                        </div>
                        <i class="bi bi-people fs-2 text-primary"></i>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <small class="text-muted d-block">Documents Generated</small>
                            <h4 class="mb-0"><?php echo $stats['total_documents']; ?></h4>
                        </div>
                        <i class="bi bi-file-earmark-text fs-2 text-success"></i>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Total Activities</small>
                            <h4 class="mb-0"><?php echo $stats['total_activities']; ?></h4>
                        </div>
                        <i class="bi bi-activity fs-2 text-info"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Settings -->
        <div class="col-lg-8">
            <!-- Edit Profile -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Designation</label>
                                <input type="text" class="form-control" name="designation" value="<?php echo htmlspecialchars($user['designation']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Update Profile
                        </button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Current Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="new_password" required minlength="6">
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="confirm_password" required minlength="6">
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="bi bi-key me-2"></i>Change Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Transfer Request -->
            <div class="card border-0 shadow-sm border-start border-info border-5">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Branch Transfer</h5>
                </div>
                <div class="card-body">
                    <?php if ($transfer_request && $transfer_request['status'] === 'Pending'): ?>
                        <div class="alert alert-info">
                            <h5 class="alert-heading"><i class="bi bi-hourglass-split"></i> Request Pending</h5>
                            <p class="mb-0">You have requested a transfer to <strong><?php echo htmlspecialchars($transfer_request['target_branch']); ?></strong>.</p>
                            <small class="text-muted">Requested on: <?php echo date('M d, Y h:i A', strtotime($transfer_request['requested_at'])); ?></small>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-3">Need to move to another branch? Request a transfer here. Admin approval required.</p>
                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to request a transfer?');">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Target Branch <span class="text-danger">*</span></label>
                                    <select class="form-select" name="to_branch_id" required>
                                        <option value="">Select Branch</option>
                                        <?php foreach ($branches as $br): ?>
                                            <?php if ($br['id'] != ($user['current_branch_id'] ?? $user['primary_branch_id'])): ?>
                                                <option value="<?php echo $br['id']; ?>">
                                                    <?php echo htmlspecialchars($br['branch_name']); ?> 
                                                    (<?php echo htmlspecialchars($br['province_np']); ?>)
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Reason</label>
                                    <textarea class="form-control" name="reason" rows="1" placeholder="Why do you need this transfer?" required></textarea>
                                </div>
                            </div>
                            <button type="submit" name="request_transfer" class="btn btn-info text-white">
                                <i class="bi bi-send me-2"></i>Submit Request
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($transfer_request && $transfer_request['status'] !== 'Pending'): ?>
                        <hr>
                        <small class="text-muted d-block">Last Request:</small>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                             <span>To: <strong><?php echo htmlspecialchars($transfer_request['target_branch']); ?></strong></span>
                             <span class="badge bg-<?php echo $transfer_request['status'] === 'Approved' ? 'success' : 'danger'; ?>">
                                 <?php echo $transfer_request['status']; ?>
                             </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$mainContent = ob_get_clean();

// Include the layout
include '../../Layout/layout_new.php';
?>
