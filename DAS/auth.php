<?php
session_start();
require_once 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($staff_id) || empty($password)) {
        $_SESSION['error'] = 'Please enter both Staff ID and password';
        header('Location: login.php');
        exit;
    }
    
    // Query database for user
    $stmt = $conn->prepare("SELECT id, staff_id, password, full_name, designation, role, is_active FROM users WHERE staff_id = ?");
    $stmt->bind_param("s", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if user is active
        if ($user['is_active'] != 1) {
            $_SESSION['error'] = 'Your account has been deactivated. Please contact administrator.';
            header('Location: login.php');
            exit;
        }
        
        // Verify password (password is staff_id for now, but hashed)
        // For initial setup, we'll check if password matches staff_id
        if (password_verify($password, $user['password']) || $password === $staff_id) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['staff_id'] = $user['staff_id'];
            $_SESSION['username'] = $user['staff_id'];
            $_SESSION['role_name'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['designation'] = $user['designation'];
            $_SESSION['logged_in'] = true;
            
            // Update last login
            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            
            // Log audit
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $audit_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'Login', 'User logged in successfully', ?)");
            $audit_stmt->bind_param("is", $user['id'], $ip_address);
            $audit_stmt->execute();
            
            // Redirect based on role
            switch ($user['role']) {
                case 'Admin':
                    header('Location: modules/dashboard/admin_dashboard.php');
                    break;
                case 'Maker':
                    header('Location: modules/dashboard/maker_dashboard.php');
                    break;
                case 'Checker':
                    header('Location: modules/dashboard/checker_dashboard.php');
                    break;
                default:
                    header('Location: modules/dashboard/maker_dashboard.php');
            }
            exit;
        } else {
            $_SESSION['error'] = 'Invalid Staff ID or password';
            header('Location: login.php');
            exit;
        }
    } else {
        $_SESSION['error'] = 'Invalid Staff ID or password';
        header('Location: login.php');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
}
?>
