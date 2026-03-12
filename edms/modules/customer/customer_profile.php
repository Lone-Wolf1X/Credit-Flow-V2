<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_once '../../includes/functions.php';

function getWebUrl($file_path) {
    if (empty($file_path)) return '#';
    
    // Remove absolute base path
    $relative = str_replace([UPLOAD_DIR, str_replace('/', '\\', UPLOAD_DIR)], '', $file_path);
    
    // Normalize slashes
    $relative = str_replace('\\', '/', $relative);
    
    // Return relative path to uploads/
    return '../../../uploads/' . ltrim($relative, '/');
}

requireLogin();

$id = intval($_GET['id'] ?? 0);
$customer = getCustomer($id);

if (!$customer) {
    header('Location: customer_list.php');
    exit;
}

$success = '';
$error = '';

// Handle success message from URL (after redirect)
if (isset($_GET['success'])) {
    $success = sanitize($_GET['success']);
}

// Role Logic
$edms_role = $_SESSION['role'] ?? 'Maker'; // Default to Maker if missing (shouldn't happen with new login)
$is_maker = ($edms_role === 'Maker' || $edms_role === 'Admin');
$is_checker = ($edms_role === 'Checker' || $edms_role === 'Admin');

// Handle Global Profile Submission (Submit All) - MOVED TO TOP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_profile_global'])) {
    
    // 1. Update ALL Draft CAPs to 'Pending Legal'
    $stmt = $conn->prepare("UPDATE cap_documents SET status = 'Pending Legal', submitted_at = NOW() WHERE customer_id = ? AND status = 'Draft'");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $updated_caps = $stmt->affected_rows;
        
        // Update Customer Status
        $cust_stmt = $conn->prepare("UPDATE customers SET status = 'Pending Legal' WHERE id = ?");
        $cust_stmt->bind_param("i", $id);
        $cust_stmt->execute();
        
        // Always proceed if we updated customer status
        // 2. Publish Documents (Set is_draft = 0 for ALL Draft docs for this customer)
        $pub_stmt = $conn->prepare("UPDATE customer_documents SET is_draft = 0 WHERE customer_id = ? AND is_draft = 1");
        $pub_stmt->bind_param("i", $id);
        $pub_stmt->execute();

        // 3. Log comments
        $comment_val = isset($_POST['comment']) ? sanitize($_POST['comment']) : '';
        
        // Get IDs of submitted CAPs to log comments
        $caps_res = $conn->query("SELECT id FROM cap_documents WHERE customer_id = $id AND status = 'Pending Legal'");
        if ($caps_res) {
            while ($cap_row = $caps_res->fetch_assoc()) {
                    $comment_stmt = $conn->prepare("INSERT INTO legal_comments (cap_document_id, user_id, action, comment) VALUES (?, ?, 'Submitted', ?)");
                    $comment_stmt->bind_param("iis", $cap_row['id'], $_SESSION['user_id'], $comment_val);
                    $comment_stmt->execute();
            }
        }

        addAuditLog($_SESSION['user_id'], 'Profile Submitted', 'customer', $id, "Submitted Profile for Legal Vetting ($updated_caps CAPs)");
        
        header("Location: customer_profile.php?id=$id&success=Profile Submitted Successfully for Vetting");
        exit;
    } else {
        $error = "Failed to update profile status.";
    }
}

// Handle CAP-Level Checker Actions (Approve/Return Individual CAP)
if ($is_checker && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checker_cap_action'])) {
    $action = $_POST['checker_cap_action'];
    $cap_id = sanitize($_POST['cap_id']);
    $comment = sanitize($_POST['comment']);

    if (empty($comment)) {
        $error = "Comment is required.";
    } else {
        $new_status = ($action === 'approve') ? 'Approved' : 'Returned';
        $log_action = ($action === 'approve') ? 'Approved CAP' : 'Returned CAP';

        // 1. Update CAP Status
        $stmt = $conn->prepare("UPDATE cap_documents SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE customer_id = ? AND cap_id = ?");
        $stmt->bind_param("siis", $new_status, $_SESSION['user_id'], $id, $cap_id);
        
        if ($stmt->execute()) {
            
            // 2. If Approved: Lock ALL documents (General, Security, Legal)
            if ($action === 'approve') {
                lockAllDocuments($id, $cap_id);
            }

            // 3. Log Comment for this CAP
            $cap_doc_id = $conn->query("SELECT id FROM cap_documents WHERE customer_id = $id AND cap_id = '$cap_id' LIMIT 1")->fetch_assoc()['id'];
            $c_stmt = $conn->prepare("INSERT INTO legal_comments (cap_document_id, user_id, action, comment) VALUES (?, ?, ?, ?)");
            $c_stmt->bind_param("iiss", $cap_doc_id, $_SESSION['user_id'], $log_action, $comment);
            $c_stmt->execute();

            addAuditLog($_SESSION['user_id'], $log_action, 'cap', $cap_doc_id, "CAP: $cap_id, Status: $new_status");

            // Sync Parent Customer Status
            $res = $conn->query("SELECT status FROM cap_documents WHERE customer_id = $id");
            $stats = [];
            while($r = $res->fetch_assoc()) $stats[] = $r['status'];

            if(in_array('Returned', $stats)) $p_status = 'Returned';
            elseif(in_array('Pending Legal', $stats)) $p_status = 'Pending Legal';
            elseif(in_array('Draft', $stats) || in_array('', $stats)) $p_status = 'Draft';
            else $p_status = 'Approved';

            $conn->query("UPDATE customers SET status = '$p_status' WHERE id = $id");

            $success = "CAP $cap_id $new_status successfully!";
            header("Location: customer_profile.php?id=$id&success=" . urlencode($success));
            exit;
        } else {
            $error = "Database update failed.";
        }
    }
}

// Handle Edit CAP Name
if ($is_maker && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_cap_name'])) {
    $old_cap_id = sanitize($_POST['old_cap_id']);
    $new_cap_id = sanitize($_POST['new_cap_id']);
    
    if (empty($new_cap_id)) {
        $error = "CAP ID cannot be empty.";
    } else {
        // Check if CAP is not yet approved
        $check = $conn->query("SELECT status FROM cap_documents WHERE customer_id = $id AND cap_id = '$old_cap_id' LIMIT 1")->fetch_assoc();
        
        if ($check && $check['status'] != 'Approved') {
            // Update CAP ID in cap_documents table
            $stmt1 = $conn->prepare("UPDATE cap_documents SET cap_id = ? WHERE customer_id = ? AND cap_id = ?");
            $stmt1->bind_param("sis", $new_cap_id, $id, $old_cap_id);
            
            // Update CAP ID in customer_documents table
            $stmt2 = $conn->prepare("UPDATE customer_documents SET cap_id = ? WHERE customer_id = ? AND cap_id = ?");
            $stmt2->bind_param("sis", $new_cap_id, $id, $old_cap_id);
            
            if ($stmt1->execute() && $stmt2->execute()) {
                addAuditLog($_SESSION['user_id'], 'Edit CAP Name', 'cap', $id, "Renamed $old_cap_id to $new_cap_id");
                $success = "CAP renamed successfully from $old_cap_id to $new_cap_id";
                header("Location: customer_profile.php?id=$id&success=" . urlencode($success));
                exit;
            } else {
                $error = "Failed to rename CAP.";
            }
        } else {
            $error = "Cannot edit approved CAP.";
        }
    }
}

