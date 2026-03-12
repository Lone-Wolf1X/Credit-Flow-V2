<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/folder_manager.php';
require_once __DIR__ . '/notification_system.php';

/**
 * Map Credit Flow roles to EDMS roles
 */
function getEdmsRole($cf_role)
{
    $map = [
        'Initiator' => 'Maker',
        'Maker'     => 'Maker',
        'Reviewer'  => 'Checker',
        'Checker'   => 'Checker',
        'Legal'     => 'Checker',
        'Approver'  => 'Checker', // Mapped Approver to Checker for EDMS review
        'Admin'     => 'Admin'
    ];
    return $map[$cf_role] ?? 'Viewer';
}

/**
 * Validate CAP ID from Credit Flow system
 */
function validateCapId($cap_id)
{
    // For smooth testing, we will bypass this validation for now as requested.
    // In production, uncomment the logic below.
    return ['valid' => true, 'message' => 'Valid CAP ID (Testing Mode)'];

    /*
    global $cf_conn;

    $stmt = $cf_conn->prepare("SELECT id, status FROM loan_applications WHERE cap_id = ?");
    $stmt->bind_param("s", $cap_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ['valid' => false, 'message' => 'CAP ID not found in Credit Flow system'];
    }

    $app = $result->fetch_assoc();
    if ($app['status'] !== 'Approved') {
        return ['valid' => false, 'message' => 'CAP ID must be Approved. Current status: ' . $app['status']];
    }

    return ['valid' => true, 'message' => 'Valid CAP ID'];
    */
}

/**
 * Upload document with folder structure
 */
function uploadDocumentToFolder($file, $customer_id, $cap_id, $category, $document_type, $remark, $user_id)
{
    global $conn;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds limit (10MB)'];
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX'];
    }

    // Get customer details
    $customer = getCustomer($customer_id);
    if (!$customer) {
        return ['success' => false, 'message' => 'Customer not found'];
    }

    // Get document path based on category
    $upload_path = getDocumentPath($customer['customer_name'], $customer['client_id'], $cap_id, $category);

    // Ensure folder exists
    if (!ensureFolderExists($upload_path)) {
        return ['success' => false, 'message' => 'Failed to create upload directory'];
    }

    // Generate unique filename
    $stored_filename = uniqid() . '_' . time() . '.' . $extension;
    $file_path = $upload_path . $stored_filename;

    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        return ['success' => false, 'message' => 'Failed to save file'];
    }

    // Determine Draft Status
    // Legal/Critical docs remain Draft (1) until Submitted. General/Security are effectively published (0).
    $is_draft = ($category === 'Legal' || $category === 'Critical') ? 1 : 0;

    // Save to database
    $stmt = $conn->prepare("INSERT INTO customer_documents (customer_id, cap_id, document_category, document_type, original_filename, stored_filename, file_path, file_size, remark, uploaded_by, is_draft) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssii", $customer_id, $cap_id, $category, $document_type, $file['name'], $stored_filename, $file_path, $file['size'], $remark, $user_id, $is_draft);

    if ($stmt->execute()) {
        $document_id = $conn->insert_id;

        // Add audit log
        addDocumentAudit($document_id, $customer_id, $cap_id, $user_id, 'Upload', "Uploaded $category document: $document_type");

        return ['success' => true, 'message' => 'Document uploaded successfully', 'document_id' => $document_id];
    } else {
        unlink($file_path);
        return ['success' => false, 'message' => 'Database error'];
    }
}

/**
 * Lock all documents for a CAP ID
 */
function lockCapDocuments($cap_id)
{
    global $conn;

    $stmt = $conn->prepare("UPDATE customer_documents SET is_locked = 1 WHERE cap_id = ?");
    $stmt->bind_param("s", $cap_id);
    return $stmt->execute();
}

/**
 * Lock all documents for a customer
 */
