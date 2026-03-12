<?php
// Database Configuration for EDMS
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'edms_db');

// Credit Flow Database (for user authentication and CAP ID validation)
define('CF_DB_NAME', 'credit_flow_db');

// Application Configuration
define('APP_NAME', 'EDMS - Electronic Document Management System');
define('BASE_URL', 'http://localhost/Credit/edms/');
define('UPLOAD_DIR', __DIR__ . '/../../uploads/');
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);

// Create database connections
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("EDMS Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // Connection to Credit Flow DB for user authentication
    $cf_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, CF_DB_NAME);
    if ($cf_conn->connect_error) {
        die("Credit Flow Connection failed: " . $cf_conn->connect_error);
    }
    $cf_conn->set_charset("utf8mb4");
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

// Get current user info
function getCurrentUser()
{
    global $cf_conn;
    if (!isLoggedIn()) {
        return null;
    }

    $stmt = $cf_conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
?>