// Handle Delete CAP
if ($is_maker && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_cap'])) {
    $cap_id = sanitize($_POST['cap_id']);
    
    // Check if CAP is not yet approved
    $check = $conn->query("SELECT status FROM cap_documents WHERE customer_id = $id AND cap_id = '$cap_id' LIMIT 1")->fetch_assoc();
    
    if ($check && $check['status'] != 'Approved') {
        // Get all documents for this CAP to delete files
        $docs = $conn->query("SELECT file_path FROM customer_documents WHERE customer_id = $id AND cap_id = '$cap_id'");
        while ($doc = $docs->fetch_assoc()) {
            if (file_exists('../../' . $doc['file_path'])) {
                unlink('../../' . $doc['file_path']);
            }
        }
        
        // Delete from database
        $conn->query("DELETE FROM customer_documents WHERE customer_id = $id AND cap_id = '$cap_id'");
        $conn->query("DELETE FROM cap_documents WHERE customer_id = $id AND cap_id = '$cap_id'");
        
        addAuditLog($_SESSION['user_id'], 'Delete CAP', 'cap', $id, "Deleted CAP: $cap_id");
        $success = "CAP $cap_id deleted successfully.";
        header("Location: customer_profile.php?id=$id&success=" . urlencode($success));
        exit;
    } else {
        $error = "Cannot delete approved CAP.";
    }
}

// Handle Submit CAP to Legal
// Handle Submit CAP to Legal
if ($is_maker && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_cap_to_legal'])) {
    $cap_id = sanitize($_POST['cap_id']);
    $maker_comment = sanitize($_POST['maker_comment'] ?? '');
    
    // Get CAP Document ID (needed for linking comments)
    $cap_query = $conn->query("SELECT id FROM cap_documents WHERE customer_id = $id AND cap_id = '$cap_id' LIMIT 1");
    $cap_data = $cap_query->fetch_assoc();
    
    if ($cap_data) {
        $cap_pk_id = $cap_data['id'];
        
        // Update CAP status to "Pending Legal"
        $stmt = $conn->prepare("UPDATE cap_documents SET status = 'Pending Legal', submitted_at = NOW(), submitted_by = ? WHERE id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $cap_pk_id);
        
        if ($stmt->execute()) {
            // Save Maker's Comment if provided
            if (!empty($maker_comment)) {
                $comment_stmt = $conn->prepare("INSERT INTO legal_comments (cap_document_id, user_id, action, comment) VALUES (?, ?, 'Submitted', ?)");
                $comment_stmt->bind_param("iis", $cap_pk_id, $_SESSION['user_id'], $maker_comment);
                $comment_stmt->execute();
            }
            
            addAuditLog($_SESSION['user_id'], 'Submit CAP to Legal', 'cap', $cap_pk_id, "Submitted CAP: $cap_id with comment: $maker_comment");
            
            // Sync Parent Customer Status
            $res = $conn->query("SELECT status FROM cap_documents WHERE customer_id = $id");
            $stats = [];
            while($r = $res->fetch_assoc()) $stats[] = $r['status'];

            if(in_array('Returned', $stats)) $p_status = 'Returned';
            elseif(in_array('Pending Legal', $stats)) $p_status = 'Pending Legal';
            elseif(in_array('Draft', $stats) || in_array('', $stats)) $p_status = 'Draft';
            else $p_status = 'Approved';

            $conn->query("UPDATE customers SET status = '$p_status' WHERE id = $id");

            $success = "CAP $cap_id submitted to Legal for vetting.";
            header("Location: customer_profile.php?id=$id&success=" . urlencode($success));
            exit;
        } else {
            $error = "Failed to update CAP status.";
        }
    } else {
        $error = "CAP not found.";
    }
}

file_put_contents(__DIR__ . '/debug_post.txt', "CHECKPOINT 3: After CAP submit handler\n", FILE_APPEND);

// Handle AJAX Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_upload'])) {
    header('Content-Type: application/json');
    
    $category = sanitize($_POST['category']);
    $cap_id = sanitize($_POST['cap_id']);
    $doc_type = sanitize($_POST['doc_type']);
    $remark = sanitize($_POST['remark']);
    
    if (empty($_FILES['file']['name'])) {
        echo json_encode(['success' => false, 'message' => 'No file selected']);
        exit;
    }

    $result = uploadDocumentToFolder($_FILES['file'], $id, $cap_id, $category, $doc_type, $remark, $_SESSION['user_id']);
    
    if ($result['success']) {
        // Fetch the inserted document to return full details (or construct from result)
        // We need file path to show "View" button
        // Re-query or rely on upload function returning path? Helper returns success/msg/doc_id. 
        // We'll trust the helper or fetch if needed. Let's fetch to be safe/clean for the UI.
        
        $doc_id = $result['document_id'];
        $stmt = $conn->prepare("SELECT * FROM customer_documents WHERE id = ?");
        $stmt->bind_param("i", $doc_id);
        $stmt->execute();
        $doc = $stmt->get_result()->fetch_assoc();
        
        // Calculate web path
        $relative_path = str_replace([UPLOAD_DIR, str_replace('/', '\\', UPLOAD_DIR)], '', $doc['file_path']);
        $web_path = '../../../uploads/' . $relative_path;

        echo json_encode([
            'success' => true, 
            'message' => 'Uploaded successfully',
            'doc_id' => $doc['id'],
            'web_path' => $web_path, // Return Web Path
            'original_filename' => $doc['original_filename'],
            'document_type' => $doc['document_type'],
            'remark' => $doc['remark']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    exit;
}

// ... (Rest of PHP code is fine) ...

// JS Updates at the bottom
// Handle Critical Document Upload (Header Card)
// Handle Critical Document Upload (Header Card)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_critical'])) {
    $cap_id_val = sanitize($_POST['cap_id']); // CAP ID (String)
    $uploaded_count = 0;
    
    // Define inputs to check: input_name => document_type
    $uploads = [
        'approval_log' => 'Approval Log',
        'cap_doc' => 'Approved CAP'
    ];
    
    foreach ($uploads as $input_name => $doc_type) {
        if (!empty($_FILES[$input_name]['name'])) {
            // Use 'Critical' category to trigger specific folder logic
            $result = uploadDocumentToFolder($_FILES[$input_name], $id, $cap_id_val, 'Critical', $doc_type, 'Critical Document', $_SESSION['user_id']);
            if ($result['success']) {
                $uploaded_count++;
                addAuditLog($_SESSION['user_id'], 'Critical Upload', 'customer', $id, "Uploaded $doc_type for $cap_id_val");
            } else {
                $error = $result['message']; // Last error overwrites (simple handling)
            }
        }
    }

    if ($uploaded_count > 0) {
        $success = "$uploaded_count critical document(s) uploaded successfully!";
        // Redirect to refresh and show uploaded documents
        header("Location: customer_profile.php?id=$id&success=" . urlencode($success));
        exit;
    } elseif (empty($error)) {
        $error = "Please select at least one file to upload.";
    }
}

file_put_contents(__DIR__ . '/debug_post.txt', "CHECKPOINT 1: After critical upload handler\n", FILE_APPEND);

// Handle Multi-Row Document Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_multi'])) {
    $category = sanitize($_POST['category']);
    $cap_id_val = sanitize($_POST['cap_id']);
    if (empty($cap_id_val)) {
        $cap_id_val = $category; // Stick to 'General' or 'Security' as folder
    }

    $uploaded_count = 0;
    $errors = [];

    // Arrays: document_type[], document[], remark[]
    // Verify arrays exist
    if (isset($_POST['document_type']) && isset($_FILES['document'])) {
        $types = $_POST['document_type'];
        $remarks = $_POST['remark'];
        $files = $_FILES['document'];

        for ($i = 0; $i < count($types); $i++) {
            // Check if type selected and file provided
            if (!empty($types[$i]) && !empty($files['name'][$i])) {
                // Construct single file array for the function
                $file_ary = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];

                $remark = sanitize($remarks[$i] ?? '');

                $result = uploadDocumentToFolder($file_ary, $id, $cap_id_val, $category, $types[$i], $remark, $_SESSION['user_id']);

                if ($result['success']) {
                    $uploaded_count++;
                } else {
                    $errors[] = "Row " . ($i + 1) . ": " . $result['message'];
                }
            }
        }

        if ($uploaded_count > 0) {
            $success = "$uploaded_count document(s) uploaded successfully!";
            addAuditLog($_SESSION['user_id'], 'Multi Upload', 'customer', $id, "Uploaded $uploaded_count legal documents for $cap_id_val");
        }

        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        }
    } else {
        $error = 'No document data received';
    }
}

