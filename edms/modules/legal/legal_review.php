<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

requireLogin();

$cap_doc_id = intval($_GET['cap_doc_id'] ?? 0);

// Fetch CAP details
$cap_sql = "
    SELECT cd.*, c.customer_name, c.client_id, c.contact_number, u.full_name as submitted_by_name
    FROM cap_documents cd
    LEFT JOIN customers c ON cd.customer_id = c.id
    LEFT JOIN " . CF_DB_NAME . ".users u ON cd.submitted_by = u.id
    WHERE cd.id = ?
";
$stmt = $conn->prepare($cap_sql);
$stmt->bind_param("i", $cap_doc_id);
$stmt->execute();
$cap = $stmt->get_result()->fetch_assoc();

    if (!$cap) {
    die("CAP Document not found");
}

// Auto-assign / Mark as Picked (Under Review)
if ($cap['status'] === 'Pending Legal') {
    $conn->query("UPDATE cap_documents SET status = 'Under Review', reviewed_by = {$_SESSION['user_id']} WHERE id = $cap_doc_id");
    $cap['status'] = 'Under Review'; // Update local variable
}

// Fetch Linked Documents for this CAP
$docs_sql = "
    SELECT * FROM customer_documents 
    WHERE customer_id = ? AND cap_id = ? AND is_draft = 0
    ORDER BY document_type
";
$stmt = $conn->prepare($docs_sql);
$stmt->bind_param("is", $cap['customer_id'], $cap['cap_id']);
$stmt->execute();
$documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle Actions (Approve/Return)
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $comment = sanitize($_POST['comment']);

    if (empty($comment)) {
        $error = "Comment is required.";
    } else {
        if ($action === 'approve') {
            $update_sql = "UPDATE cap_documents SET status = 'Approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?";
        } else {
            $update_sql = "UPDATE cap_documents SET status = 'Returned', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?";
        }

        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ii", $_SESSION['user_id'], $cap_doc_id);

        if ($stmt->execute()) {
            // Log Comment
            $c_stmt = $conn->prepare("INSERT INTO legal_comments (cap_document_id, user_id, action, comment) VALUES (?, ?, ?, ?)");
            $action_text = ($action == 'approve') ? 'Approved' : 'Returned';

            $c_stmt->bind_param("iiss", $cap_doc_id, $_SESSION['user_id'], $action_text, $comment);
            $c_stmt->execute();

            // Lock docs if approved
            if ($action === 'approve') {
                 lockCapDocuments($cap['cap_id']); 
            }

            $success = "CAP Document $action_text successfully!";
            header("refresh:2;url=legal_vetting.php");
        } else {
            $error = "Database update failed.";
        }
    }
}

include '../../includes/header.php';
?>

<style>
    .pdf-viewer {
        width: 100%;
        height: 600px;
        border: none;
        background: #f8f9fa;
    }
    
    .doc-item-active {
        background-color: #f0fdf4 !important;
        border-left: 4px solid var(--bs-success) !important;
    }
</style>

<div class="row g-4">
    <!-- Left Column: Document List & Actions -->
    <div class="col-md-4">
        <!-- Info Card -->
        <div class="card border-0 shadow-sm mb-3">
             <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                     <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                        <i class="fas fa-file-contract fa-2x"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($cap['cap_id']); ?></h5>
                        <small class="text-muted">Legal Vetting</small>
                    </div>
                </div>
                
                <div class="mb-2">
                    <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Customer</small>
                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($cap['customer_name']); ?></div>
                </div>
                
                <div class="mb-2">
                     <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Submitted By</small>
                    <div class="d-flex align-items-center">
                         <i class="fas fa-user-circle text-muted me-2"></i>
                         <span><?php echo htmlspecialchars($cap['submitted_by_name']); ?></span>
                    </div>
                </div>
             </div>
        </div>

        <!-- Document List -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-folder me-2 text-warning"></i>Documents</h6>
            </div>
            <div class="list-group list-group-flush" id="docList" style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($documents as $index => $doc): ?>
                    <div class="list-group-item doc-list-item <?php echo $index === 0 ? 'doc-item-active' : ''; ?> p-3 border-bottom"
                        onclick="loadDoc(this, '../../<?php echo $doc['file_path']; ?>')" style="cursor: pointer;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-dark small"><?php echo htmlspecialchars($doc['document_type']); ?></strong>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </div>
                        <div class="d-flex align-items-center small text-muted">
                            <i class="fas fa-paperclip me-1"></i>
                            <span class="text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($doc['original_filename']); ?></span>
                        </div>
                        <?php if ($doc['remark']): ?>
                            <div class="mt-1 small text-info bg-info bg-opacity-10 px-2 py-1 rounded d-inline-block">
                                <?php echo htmlspecialchars($doc['remark']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Decision Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-gavel me-2 text-danger"></i>Legal Decision</h6>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success small mb-3"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger small mb-3"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small text-muted text-uppercase fw-bold">Review Comments</label>
                        <textarea class="form-control bg-light" name="comment" rows="3" required
                            placeholder="Enter your legal observation..."></textarea>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="action" value="approve" class="btn btn-success fw-bold py-2"
                            onclick="return confirm('Confirm Approval? Documents will be locked permanently.')">
                            <i class="fas fa-check-circle me-2"></i> Approve CAP
                        </button>
                        <button type="submit" name="action" value="return" class="btn btn-danger fw-bold py-2"
                            onclick="return confirm('Return to Initiator? This will require re-submission.')">
                            <i class="fas fa-undo me-2"></i> Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Document Preview -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark" id="previewTitle">Document Preview</h6>
                <a href="#" id="openNewTab" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                    <i class="fas fa-external-link-alt me-1"></i> Open in New Tab
                </a>
            </div>
            <div class="card-body p-0 bg-light d-flex align-items-center justify-content-center">
                <iframe id="docFrame" class="pdf-viewer" src=""></iframe>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
    function loadDoc(element, url) {
        // Update Active State
        document.querySelectorAll('.doc-list-item').forEach(el => el.classList.remove('doc-item-active'));
        element.classList.add('doc-item-active');

        // Update Frame
        document.getElementById('docFrame').src = url;
        document.getElementById('openNewTab').href = url;

        // Update Title
        const title = element.querySelector('strong').innerText;
        document.getElementById('previewTitle').innerText = title;
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function () {
        const firstDoc = document.querySelector('.doc-list-item');
        if (firstDoc) {
            // Trigger click logic without click event to avoid weird scroll
            loadDoc(firstDoc, '../../<?php echo $documents[0]['file_path'] ?? ''; ?>');
        }
    });
</script>