function lockCustomerDocuments($customer_id)
{
    global $conn;

    $stmt = $conn->prepare("UPDATE customer_documents SET is_locked = 1 WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    return $stmt->execute();
}

/**
 * Add document audit log
 */
function addDocumentAudit($document_id, $customer_id, $cap_id, $user_id, $action, $details = null)
{
    global $conn;

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $conn->prepare("INSERT INTO document_audit (document_id, customer_id, cap_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisisss", $document_id, $customer_id, $cap_id, $user_id, $action, $details, $ip_address);
    return $stmt->execute();
}

/**
 * Add general audit log
 */
function addAuditLog($user_id, $action, $entity_type, $entity_id, $details = null)
{
    global $conn;

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $conn->prepare("INSERT INTO edms_audit_log (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $user_id, $action, $entity_type, $entity_id, $details, $ip_address);
    return $stmt->execute();
}

/**
 * Get customer by ID
 */
function getCustomer($id)
{
    global $conn, $cf_conn;

    $stmt = $conn->prepare("SELECT c.*, u.full_name as created_by_name FROM customers c LEFT JOIN edms_users u ON c.created_by = u.id WHERE c.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get customer documents by CAP ID and category
 */
function getCustomerDocuments($customer_id, $cap_id, $category = null)
{
    global $conn, $cf_conn;

    if ($category) {
        $stmt = $conn->prepare("SELECT cd.*, u.full_name as uploaded_by_name FROM customer_documents cd LEFT JOIN edms_users u ON cd.uploaded_by = u.id WHERE cd.customer_id = ? AND cd.cap_id = ? AND cd.document_category = ? ORDER BY cd.uploaded_at DESC");
        $stmt->bind_param("iss", $customer_id, $cap_id, $category);
    } else {
        $stmt = $conn->prepare("SELECT cd.*, u.full_name as uploaded_by_name FROM customer_documents cd LEFT JOIN edms_users u ON cd.uploaded_by = u.id WHERE cd.customer_id = ? AND cd.cap_id = ? ORDER BY cd.uploaded_at DESC");
        $stmt->bind_param("is", $customer_id, $cap_id);
    }

    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all CAP documents for customer
 */
function getCustomerCapDocuments($customer_id)
{
    global $conn, $cf_conn;

    $stmt = $conn->prepare("SELECT cd.*, u1.full_name as submitted_by_name, u2.full_name as reviewed_by_name FROM cap_documents cd LEFT JOIN edms_users u1 ON cd.submitted_by = u1.id LEFT JOIN edms_users u2 ON cd.reviewed_by = u2.id WHERE cd.customer_id = ? ORDER BY cd.submitted_at DESC");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get legal comments
 */
function getLegalComments($cap_document_id)
{
    global $conn, $cf_conn;

    $stmt = $conn->prepare("SELECT lc.*, u.full_name, u.designation FROM legal_comments lc LEFT JOIN edms_users u ON lc.user_id = u.id WHERE lc.cap_document_id = ? ORDER BY lc.created_at ASC");
    $stmt->bind_param("i", $cap_document_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get document dropdowns by category
 */
function getDocumentDropdowns($category)
{
    global $conn;

    $stmt = $conn->prepare("SELECT document_name FROM document_dropdowns WHERE category = ? AND is_active = 1 ORDER BY display_order, document_name");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return array_column($result, 'document_name');
}

/**
 * Check if CAP is locked
 */
function isCapLocked($cap_id)
{
    global $conn;

    $stmt = $conn->prepare("SELECT is_locked FROM cap_documents WHERE cap_id = ? LIMIT 1");
    $stmt->bind_param("s", $cap_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result ? (bool) $result['is_locked'] : false;
}

/**
 * Sanitize input
 */
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Get status badge class
 */
function getStatusBadge($status)
{
    $badges = [
        'Draft' => 'badge bg-secondary',
        'Pending' => 'badge bg-warning text-dark',
        'Pending Legal' => 'badge bg-info',
        'Picked' => 'badge bg-primary',
        'Approved' => 'badge bg-success',
        'Returned' => 'badge bg-danger'
    ];
    return $badges[$status] ?? 'badge bg-secondary';
}

/**
 * Get lock badge
 */
function getLockBadge($is_locked)
{
    return $is_locked ? '<span class="badge bg-warning"><i class="fas fa-lock"></i> LOCKED</span>' : '';
}

/**
 * Lock all documents for a customer when CAP is approved
 */
function lockAllDocuments($customer_id, $cap_id)
{
    global $conn;
    
    // Lock General and Security documents (customer_documents table)
    $stmt1 = $conn->prepare("UPDATE customer_documents SET is_locked = 1 WHERE customer_id = ?");
    $stmt1->bind_param('i', $customer_id);
    $stmt1->execute();
    
    // Lock Legal documents for this CAP
    $stmt2 = $conn->prepare("UPDATE cap_documents SET is_locked = 1 WHERE customer_id = ? AND cap_id = ?");
    $stmt2->bind_param('is', $customer_id, $cap_id);
    $stmt2->execute();
    
    return true;
}

/**
 * Get all CAPs for a customer grouped by CAP ID
 */
function getCustomerCAPs($customer_id)
{
    global $conn;
    
    $query = "SELECT DISTINCT cap_id, 
              MAX(status) as status,
              MIN(created_at) as created_at 
              FROM cap_documents 
              WHERE customer_id = ? 
              GROUP BY cap_id
              ORDER BY cap_id ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $caps = [];
    while ($row = $result->fetch_assoc()) {
        $caps[] = $row;
    }
    
    return $caps;
}

/**
 * Get documents for a specific CAP
 */
function getCapDocuments($customer_id, $cap_id)
{
    global $conn;
    
    $query = "SELECT cd.*, u.full_name as uploaded_by_name 
              FROM cap_documents cd
              LEFT JOIN edms_users u ON cd.uploaded_by = u.id
              WHERE cd.customer_id = ? AND cd.cap_id = ?
              ORDER BY cd.uploaded_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('is', $customer_id, $cap_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $documents = [];
    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }
    
    return $documents;
}

/**
 * Check if previous CAP is approved (to allow adding new CAP)
 */
function canAddNewCAP($customer_id)
{
    global $conn;
    
    // Get the latest CAP status
    $query = "SELECT status FROM cap_documents 
              WHERE customer_id = ? 
              ORDER BY cap_id DESC, id DESC
              LIMIT 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        return true; // No CAPs yet, can add first one
    }
    
    $row = $result->fetch_assoc();
    return $row['status'] == 'Approved'; // Can only add if last CAP is approved
}
?>