// Handle standard single document upload (General/Security tabs still use this)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    $category = sanitize($_POST['category']);
    $document_type = sanitize($_POST['document_type']);
    $cap_id_val = 'General'; // Default for non-CAP docs

    if (!empty($_FILES['document']['name'])) {
        $result = uploadDocumentToFolder($_FILES['document'], $id, $cap_id_val, $category, $document_type, '', $_SESSION['user_id']);
        if ($result['success']) {
            $success = $result['message'];
            addAuditLog($_SESSION['user_id'], 'Document Uploaded', 'customer', $id, "$category - $document_type");
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Please select a file';
    }
}

file_put_contents(__DIR__ . '/debug_post.txt', "CHECKPOINT 2: After document upload handler\n", FILE_APPEND);

// Handle CAP ID submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_cap'])) {
    $cap_id = sanitize($_POST['cap_id']);

    // Validate CAP ID
    $validation = validateCapId($cap_id);

    if (!$validation['valid']) {
        $error = $validation['message'];
    } else {
        // Check if CAP ID already exists for this customer
        $check = $conn->prepare("SELECT id FROM cap_documents WHERE customer_id = ? AND cap_id = ?");
        $check->bind_param("is", $id, $cap_id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = 'This CAP ID is already linked to this customer';
        } else {
            $stmt = $conn->prepare("INSERT INTO cap_documents (customer_id, cap_id, submitted_by, status) VALUES (?, ?, ?, 'Draft')");
            $stmt->bind_param("isi", $id, $cap_id, $_SESSION['user_id']);

            if ($stmt->execute()) {
                $cap_doc_id = $conn->insert_id;

                // Add comment
                $comment_stmt = $conn->prepare("INSERT INTO legal_comments (cap_document_id, user_id, action, comment) VALUES (?, ?, 'Submitted', 'CAP ID submitted for legal vetting')");
                $comment_stmt->bind_param("ii", $cap_doc_id, $_SESSION['user_id']);
                $comment_stmt->execute();

                addAuditLog($_SESSION['user_id'], 'CAP Submitted', 'cap_document', $cap_doc_id, "CAP ID: $cap_id");
                $success = 'CAP ID submitted for legal vetting successfully!';
            } else {
                $error = 'Failed to submit CAP ID';
            }
        }
    }
}

    // Handle Add New CAP
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_new_cap'])) {
        $cap_id = sanitize($_POST['cap_id']);

        // Validate CAP ID
        $validation = validateCapId($cap_id);

        if (!$validation['valid']) {
            $error = $validation['message'];
        } else {
            // Check if CAP ID already exists for this customer
            $check = $conn->prepare("SELECT id FROM cap_documents WHERE customer_id = ? AND cap_id = ?");
            $check->bind_param("is", $id, $cap_id);
            $check->execute();

            if ($check->get_result()->num_rows > 0) {
                $error = 'This CAP ID is already linked to this customer';
            } else {
                // Determine folder path (e.g., existing path or create new? For now, we assume sharing main folder or just logical separation)
                // Actually, createCustomerFolders creates `FullName_ClientID_CAPID`.
                // New CAPs might ideally share the customer folder or have their own subfolder.
                // For simplicity, we'll use the customer's base folder logic or just store the CAP ID reference.
                
                $stmt = $conn->prepare("INSERT INTO cap_documents (customer_id, cap_id, submitted_by, status) VALUES (?, ?, ?, 'Draft')");
                $stmt->bind_param("isi", $id, $cap_id, $_SESSION['user_id']);

                if ($stmt->execute()) {
                    $cap_doc_id = $conn->insert_id;

                    // Add comment
                    $comment_stmt = $conn->prepare("INSERT INTO legal_comments (cap_document_id, user_id, action, comment) VALUES (?, ?, 'Created', 'Additional CAP ID linked')");
                    $comment_stmt->bind_param("ii", $cap_doc_id, $_SESSION['user_id']);
                    $comment_stmt->execute();

                    addAuditLog($_SESSION['user_id'], 'CAP Added', 'cap_document', $cap_doc_id, "Linked new CAP ID: $cap_id");
                    $success = 'New CAP ID added successfully!';
                } else {
                    $error = 'Failed to link new CAP ID';
                }
            }
        }
    // Old handler removed - moved to top


    // Handle Global Checker Action (Approve/Return Profile)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checker_profile_action'])) {
        $action_val = $_POST['checker_profile_action'];
        $comment_val = sanitize($_POST['comment']);

        $action_text = '';
        $msg = '';

        if ($action_val === 'approve') {
             // 1. Update Customer Status
             $conn->query("UPDATE customers SET status = 'Approved' WHERE id = $id");
             
             // 2. Update All CAPs to Approved
             $conn->query("UPDATE cap_documents SET status = 'Approved', reviewed_by = {$_SESSION['user_id']}, reviewed_at = NOW() WHERE customer_id = $id");
             
             // 3. Lock All Documents
             lockCustomerDocuments($id);
             
             $action_text = 'Approved';
             $msg = "Profile Approved and Documents Locked.";
        } elseif ($action_val === 'return') {
             // 1. Update Customer Status
             $conn->query("UPDATE customers SET status = 'Returned' WHERE id = $id");
             
             // 2. Update All CAPs to Returned
             $conn->query("UPDATE cap_documents SET status = 'Returned', reviewed_by = {$_SESSION['user_id']}, reviewed_at = NOW() WHERE customer_id = $id");
             
             $action_text = 'Returned';
             $msg = "Profile Returned to Maker.";
        }

        // Log Comment
        $caps = $conn->query("SELECT id FROM cap_documents WHERE customer_id = $id");
        while ($c = $caps->fetch_assoc()) {
            $comment_stmt = $conn->prepare("INSERT INTO legal_comments (cap_document_id, user_id, action, comment) VALUES (?, ?, ?, ?)");
            $comment_stmt->bind_param("iiss", $c['id'], $_SESSION['user_id'], $action_text, $comment_val);
            $comment_stmt->execute();
        }

        addAuditLog($_SESSION['user_id'], "Profile $action_text", 'customer', $id, $comment_val);
        header("Location: customer_profile.php?id=$id&success=$msg");
        exit;
    }
}


