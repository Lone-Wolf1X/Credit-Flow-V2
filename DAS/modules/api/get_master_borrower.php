<?php
/**
 * Get Master Borrower API
 */
require_once '../../config/config.php';
require_once '../../includes/borrower_master.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = intval($_GET['id']);
    
   $borrower = getMasterBorrower($id);
    
    if ($borrower) {
        echo json_encode(['success' => true, 'data' => $borrower]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Borrower not found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
