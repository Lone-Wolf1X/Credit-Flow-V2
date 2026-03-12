<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Generate CAP ID based on loan segment and type
 */
function generateCapId($loan_segment, $loan_type)
{
    global $conn;

    // Get prefix and increment counter
    $stmt = $conn->prepare("SELECT id, prefix, counter FROM cap_id_config WHERE loan_segment = ? AND loan_type = ?");
    $stmt->bind_param("ss", $loan_segment, $loan_type);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    $config = $result->fetch_assoc();
    $new_counter = $config['counter'] + 1;

    // Update counter
    $update_stmt = $conn->prepare("UPDATE cap_id_config SET counter = ? WHERE id = ?");
    $update_stmt->bind_param("ii", $new_counter, $config['id']);
    $update_stmt->execute();

    // Generate CAP ID with zero-padded counter
    return $config['prefix'] . '-' . str_pad($new_counter, 3, '0', STR_PAD_LEFT);
}

/**
 * Get reviewer and approver based on escalation matrix
 */
function getAssignedUsers($loan_segment, $loan_type, $initiator_designation)
{
    global $conn;

    // Get escalation matrix entry
    $stmt = $conn->prepare("SELECT reviewer_designation, approver_designation FROM escalation_matrix WHERE loan_segment = ? AND loan_type = ?");
    $stmt->bind_param("ss", $loan_segment, $loan_type);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ['reviewer_id' => null, 'approver_id' => null];
    }

    $matrix = $result->fetch_assoc();

    // Find reviewer
    $reviewer_stmt = $conn->prepare("SELECT id FROM users WHERE designation = ? AND role = 'Reviewer' AND is_active = 1 LIMIT 1");
    $reviewer_stmt->bind_param("s", $matrix['reviewer_designation']);
    $reviewer_stmt->execute();
    $reviewer_result = $reviewer_stmt->get_result();
    $reviewer_id = $reviewer_result->num_rows > 0 ? $reviewer_result->fetch_assoc()['id'] : null;

    // Find approver
    $approver_stmt = $conn->prepare("SELECT id FROM users WHERE designation = ? AND role = 'Approver' AND is_active = 1 LIMIT 1");
    $approver_stmt->bind_param("s", $matrix['approver_designation']);
    $approver_stmt->execute();
    $approver_result = $approver_stmt->get_result();
    $approver_id = $approver_result->num_rows > 0 ? $approver_result->fetch_assoc()['id'] : null;

    return [
        'reviewer_id' => $reviewer_id,
        'approver_id' => $approver_id
    ];
}

/**
 * Handle file upload
 */
function uploadFile($file, $application_id, $user_id, $applicant_name)
{
    global $conn;

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds limit (5MB)'];
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    // Sanitize applicant name for folder creation (alphanumeric and spaces replaced by underscores)
    $safe_name = preg_replace('/[^a-zA-Z0-9]/', '_', trim($applicant_name));
    $user_upload_dir = UPLOAD_DIR . $safe_name . '/';

    // Create user directory if not exists
    if (!is_dir($user_upload_dir)) {
        mkdir($user_upload_dir, 0755, true);
    }

    // Use original filename, keeping it safe
    $safe_filename = preg_replace('/[^a-zA-Z0-9.-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $extension;

    // Handle duplicate filenames by appending timestamp key if needed
    if (file_exists($user_upload_dir . $safe_filename)) {
        $safe_filename = pathinfo($safe_filename, PATHINFO_FILENAME) . '_' . time() . '.' . $extension;
    }

    $file_path = $user_upload_dir . $safe_filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        return ['success' => false, 'message' => 'Failed to save file'];
    }

    // Save to database (Store relative path)
    // Note: UPLOAD_DIR ends with /, so we construct the relative path carefully.
    // Assuming UPLOAD_DIR is defined as __DIR__ . '/../uploads/' in config
    // The relative path for DB should be uploads/CustomerName/Filename
    $db_file_path = 'uploads/' . $safe_name . '/' . $safe_filename;

    $stmt = $conn->prepare("INSERT INTO application_files (application_id, original_filename, stored_filename, file_path, file_type, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssii", $application_id, $file['name'], $safe_filename, $db_file_path, $extension, $file['size'], $user_id);

    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'File uploaded successfully'];
    } else {
        unlink($file_path); // Delete file if database insert fails
        return ['success' => false, 'message' => 'Database error'];
    }
}

/**
 * Add audit log entry
 */
function addAuditLog($application_id, $user_id, $action, $comment = null)
{
    global $conn;

    $stmt = $conn->prepare("INSERT INTO application_comments (application_id, user_id, action, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $application_id, $user_id, $action, $comment);
    return $stmt->execute();
}

/**
 * Get application by ID
 */
function getApplication($id)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT la.*, 
               i.full_name as initiator_name, i.staff_id as initiator_staff_id,
               r.full_name as reviewer_name, r.staff_id as reviewer_staff_id,
               a.full_name as approver_name, a.staff_id as approver_staff_id
        FROM loan_applications la
        LEFT JOIN users i ON la.initiator_id = i.id
        LEFT JOIN users r ON la.reviewer_id = r.id
        LEFT JOIN users a ON la.approver_id = a.id
        WHERE la.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Get application files
 */
function getApplicationFiles($application_id)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT af.*, u.full_name as uploaded_by_name
        FROM application_files af
        LEFT JOIN users u ON af.uploaded_by = u.id
        WHERE af.application_id = ?
        ORDER BY af.uploaded_at DESC
    ");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get application comments/audit trail
 */
function getApplicationComments($application_id)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT ac.*, u.full_name, u.staff_id, u.designation
        FROM application_comments ac
        LEFT JOIN users u ON ac.user_id = u.id
        WHERE ac.application_id = ?
        ORDER BY ac.created_at ASC
    ");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Format currency
 */
function formatCurrency($amount)
{
    return 'NPR ' . number_format($amount, 2);
}

/**
 * Get status badge class
 */
function getStatusBadge($status)
{
    $badges = [
        'Initiated' => 'badge bg-info',
        'Under Review' => 'badge bg-warning',
        'Returned' => 'badge bg-danger',
        'Approved' => 'badge bg-success',
        'Rejected' => 'badge bg-dark'
    ];
    return $badges[$status] ?? 'badge bg-secondary';
}

/**
 * Sanitize input
 */
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Get loan segments
 */
function getLoanSegments()
{
    return ['Retail', 'SME/MSME', 'Micro', 'Agriculture'];
}

/**
 * Get loan types by segment
 */
function getLoanTypes($segment)
{
    $types = [
        'Retail' => ['Personal Term Loan', 'Personal OD Loan', 'Professional Loan', 'Home Loan', 'LAP', 'Vehicle Loan', 'Education Loan'],
        'SME/MSME' => ['Business Term Loan', 'Working Capital', 'Mudra Loan', 'CGTMSE'],
        'Micro' => ['Group Loan', 'Individual Micro Loan'],
        'Agriculture' => ['Crop Loan/KCC', 'Tractor Loan']
    ];
    return $types[$segment] ?? [];
}
?>