// Handle Document Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_document'])) {
    $doc_id = intval($_POST['doc_id']);
    
    // Safety Check: Ensure document belongs to this customer
    $stmt = $conn->prepare("SELECT file_path, is_locked, cap_id, document_type FROM customer_documents WHERE id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $doc_id, $id);
    $stmt->execute();
    $doc_check = $stmt->get_result()->fetch_assoc();
    
    if ($doc_check) {
        if ($doc_check['is_locked']) {
            $error = "Cannot delete a verified/locked document.";
        } else {
            // Delete file from server
            if (file_exists('../../' . $doc_check['file_path'])) {
                 unlink('../../' . $doc_check['file_path']);
            } elseif (file_exists($doc_check['file_path'])) {
                 unlink($doc_check['file_path']);
            }

            // Delete from DB
            $del_stmt = $conn->prepare("DELETE FROM customer_documents WHERE id = ?");
            $del_stmt->bind_param("i", $doc_id);
            if ($del_stmt->execute()) {
                // Log action
                $remark = "Deleted " . $doc_check['document_type'] . " for " . $doc_check['cap_id'];
                addAuditLog($_SESSION['user_id'], 'Delete Document', 'customer', $id, $remark);
                $success = "Document deleted successfully.";
            } else {
                $error = "Database error deleting record.";
            }
        }
    } else {
        $error = "Document not found or access denied.";
    }
}

// Get documents by category
$general_docs = getCustomerDocuments($id, 'General', 'General');
$security_docs = getCustomerDocuments($id, 'Security', 'Security');

// Get CAP documents
$cap_documents = getCustomerCapDocuments($id);

// Check if new CAP can be added (only if previous CAP is approved)
$can_add_cap = canAddNewCAP($id);

// Define Read-Only Status
// Maker can ALWAYS upload documents (Post-Approval workflow).
// Granular locking prevents modifying vetted (Approved) documents.
$can_edit = $is_maker; 
// Old logic: $is_maker && in_array($customer['status'], ['Draft', 'Returned', 'Pending']);

include '../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Global Profile Submit Button (Visible to Maker when Draft or Returned) -->
<?php if ($is_maker && in_array($customer['status'], ['Draft', 'Returned', 'Analysis', 'Observation', 'Limit Renew', 'Enhancement'])): ?>
    <div class="card mb-4 mt-4 border-success shadow-sm">
        <div class="card-body d-flex align-items-center justify-content-between bg-white rounded">
            <div>
                <h5 class="mb-1 text-success fw-bold"><i class="fas fa-check-circle me-2"></i>Submit Profile</h5>
                <p class="text-muted mb-0 small">
                    <?php if ($customer['status'] == 'Returned'): ?>
                        <span class="text-danger fw-bold">Profile Returned.</span> Please address remarks and re-submit.
                    <?php else: ?>
                        Ready to send for Legal Vetting?
                    <?php endif; ?>
                </p>
            </div>
            <button class="btn btn-success btn-lg px-5 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#submitProfileModal">
                <?php echo ($customer['status'] == 'Returned') ? 'Re-Submit Profile' : 'Submit Profile'; ?> <i class="fas fa-paper-plane ms-2"></i>
            </button>
        </div>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="form-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-user"></i> Customer Information</h4>
                <!-- Profile Submit Button Removed - Using CAP-level approval -->
            </div>
            <div class="row">
                <div class="col-md-6">
                    <strong>Name:</strong> <?php echo htmlspecialchars($customer['customer_name']); ?><br>
                    <strong>Contact:</strong> <?php echo htmlspecialchars($customer['contact_number']); ?>
                </div>
                <div class="col-md-6 text-end">
                    <strong>Created By:</strong> <?php echo htmlspecialchars($customer['created_by_name']); ?><br>
                    <strong>Created:</strong> <?php echo date('M d, Y', strtotime($customer['created_at'])); ?>
                </div>
            </div>
        </div>

<!-- Customer Status Badge Removed - Using CAP-level status -->

<!-- Recent Legal Remarks Card Removed (Moved to Comments Tab) -->

<!-- Tab Navigation -->
<ul class="nav nav-tabs mb-4" id="documentTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
            <i class="fas fa-file-alt text-primary"></i> General Documents
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
            <i class="fas fa-shield-alt text-success"></i> Security Documents
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="legal-tab" data-bs-toggle="tab" data-bs-target="#legal" type="button" role="tab" aria-controls="legal" aria-selected="false">
            <i class="fas fa-gavel text-warning"></i> Legal Documents
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button" role="tab" aria-controls="comments" aria-selected="false">
            <i class="fas fa-comments text-info"></i> Comments History
        </button>
    </li>
