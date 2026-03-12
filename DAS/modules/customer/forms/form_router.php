<?php
/**
 * Form Router
 * Routes to appropriate form based on type (Individual/Corporate) and entity (Borrower/Guarantor)
 */

session_start();
require_once '../../../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../../login.php');
    exit;
}

// Get parameters
$entity = $_GET['entity'] ?? 'borrower'; // borrower or guarantor
$type = $_GET['type'] ?? 'Individual'; // Individual or Corporate
$profile_id = $_GET['profile_id'] ?? '';
$id = $_GET['id'] ?? null;
$view_mode = isset($_GET['view_mode']) && $_GET['view_mode'] == '1';

// Force Checker to View Mode
if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Checker') {
    $view_mode = true;
}

if (empty($profile_id)) {
    header('Location: ../create_customer.php');
    exit;
}

// Route to appropriate form
$formFile = '';

if ($entity == 'borrower') {
    if ($type == 'Individual') {
        $formFile = 'individual_borrower.php';
    } else {
        $formFile = 'corporate_borrower.php';
    }
} elseif ($entity == 'guarantor') {
    if ($type == 'Individual') {
        $formFile = 'individual_guarantor.php';
    } else {
        $formFile = 'corporate_guarantor.php';
    }
} else {
    // Fallback/Error
    echo "Invalid entity type.";
    exit;
}

// Include the appropriate form
$formPath = __DIR__ . '/' . $formFile;

if (file_exists($formPath)) {
    // Pass variables to the included file
    // These variables ($profile_id, $id, $view_mode, etc.) are already in scope
    include $formPath;
} else {
    // Fallback if file missing (should not happen if files created correctly)
    echo "Form file not found: $formFile";
    exit;
}
?>
