<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'das_db');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$das_conn = &$conn; // Alias for compatibility with newer modules

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../../login.php');
        exit;
    }
}

// Application settings
define('APP_NAME', 'Document Automation System');
define('APP_VERSION', '1.0.0');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10485760); // 10MB

// Timezone
// Email Configuration (SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'abhi.pwn2020@gmail.com'); // TODO: Update with actual email
define('SMTP_PASS', 'frhg swvm kjnm mpgu');     // TODO: Update with App Password
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'no-reply@credit-das.com');
define('SMTP_FROM_NAME', 'Credit DAS System');