</ul>
        <div class="tab-content" id="documentTabsContent">
            <!-- General Documents -->
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <div class="form-card mb-4">
                    <h5 class="mb-3">General Documents</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase w-100">
                                <tr>
                                    <th style="width: 30%;">Document Type</th>
                                    <th style="width: 35%;">File / Preview</th>
                                    <th style="width: 25%;">Remark</th>
                                    <th style="width: 10%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($general_docs as $doc): ?>
                                    <tr class="existing-doc-row bg-light bg-opacity-10">
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($doc['document_type']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($doc['original_filename']); ?></small>
                                        </td>
                                        <td>
<a href="<?php echo getWebUrl($doc['file_path']); ?>" 
                                               onclick="viewDocument(event, this.href)"
                                               class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                <i class="fas fa-eye me-1"></i> View Document
                                            </a>
                                        </td>
                                        <td>
                                            <span class="text-muted small"><?php echo htmlspecialchars($doc['remark']); ?></span>
                                        </td>
                                        <td>
                                            <?php if (!$doc['is_locked']): ?>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this document?');" style="display:inline;">
                                                    <input type="hidden" name="delete_document" value="1">
                                                    <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="fas fa-lock"></i></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <?php if ($can_edit): ?>
                                    <tr class="ajax-upload-row" data-cap-id="General" data-category="General">
                                        <td>
                                            <select class="form-select form-select-sm doc-type">
                                                <option value="">Select Type...</option>
                                                <option value="KYC">KYC</option>
                                                <option value="Citizenship">Citizenship</option>
                                                <option value="PAN Copy">PAN Copy</option>
                                                <option value="Photo">Photo</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="file" class="form-control form-control-sm doc-file" accept=".pdf,.jpg,.png,.doc,.docx">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm doc-remark" placeholder="Remark">
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-success text-white" onclick="uploadRow(this)">
                                                    <i class="fas fa-save"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-info text-white" onclick="addEmptyRow(this)">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Security Documents -->
            <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                <div class="form-card mb-4">
                    <h5 class="mb-3">Security Documents</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase w-100">
                                <tr>
                                    <th style="width: 30%;">Document Type</th>
                                    <th style="width: 35%;">File / Preview</th>
                                    <th style="width: 25%;">Remark</th>
                                    <th style="width: 10%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($security_docs as $doc): ?>
                                    <tr class="existing-doc-row bg-light bg-opacity-10">
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($doc['document_type']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($doc['original_filename']); ?></small>
                                        </td>
                                        <td>
<a href="<?php echo getWebUrl($doc['file_path']); ?>" 
                                               onclick="viewDocument(event, this.href)"
                                               class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                <i class="fas fa-eye me-1"></i> View Document
                                            </a>
                                        </td>
                                        <td>
                                            <span class="text-muted small"><?php echo htmlspecialchars($doc['remark']); ?></span>
                                        </td>
                                        <td>
                                            <?php if (!$doc['is_locked']): ?>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this document?');" style="display:inline;">
                                                    <input type="hidden" name="delete_document" value="1">
                                                    <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="fas fa-lock"></i></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if ($can_edit): ?>
                                    <tr class="ajax-upload-row" data-cap-id="Security" data-category="Security">
                                        <td>
                                            <input type="text" class="form-control form-control-sm doc-type" placeholder="e.g. Property Papers">
                                        </td>
                                        <td>
                                            <input type="file" class="form-control form-control-sm doc-file" accept=".pdf,.jpg,.png,.doc,.docx">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm doc-remark" placeholder="Remark">
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-success text-white" onclick="uploadRow(this)">
                                                    <i class="fas fa-save"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-info text-white" onclick="addEmptyRow(this)">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Legal Documents -->
            <div class="tab-pane fade" id="legal" role="tabpanel" aria-labelledby="legal-tab">
                <?php if (empty($cap_documents)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Please link a CAP ID (in the sidebar) to upload legal documents.
                    </div>
                <?php else: ?>
                    <!-- CAP Sub-tabs -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <ul class="nav nav-pills gap-2" id="legalCapTabs" role="tablist">
                            <?php foreach ($cap_documents as $index => $cap):
                                $is_approved = ($cap['status'] === 'Approved');
                                $tab_class = $is_approved ? 'bg-success text-white' : '';
                                ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?> <?php echo $tab_class; ?>"
                                        id="cap-tab-<?php echo $cap['id']; ?>" data-bs-toggle="pill"
                                        data-bs-target="#cap-content-<?php echo $cap['id']; ?>" type="button" role="tab">
                                        <?php if ($is_approved): ?><i class="fas fa-lock me-1"></i><?php endif; ?>
                                        <?php echo htmlspecialchars($cap['cap_id']); ?>
                                        
                                        <!-- Edit/Delete for non-approved CAPs -->
                                        <?php if ($is_maker && !$is_approved): ?>
                                            <button type="button" class="btn btn-sm btn-link text-white p-0 ms-2" 
                                                data-bs-toggle="modal" data-bs-target="#editCapModal<?php echo $cap['id']; ?>"
                                                onclick="event.stopPropagation();" title="Edit CAP Name">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-link text-white p-0" 
                                                onclick="event.stopPropagation(); if(confirm('Delete CAP <?php echo $cap['cap_id']; ?>? All documents will be removed.')) document.getElementById('deleteCapForm<?php echo $cap['id']; ?>').submit();"
                                                title="Delete CAP">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        <?php endif; ?>
                                    </button>
                                    
                                    <!-- Hidden delete form -->
                                    <?php if ($is_maker && !$is_approved): ?>
                                        <form id="deleteCapForm<?php echo $cap['id']; ?>" method="POST" style="display:none;">
                                            <input type="hidden" name="delete_cap" value="1">
                                            <input type="hidden" name="cap_id" value="<?php echo htmlspecialchars($cap['cap_id']); ?>">
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($is_maker && $can_add_cap): ?>
                            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addCapModal">
                                <i class="fas fa-plus-circle me-1"></i> Add CAP
                            </button>
                        <?php elseif ($is_maker): ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled data-bs-toggle="tooltip" title="Previous CAP must be Approved first">
                                <i class="fas fa-lock me-1"></i> Add CAP
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="tab-content" id="legalCapTabsContent">
                        <?php foreach ($cap_documents as $index => $cap):
                            // Fetch legal AND critical docs for this specific CAP
                            $stmt = $conn->prepare("SELECT cd.*, u.full_name as uploaded_by_name FROM customer_documents cd LEFT JOIN edms_users u ON cd.uploaded_by = u.id WHERE cd.customer_id = ? AND cd.cap_id = ? AND cd.document_category IN ('Legal', 'Critical') ORDER BY cd.uploaded_at DESC");
                            $stmt->bind_param("is", $id, $cap['cap_id']);
                            $stmt->execute();
                            $cap_legal_docs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            ?>
                            <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>"
                                id="cap-content-<?php echo $cap['id']; ?>" role="tabpanel">

                                <!-- 1. Header Card: Mandatory Docs -->
                                <div class="card mb-4 border-primary">
                                    <div
                                        class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-star"></i> Critical Documents</h6>

                                            <span class="<?php echo getStatusBadge($cap['status']); ?>">
                                                <?php echo !empty($cap['status']) ? $cap['status'] : 'Draft'; ?>
                                                <?php if($cap['status'] == 'Approved'): ?> <i class="fas fa-lock ms-1"></i> <?php endif; ?>
                                            </span>
                                    </div>
                                    <div class="card-body">
                                        <?php
                                        // Filter for Critical Docs
                                        $approval_log = null;
                                        $approved_cap = null;
                                        foreach ($cap_legal_docs as $d) {
                                            if ($d['document_type'] === 'Approval Log') $approval_log = $d;
                                            if ($d['document_type'] === 'Approved CAP') $approved_cap = $d;
                                        }
                                        $has_approval_log = ($approval_log !== null);
                                        $has_approved_cap = ($approved_cap !== null);
                                        ?>
                                        
                                        <form method="POST" action="" enctype="multipart/form-data">
                                            <input type="hidden" name="upload_critical" value="1">
                                            <input type="hidden" name="cap_id" value="<?php echo htmlspecialchars($cap['cap_id']); ?>">

                                            <div class="mb-3">
                                                <h5 class="text-primary fw-bold"><?php echo htmlspecialchars($cap['cap_id']); ?></h5>
                                            </div>

                                            <div class="row g-3">
                                                <!-- Approval Log -->
                                                <div class="col-md-6">
                                                    <div class="card bg-light border-0 h-100">
                                                        <div class="card-body">
                                                            <label class="form-label fw-bold mb-2">Approval Log</label>
                                                            
                                                            <?php if ($approval_log): ?>
                                                                <!-- File already uploaded - show view/delete only -->
                                                                <div class="p-3 bg-white rounded border shadow-sm d-flex justify-content-between align-items-center">
                                                                    <div class="d-flex align-items-center text-truncate me-2">
                                                                        <i class="fas fa-file-pdf text-danger me-2 fa-lg"></i>
                                                                        <div>
                                                                            <div class="small fw-bold text-dark text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($approval_log['original_filename']); ?></div>
                                                                            <div class="text-muted" style="font-size: 0.7rem;">Uploaded: <?php echo date('M d, Y', strtotime($approval_log['uploaded_at'])); ?></div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="flex-shrink-0">
                                                                        <a href="../../../uploads/<?php echo str_replace([UPLOAD_DIR, str_replace('/', '\\', UPLOAD_DIR)], '', $approval_log['file_path']); ?>" 
                                                                           onclick="viewDocument(event, this.href)" 
                                                                           class="btn btn-sm btn-outline-primary me-1" title="View">
                                                                            <i class="fas fa-eye"></i>
                                                                        </a>
                                                                        <?php if (!$approval_log['is_locked'] && $is_maker): ?>
                                                                            <button type="button" onclick="if(confirm('Delete to upload a new file?')) document.getElementById('del_cri_<?php echo $approval_log['id']; ?>').submit();" class="btn btn-sm btn-outline-danger" title="Delete">
                                                                                <i class="fas fa-trash-alt"></i>
                                                                            </button>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-secondary"><i class="fas fa-lock"></i> Locked</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            <?php else: ?>
                                                                <!-- No file uploaded - show upload input -->
                                                                <?php if ($is_maker): ?>
                                                                    <input type="file" class="form-control form-control-sm" name="approval_log" accept=".pdf,.jpg,.png,.doc,.docx">
                                                                    <small class="text-muted d-block mt-1"><i class="fas fa-info-circle"></i> Upload PDF, Image, or Document</small>
                                                                <?php else: ?>
                                                                    <div class="text-muted small"><i class="fas fa-minus-circle"></i> No file uploaded</div>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Approved CAP -->
                                                <div class="col-md-6">
                                                     <div class="card bg-light border-0 h-100">
                                                        <div class="card-body">
                                                            <label class="form-label fw-bold mb-2">Approved CAP</label>
                                                            
                                                            <?php if ($approved_cap): ?>
                                                                <!-- File already uploaded - show view/delete only -->
                                                                <div class="p-3 bg-white rounded border shadow-sm d-flex justify-content-between align-items-center">
                                                                    <div class="d-flex align-items-center text-truncate me-2">
                                                                        <i class="fas fa-file-pdf text-danger me-2 fa-lg"></i>
                                                                        <div>
                                                                            <div class="small fw-bold text-dark text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($approved_cap['original_filename']); ?></div>
                                                                            <div class="text-muted" style="font-size: 0.7rem;">Uploaded: <?php echo date('M d, Y', strtotime($approved_cap['uploaded_at'])); ?></div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="flex-shrink-0">
                                                                        <a href="../../../uploads/<?php echo str_replace([UPLOAD_DIR, str_replace('/', '\\', UPLOAD_DIR)], '', $approved_cap['file_path']); ?>" 
                                                                           onclick="viewDocument(event, this.href)" 
                                                                           class="btn btn-sm btn-outline-primary me-1" title="View">
                                                                            <i class="fas fa-eye"></i>
                                                                        </a>
                                                                        <?php if (!$approved_cap['is_locked'] && $is_maker): ?>
                                                                            <button type="button" onclick="if(confirm('Delete to upload a new file?')) document.getElementById('del_cri_<?php echo $approved_cap['id']; ?>').submit();" class="btn btn-sm btn-outline-danger" title="Delete">
                                                                                <i class="fas fa-trash-alt"></i>
                                                                            </button>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-secondary"><i class="fas fa-lock"></i> Locked</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            <?php else: ?>
                                                                <!-- No file uploaded - show upload input -->
                                                                <?php if ($is_maker): ?>
                                                                    <input type="file" class="form-control form-control-sm" name="cap_doc" accept=".pdf,.jpg,.png,.doc,.docx">
                                                                    <small class="text-muted d-block mt-1"><i class="fas fa-info-circle"></i> Upload PDF, Image, or Document</small>
                                                                <?php else: ?>
                                                                    <div class="text-muted small"><i class="fas fa-minus-circle"></i> No file uploaded</div>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <?php if ($is_maker && (!$approval_log || !$approved_cap)): ?>
                                                <div class="mt-3 text-end">
                                                    <button type="submit" class="btn btn-primary px-4">
                                                        <i class="fas fa-cloud-upload-alt me-1"></i> Upload Critical Documents
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($approval_log && $approved_cap): ?>
                                                <div class="alert alert-success mt-3 mb-0">
                                                    <i class="fas fa-check-circle"></i> Both critical documents uploaded. Delete a file to upload a replacement.
                                                </div>
                                            <?php endif; ?>
                                        </form>
                                        
                                        <!-- Hidden Delete Forms for Critical Docs -->
                                        <?php if ($approval_log && !$approval_log['is_locked'] && $is_maker): ?>
                                            <form id="del_cri_<?php echo $approval_log['id']; ?>" method="POST" action="" onsubmit="return confirm('Delete Approval Log?');" style="display:none;">
                                                <input type="hidden" name="delete_document" value="1">
                                                <input type="hidden" name="doc_id" value="<?php echo $approval_log['id']; ?>">
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($approved_cap && !$approved_cap['is_locked'] && $is_maker): ?>
                                            <form id="del_cri_<?php echo $approved_cap['id']; ?>" method="POST" action="" onsubmit="return confirm('Delete Approved CAP?');" style="display:none;">
                                                <input type="hidden" name="delete_document" value="1">
                                                <input type="hidden" name="doc_id" value="<?php echo $approved_cap['id']; ?>">
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Checker Decision Card -->
                                <!-- Checker Decision Card Removed (Global Logic Used) -->

                                <!-- Combined Document Table & Upload -->
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-white py-3">
                                        <h6 class="mb-0 fw-bold"><i class="fas fa-file-contract me-2 text-primary"></i>Legal Documents</h6>
                                    </div>
                                    <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle mb-0" id="doc-table-<?php echo $cap['id']; ?>">
                                                    <thead class="bg-light text-muted small text-uppercase w-100">
                                                        <tr>
                                                            <th style="width: 30%;">Document Type</th>
                                                            <th style="width: 35%;">File / Preview</th>
                                                            <th style="width: 25%;">Remark</th>
                                                            <th style="width: 10%;">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- Existing Documents -->
                                                        <?php foreach ($cap_legal_docs as $doc): 
                                                            // Skip Critical Docs (they are shown in the header card)
                                                            if (in_array($doc['document_type'], ['Approval Log', 'Approved CAP'])) continue;
                                                        ?>
                                                            <tr class="existing-doc-row bg-light bg-opacity-10">
                                                                <td>
                                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($doc['document_type']); ?></div>
                                                                    <small class="text-muted"><?php echo htmlspecialchars($doc['original_filename']); ?></small>
                                                                </td>
                                                                <td>
<a href="<?php echo getWebUrl($doc['file_path']); ?>" 
                                                                       onclick="viewDocument(event, this.href)"
                                                                       class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                                        <i class="fas fa-eye me-1"></i> View Document
                                                                    </a>
                                                                </td>
                                                                <td>
                                                                    <span class="text-muted small"><?php echo htmlspecialchars($doc['remark']); ?></span>
                                                                </td>
                                                                <td>
                                                                    <?php if (!$doc['is_locked'] && $is_maker): ?>
                                                                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this document?');" style="display:inline;">
                                                                            <input type="hidden" name="delete_document" value="1">
                                                                            <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                                                                <i class="fas fa-trash-alt"></i>
                                                                            </button>
                                                                        </form>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-secondary"><i class="fas fa-lock"></i></span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>

                                                        <!-- AJAX Upload Row Template -->
                                                        <tr class="ajax-upload-row" data-cap-id="<?php echo htmlspecialchars($cap['cap_id']); ?>" data-category="Legal">
                                                            <td>
                                                                <select class="form-select form-select-sm doc-type">
                                                                    <option value="">Select Type...</option>
                                                                    <?php
                                                                    $legal_types = ['Loan Agreement', 'Mortgage Deed', 'Promissory Note', 'Guarantee Documents', 'Legal Opinion', 'Sanction Letter', 'Disbursement Letter', 'Other'];
                                                                    foreach ($legal_types as $type)
                                                                        echo "<option value='$type'>$type</option>";
                                                                    ?>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="file" class="form-control form-control-sm doc-file" accept=".pdf,.jpg,.png,.doc,.docx">
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control form-control-sm doc-remark" placeholder="Remark">
                                                            </td>
                                                            <td>
                                                                    <div class="d-flex gap-2">
                                                                        <button type="button" class="btn btn-sm btn-success text-white" onclick="uploadRow(this)">
                                                                            <i class="fas fa-save"></i>
                                                                        </button>
                                                                        <button type="button" class="btn btn-sm btn-info text-white" onclick="addEmptyRow(this)">
                                                                            <i class="fas fa-plus"></i>
                                                                        </button>
                                                                    </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                    </div>
                                </div>

                                <!-- Document Grid Removed -->

                                <!-- Maker Submit CAP Button -->
                                <?php if ($is_maker && (empty($cap['status']) || in_array($cap['status'], ['Draft', 'Returned']))): ?>
                                    <div class="card border-success mt-4">
                                        <div class="card-body">
                                            <form method="POST">
                                                <input type="hidden" name="cap_id" value="<?php echo htmlspecialchars($cap['cap_id']); ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label text-muted small">Submission Remarks (Optional)</label>
                                                    <textarea name="maker_comment" class="form-control form-control-sm" rows="2" placeholder="Start typing remarks..."></textarea>
                                                </div>

                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div>
                                                        <h6 class="mb-1 text-success fw-bold">
                                                            <i class="fas fa-paper-plane me-2"></i>Submit CAP to Legal
                                                        </h6>
                                                        <p class="text-muted mb-0 small">
                                                            Submit <?php echo htmlspecialchars($cap['cap_id']); ?> for legal vetting
                                                        </p>
                                                    </div>
                                                    <button type="submit" name="submit_cap_to_legal" class="btn btn-success"
                                                        onclick="return confirm('Submit <?php echo $cap['cap_id']; ?> to Legal for vetting?');">
                                                        <i class="fas fa-check-circle me-1"></i> Submit CAP
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- CAP-Level Checker Decision Card -->
                                <?php if ($is_checker && $cap['status'] == 'Pending Legal'): ?>
                                    <div class="card border-info mt-4">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0"><i class="fas fa-gavel"></i> Legal Decision for <?php echo htmlspecialchars($cap['cap_id']); ?></h6>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST" action="">
                                                <input type="hidden" name="cap_id" value="<?php echo htmlspecialchars($cap['cap_id']); ?>">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Legal Remarks <span class="text-danger">*</span></label>
                                                    <textarea class="form-control" name="comment" rows="3" required placeholder="Enter your remarks for this CAP..."></textarea>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button type="submit" name="checker_cap_action" value="approve" class="btn btn-success flex-grow-1"
                                                        onclick="return confirm('Approve <?php echo $cap['cap_id']; ?>? This will lock ALL documents (General, Security, Legal).');">
                                                        <i class="fas fa-check-circle me-1"></i> Approve CAP
                                                    </button>
                                                    <button type="submit" name="checker_cap_action" value="return" class="btn btn-danger flex-grow-1"
                                                        onclick="return confirm('Return <?php echo $cap['cap_id']; ?> to Maker?');">
                                                        <i class="fas fa-undo me-1"></i> Return CAP
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>


</div>

<script>
    function uploadRow(btn) {
        // ... (existing JS)
    }
    // ... (existing JS)
</script>
<!-- Add CAP Modal -->
<div class="modal fade" id="addCapModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New CAP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p class="text-muted small">Enter a valid CAP ID to link it to this customer.</p>
                    <div class="mb-3">
                        <label class="form-label">CAP ID</label>
                        <input type="text" name="cap_id" class="form-control" required placeholder="e.g. CAP-2023-001">
                        <small class="text-info"><i class="fas fa-info-circle"></i> For testing, any ID format is accepted.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_new_cap" class="btn btn-primary">Add CAP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Submit Profile Modal -->
<div class="modal fade" id="submitProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit Profile for Vetting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="customer_profile.php?id=<?php echo $id; ?>" onsubmit="alert('Form submitting...'); return true;">
                <!-- Robust Form: Hidden input + Action -->
                <input type="hidden" name="submit_profile_global" value="1">
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> This will submit <strong>ALL</strong> documents (General, Security, Legal, Critical) for vetting.
                    </div>
                    <p>Are you sure you want to proceed?</p>
                    <div class="mb-3">
                        <label class="form-label">Comments (Optional)</label>
                        <textarea name="comment" class="form-control" rows="3" placeholder="Any remarks for the legal team..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane me-1"></i> Submit Profile
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Global Checker Decision Card REMOVED - Now using CAP-level approval -->







<!-- Document Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content h-100">
            <div class="modal-header">
                <h5 class="modal-title">Document Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 85vh;">
                 <iframe id="previewFrame" src="" style="width:100%; height:100%; border:none;"></iframe>
            </div>
        </div>
    </div>
</div>

            <!-- Comments History Tab -->
            <div class="tab-pane fade" id="comments" role="tabpanel" aria-labelledby="comments-tab">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <h5 class="mb-0"><i class="fas fa-history text-info me-2"></i>Audit Trail & Comments</h5>
                            
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <small class="text-muted me-2">Filter by:</small>
                                
                                <!-- Action Filter -->
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary active" data-filter-type="action" data-filter-value="all" onclick="filterComments('action', 'all', this)">
                                        All
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" data-filter-type="action" data-filter-value="Submitted" onclick="filterComments('action', 'Submitted', this)">
                                        Submitted
                                    </button>
                                    <button type="button" class="btn btn-outline-success" data-filter-type="action" data-filter-value="Approved" onclick="filterComments('action', 'Approved', this)">
                                        Approved
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" data-filter-type="action" data-filter-value="Returned" onclick="filterComments('action', 'Returned', this)">
                                        Returned
                                    </button>
                                </div>
                                
                                <!-- Role Filter -->
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary active" data-filter-type="role" data-filter-value="all" onclick="filterComments('role', 'all', this)">
                                        All Roles
                                    </button>
                                    <button type="button" class="btn btn-outline-info" data-filter-type="role" data-filter-value="Maker" onclick="filterComments('role', 'Maker', this)">
                                        Maker
                                    </button>
                                    <button type="button" class="btn btn-outline-warning" data-filter-type="role" data-filter-value="Checker" onclick="filterComments('role', 'Checker', this)">
                                        Checker
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush chat-history">
                            <?php 
                            $history_query = "
                                SELECT lc.created_at, u.full_name, u.role, lc.action, cd.cap_id, lc.comment 
                                FROM legal_comments lc 
                                JOIN edms_users u ON lc.user_id = u.id 
                                LEFT JOIN cap_documents cd ON lc.cap_document_id = cd.id
                                WHERE cd.customer_id = $id 
                                ORDER BY lc.created_at DESC
                            ";
                            $history = $conn->query($history_query)->fetch_all(MYSQLI_ASSOC);
                            
                            if(empty($history)): ?>
                                <div class="text-center p-5 text-muted">
                                    <i class="fas fa-comment-slash fa-3x mb-3 opacity-50"></i>
                                    <p>No comments or history found.</p>
                                </div>
                            <?php else: foreach($history as $row): 
                                $bg_color = ($row['role'] == 'Checker') ? 'bg-info-soft' : 'bg-light';
                                $border_color = ($row['role'] == 'Checker') ? 'border-info' : 'border-light';
                            ?>
                                <div class="list-group-item comment-item p-3 mb-2 rounded border <?php echo $border_color; ?> <?php echo $bg_color; ?>" 
                                     data-action="<?php echo htmlspecialchars($row['action']); ?>" 
                                     data-role="<?php echo htmlspecialchars($row['role']); ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar small me-2" style="background-color: <?php echo getAvatarColor($row['role'], $row['full_name']); ?>">
                                                <?php echo getUserInitials($row['full_name']); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($row['full_name']); ?> <small class="text-muted fw-normal ms-1">(<?php echo $row['role']; ?>)</small></h6>
                                                <small class="text-primary fw-bold"><?php echo htmlspecialchars($row['action']); ?> <?php if($row['cap_id']) echo "- " . htmlspecialchars($row['cap_id']); ?></small>
                                            </div>
                                        </div>
                                        <small class="text-muted"><i class="far fa-clock me-1"></i><?php echo date('M d, h:i A', strtotime($row['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-0 text-dark small ps-5" style="white-space: pre-wrap;"><?php echo htmlspecialchars($row['comment']); ?></p>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ... existing scripts ...
    // Define Legal Types in JS to avoid PHP injection issues
    const legalDocTypes = [
        "Mortgage Deed", "Loan Agreement", "Personal Guarantee",
        "Corporate Guarantee", "Promissory Note", "Board Resolution",
        "Power of Attorney", "Insurance Policy", "Valuation Report", "Other"
    ];
    // Delete document function
    function lockCapDocuments(capId) {
        // Implementation handled by server-side status update
    }
    document.addEventListener('DOMContentLoaded', function () {
        // Init any tooltips or other UI elements if needed
    });

    // Comment Filter Function
    let activeFilters = {
        action: 'all',
        role: 'all'
    };

    function filterComments(filterType, filterValue, button) {
        // Update active filter
        activeFilters[filterType] = filterValue;
        
        // Update button states
        const buttonGroup = button.closest('.btn-group');
        buttonGroup.querySelectorAll('button').forEach(btn => {
            btn.classList.remove('active');
        });
        button.classList.add('active');
        
        // Get all comment items
        const commentItems = document.querySelectorAll('.comment-item');
        
        // Filter comments
        commentItems.forEach(item => {
            const itemAction = item.getAttribute('data-action');
            const itemRole = item.getAttribute('data-role');
            
            const actionMatch = activeFilters.action === 'all' || itemAction === activeFilters.action;
            const roleMatch = activeFilters.role === 'all' || itemRole === activeFilters.role;
            
            if (actionMatch && roleMatch) {
                item.style.display = '';
                item.style.animation = 'fadeIn 0.3s ease';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function addEmptyRow(btn) {
        var row = btn.closest('tr');
        var tbody = row.closest('tbody');
        
        // Clone the current row to create a new empty one
        var newRow = row.cloneNode(true);
        
        // Clear inputs
        newRow.querySelectorAll('input').forEach(input => input.value = '');
        if(newRow.querySelector('select')) newRow.querySelector('select').selectedIndex = 0;
        
        // Reset buttons
        var btns = newRow.querySelectorAll('button');
        btns[0].disabled = false;
        btns[0].innerHTML = '<i class="fas fa-save"></i>';
        // Ensure + button is present (in case cloned row was modifying state)
        // If the structure is: [Save] [Plus]
        // But if we are cloning an *active* upload row, it should handle itself.
        
        tbody.appendChild(newRow);
    }

    function uploadRow(btn) {
        var row = btn.closest('tr');
        var capId = row.getAttribute('data-cap-id');
        var category = row.getAttribute('data-category'); 
        var type = row.querySelector('.doc-type').value; 
        
        var docTypeVal = "";
        if (row.querySelector('.doc-type').tagName === 'SELECT') {
             docTypeVal = row.querySelector('.doc-type').value;
        } else {
             docTypeVal = row.querySelector('.doc-type').value.trim();
        }

        var remark = row.querySelector('.doc-remark').value;
        var fileInput = row.querySelector('.doc-file');
        
        if (fileInput.files.length === 0) {
            alert("Please select a file.");
            return;
        }
        if (docTypeVal === "") {
            alert("Please select or enter a document type.");
            return;
        }
        
        var formData = new FormData();
        formData.append('ajax_upload', '1');
        formData.append('cap_id', capId);
        formData.append('category', category); 
        formData.append('doc_type', docTypeVal);
        formData.append('remark', remark);
        formData.append('file', fileInput.files[0]);
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                 var btnCell = btn.closest('td');
                 
                // Transform row to view mode
                row.classList.remove('ajax-upload-row');
                row.classList.add('existing-doc-row', 'bg-light', 'bg-opacity-10');
                row.removeAttribute('data-cap-id'); 
                row.removeAttribute('data-category'); 

                row.innerHTML = `
                    <td>
                        <div class="fw-bold text-dark">${data.document_type}</div>
                        <small class="text-muted">${data.original_filename}</small>
                    </td>
                    <td>
                        <a href="${data.web_path}" onclick="viewDocument(event, this.href)" 
                           class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="fas fa-eye me-1"></i> View Document
                        </a>
                    </td>
                    <td>
                        <span class="text-muted small">${data.remark}</span>
                    </td>
                    <td>
                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this document?');" style="display:inline;">
                            <input type="hidden" name="delete_document" value="1">
                            <input type="hidden" name="doc_id" value="${data.doc_id}">
                            <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </td>
                `;

                // Add new empty row for next upload
                var tbody = row.parentNode;
                var newRow = document.createElement('tr');
                newRow.className = "ajax-upload-row";
                newRow.setAttribute('data-cap-id', capId);
                newRow.setAttribute('data-category', category);

                var typeInput = '';
                if (category === 'Legal' && typeof legalDocTypes !== 'undefined') {
                    // Build select from JS array
                    var options = legalDocTypes.map(t => `<option value="${t}">${t}</option>`).join('');
                    typeInput = `<select class="form-select form-select-sm doc-type">
                                    <option value="">Select Type...</option>
                                    ${options}
                                 </select>`;
                } else {
                    typeInput = `<input type="text" class="form-control form-control-sm doc-type" placeholder="e.g. Document">`;
                }

                newRow.innerHTML = `
                    <td>${typeInput}</td>
                    <td><input type="file" class="form-control form-control-sm doc-file" accept=".pdf,.jpg,.png,.doc,.docx"></td>
                    <td><input type="text" class="form-control form-control-sm doc-remark" placeholder="Remark"></td>
                    <td>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-success text-white" onclick="uploadRow(this)"><i class="fas fa-save"></i></button>
                            <button type="button" class="btn btn-sm btn-info text-white" onclick="addEmptyRow(this)"><i class="fas fa-plus"></i></button>
                        </div>
                    </td>
                `;
                tbody.appendChild(newRow);

            } else {
                alert("Upload failed: " + data.message);
                btn.innerHTML = '<i class="fas fa-save"></i>';
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("An error occurred during upload.");
            btn.innerHTML = '<i class="fas fa-save"></i>';
            btn.disabled = false;
        });
    }

    // Legacy functions removed (addTableRow, removeTableRow not needed for this flow)
</script>

<!-- Edit CAP Modals (one for each CAP) -->
<?php foreach ($cap_documents as $cap): ?>
    <div class="modal fade" id="editCapModal<?php echo $cap['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit CAP Name</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <p class="text-muted small">Rename CAP ID. Note: Cannot edit approved CAPs.</p>
                        <input type="hidden" name="old_cap_id" value="<?php echo htmlspecialchars($cap['cap_id']); ?>">
                        <div class="mb-3">
                            <label class="form-label">New CAP ID</label>
                            <input type="text" name="new_cap_id" class="form-control" 
                                value="<?php echo htmlspecialchars($cap['cap_id']); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_cap_name" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php include '../../includes/footer.php'; ?>
