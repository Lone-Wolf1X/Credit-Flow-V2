<?php
// Database Configuration for Credit Flow System
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'credit_flow_db');

// Application Configuration
define('APP_NAME', 'Credit Flow System');
define('BASE_URL', 'http://localhost/Credit/credit_flow/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx']);

// Create database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['staff_id']);
}

// Check user role
function hasRole($role)
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect if not logged in
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

// Redirect if not authorized
function requireRole($role)
{
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    }
}

// Get current user info
function getCurrentUser()
{
    global $conn;
    if (!isLoggedIn()) {
        return null;
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
?>