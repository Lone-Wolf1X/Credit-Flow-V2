<?php
// Guarantor Form Proxy
// Redirects to specific form based on Guarantor Type (Individual/Corporate)

require_once '../../../config/config.php';

// Get parameters
$profile_id = $_GET['profile_id'] ?? '';
$guarantor_id = $_GET['id'] ?? '';
$view_mode = isset($_GET['view_mode']) && $_GET['view_mode'] == '1';
$type = $_GET['type'] ?? 'Individual';

// Force Checker to View Mode
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Checker') {
    $view_mode = true;
}

// Fetch existing data if ID is present
$guarantor_data = [];
if ($guarantor_id) {
    $stmt = $conn->prepare("SELECT * FROM guarantors WHERE id = ?");
    $stmt->bind_param("i", $guarantor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $fetched_data = $result->fetch_assoc();
    
    if ($fetched_data) {
        $guarantor_data = $fetched_data;
        // Override type from data if editing
        $type = $guarantor_data['guarantor_type'];
    }
}

// Include appropriate form
if ($type === 'Corporate') {
    require_once 'corporate_guarantor.php';
} else {
    require_once 'individual_guarantor.php';
}
?>
