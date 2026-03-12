<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = sanitize($_POST['staff_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($staff_id) || empty($password)) {
        $error = 'Please enter both Staff ID and Password';
    } else {
        // Check user credentials
        $stmt = $conn->prepare("SELECT * FROM users WHERE staff_id = ? AND is_active = 1");
        $stmt->bind_param("s", $staff_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password (for default users, password is same as staff_id)
            // In production, use password_verify with hashed passwords
            if (password_verify($staff_id, $user['password']) || $password === $staff_id) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['staff_id'] = $user['staff_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['designation'] = $user['designation'];

                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid credentials';
            }
        } else {
            $error = 'Invalid credentials';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <i class="fas fa-university fa-3x text-primary mb-3"></i>
                <h2><?php echo APP_NAME; ?></h2>
                <p class="text-muted">Loan Credit Approval Workflow</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="staff_id" class="form-label">Staff ID</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="staff_id" name="staff_id"
                            placeholder="Enter your Staff ID" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="Enter your password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="mt-4 text-center">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> Test Users: 100, 101, 102, 103, 104<br>
                    Password: Same as Staff ID
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>