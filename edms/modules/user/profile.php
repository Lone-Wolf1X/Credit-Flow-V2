<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $error = "All fields are required.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_pass) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM edms_users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows === 1) {
            $row = $res->fetch_assoc();
            if (password_verify($current_pass, $row['password'])) {
                // Update password
                $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE edms_users SET password = ? WHERE id = ?");
                $update->bind_param("si", $hashed_pass, $user_id);
                
                if ($update->execute()) {
                    $success = "Password updated successfully!";
                } else {
                    $error = "Failed to update password.";
                }
            } else {
                $error = "Incorrect current password.";
            }
        } else {
            $error = "User not found.";
        }
    }
}

// Get User Details
$stmt = $conn->prepare("SELECT full_name, email, role, designation FROM edms_users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h4 class="mb-4 fw-bold text-dark"><i class="fas fa-user-circle text-primary me-2"></i>My Profile</h4>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- User Info Card -->
                <div class="col-md-5">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <?php echo generateAvatar($user['full_name'], $user['role']); ?>
                            </div>
                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($user['designation']); ?></p>
                            
                            <div class="d-inline-block bg-light px-3 py-1 rounded-pill border">
                                <small class="fw-bold text-secondary"><?php echo htmlspecialchars($user['role']); ?></small>
                            </div>

                            <hr class="my-4">

                            <div class="text-start">
                                <div class="mb-3">
                                    <label class="small text-muted text-uppercase fw-bold">Email Address</label>
                                    <div class="d-flex align-items-center mt-1">
                                        <i class="fas fa-envelope text-secondary me-2"></i>
                                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Password Change Card -->
                <div class="col-md-7">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-lock me-2"></i>Change Password</h6>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" minlength="6" required>
                                    <div class="form-text">Minimum 6 characters</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
