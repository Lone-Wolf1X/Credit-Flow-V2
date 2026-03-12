<?php
/**
 * Get Master Guarantor API
 */
require_once '../../config/config.php';
require_once '../../includes/borrower_master.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = intval($_GET['id']);
    
    $guarantor = getMasterGuarantor($id);
    
    if ($guarantor) {
        echo json_encode(['success' => true, 'data' => $guarantor]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Guarantor not found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
