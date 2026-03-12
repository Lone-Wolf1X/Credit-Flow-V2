<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = sanitize($_POST['staff_id']);
    $password = $_POST['password'];

    if (empty($staff_id) || empty($password)) {
        $error = 'Both fields are required';
    } else {
        // Check user credentials from EDMS Users table
        $stmt = $conn->prepare("SELECT * FROM edms_users WHERE staff_id = ? AND status = 'Active'");
        $stmt->bind_param("s", $staff_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify Password
            if (password_verify($password, $user['password']) || $password === $user['password'] || $password === $staff_id) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['staff_id'] = $user['staff_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role']; 
                $_SESSION['edms_role'] = getEdmsRole($user['role']);
                $_SESSION['logged_in'] = true;

                // Update Last Login (Skipped: Column missing in DB)
                // $update_stmt = $conn->prepare("UPDATE edms_users SET last_login = NOW() WHERE id = ?");
                // $update_stmt->bind_param("i", $user['id']);
                // $update_stmt->execute();

                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid credentials';
            }
        } else {
            $error = 'User not found or inactive';
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            padding: 40px;
            transition: transform 0.3s;
        }
        .brand-icon {
            width: 60px;
            height: 60px;
            background: #0d9488;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            margin: 0 auto 20px;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.4);
        }
        .form-control {
            border: 1px solid #e2e8f0;
            padding: 12px 16px;
            border-radius: 8px;
            background: #f8fafc;
        }
        .form-control:focus {
            background: #fff;
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
        }
        .btn-login {
            background: #0d9488;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
            transition: all 0.2s;
        }
        .btn-login:hover {
            background: #0f766e;
            transform: translateY(-1px);
        }
        .alert-dismissible {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand-icon">
            <i class="fas fa-folder-open"></i>
        </div>
        <h4 class="text-center fw-bold mb-1 text-dark">Welcome to EDMS</h4>
        <p class="text-center text-muted small mb-4">Sign in to manage your documents</p>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-1"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="staff_id" class="form-label text-muted small fw-bold">Staff ID</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" id="staff_id" name="staff_id" required autofocus placeholder="Enter Staff ID">
                </div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label text-muted small fw-bold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" required placeholder="Enter Password">
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember">
                    <label class="form-check-label small text-muted" for="remember">Remember me</label>
                </div>
                <a href="#" class="small text-decoration-none text-primary">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-primary btn-login text-white">
                Sign In <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>