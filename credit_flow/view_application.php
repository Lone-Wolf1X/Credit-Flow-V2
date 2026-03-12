<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

requireLogin();

$id = intval($_GET['id'] ?? 0);
$application = getApplication($id);

if (!$application) {
    header('Location: dashboard.php');
    exit;
}

// Check if user has permission to view
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$can_view = false;
if (
    $role === 'Admin' ||
    $application['initiator_id'] == $user_id ||
    $application['reviewer_id'] == $user_id ||
    $application['approver_id'] == $user_id
) {
    $can_view = true;
}

if (!$can_view) {
    header('Location: dashboard.php');
    exit;
}

// Get files and comments
$files = getApplicationFiles($id);
$comments = getApplicationComments($id);

// Determine available actions
$can_edit = ($role === 'Initiator' && $application['initiator_id'] == $user_id && $application['status'] === 'Returned');
$can_review = ($role === 'Reviewer' && $application['reviewer_id'] == $user_id && $application['current_stage'] === 'Reviewer');
$can_approve = ($role === 'Approver' && $application['approver_id'] == $user_id && $application['current_stage'] === 'Approver');

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-file-alt"></i> Application Details</h4>
                <span class="<?php echo getStatusBadge($application['status']); ?> fs-5">
                    <?php echo $application['status']; ?>
                </span>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>CAP ID:</strong><br>
                    <span class="text-primary fs-5"><?php echo htmlspecialchars($application['cap_id']); ?></span>
                </div>
                <div class="col-md-6">
                    <strong>Application Date:</strong><br>
                    <?php echo date('d M Y, h:i A', strtotime($application['created_at'])); ?>
                </div>
            </div>

            <hr>

            <h5 class="mb-3">Applicant Information</h5>
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Full Name:</strong><br>
                    <?php echo htmlspecialchars($application['applicant_name']); ?>
                </div>
                <div class="col-md-6">
                    <strong>Contact Number:</strong><br>
                    <?php echo htmlspecialchars($application['contact_number']); ?>
                </div>
            </div>

            <div class="mb-3">
                <strong>Address:</strong><br>
                <?php echo htmlspecialchars($application['address']); ?>
            </div>

            <hr>

            <h5 class="mb-3">Loan Details</h5>
            <div class="row mb-3">
                <div class="col-md-4">
                    <strong>Loan Segment:</strong><br>
                    <?php echo htmlspecialchars($application['loan_segment']); ?>
                </div>
                <div class="col-md-4">
                    <strong>Loan Type:</strong><br>
                    <?php echo htmlspecialchars($application['loan_type']); ?>
                </div>
                <div class="col-md-4">
                    <strong>Loan Scheme:</strong><br>
                    <?php echo htmlspecialchars($application['loan_scheme'] ?: 'N/A'); ?>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Existing Loan Limit:</strong><br>
                    <?php echo $application['loan_limit'] ? formatCurrency($application['loan_limit']) : 'N/A'; ?>
                </div>
                <div class="col-md-6">
                    <strong>Proposed Limit:</strong><br>
                    <span class="text-success fs-5"><?php echo formatCurrency($application['proposed_limit']); ?></span>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Relationship Start Date:</strong><br>
                    <?php echo $application['relationship_start_date'] ? date('d M Y', strtotime($application['relationship_start_date'])) : 'N/A'; ?>
                </div>
                <div class="col-md-6">
                    <strong>Relationship Manager:</strong><br>
                    <?php echo htmlspecialchars($application['relationship_manager'] ?: 'N/A'); ?>
                </div>
            </div>

            <hr>

            <h5 class="mb-3">Workflow Information</h5>
            <div class="row mb-3">
                <div class="col-md-4">
                    <strong>Initiator:</strong><br>
                    <?php echo htmlspecialchars($application['initiator_name']); ?><br>
                    <small class="text-muted">Staff ID:
                        <?php echo htmlspecialchars($application['initiator_staff_id']); ?></small>
                </div>
                <div class="col-md-4">
                    <strong>Reviewer:</strong><br>
                    <?php echo htmlspecialchars($application['reviewer_name']); ?><br>
                    <small class="text-muted">Staff ID:
                        <?php echo htmlspecialchars($application['reviewer_staff_id']); ?></small>
                </div>
                <div class="col-md-4">
                    <strong>Approver:</strong><br>
                    <?php echo htmlspecialchars($application['approver_name']); ?><br>
                    <small class="text-muted">Staff ID:
                        <?php echo htmlspecialchars($application['approver_staff_id']); ?></small>
                </div>
            </div>

            <div class="mb-3">
                <strong>Current Stage:</strong>
                <span class="badge bg-info"><?php echo $application['current_stage']; ?></span>
            </div>

            <?php if (!empty($files)): ?>
                <hr>
                <h5 class="mb-3">Uploaded Documents</h5>
                <div class="list-group">
                    <?php foreach ($files as $file): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-file-<?php echo $file['file_type'] === 'pdf' ? 'pdf' : ($file['file_type'] === 'xls' || $file['file_type'] === 'xlsx' ? 'excel' : ($file['file_type'] === 'doc' || $file['file_type'] === 'docx' ? 'word' : 'image')); ?> text-primary"></i>
                                    <strong><?php echo htmlspecialchars($file['original_filename']); ?></strong><br>
                                    <small class="text-muted">
                                        Uploaded by <?php echo htmlspecialchars($file['uploaded_by_name']); ?>
                                        on <?php echo date('d M Y', strtotime($file['uploaded_at'])); ?>
                                    </small>
                                </div>
                                <?php $is_pdf = strtolower($file['file_type']) === 'pdf'; ?>
                                <a href="<?php echo $file['file_path']; ?>" 
                                   target="_blank"
                                   <?php echo $is_pdf ? '' : 'download="' . htmlspecialchars($file['original_filename']) . '"'; ?>
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-<?php echo $is_pdf ? 'eye' : 'download'; ?>"></i> <?php echo $is_pdf ? 'View' : 'Download'; ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mt-4">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>

                <?php if ($can_edit): ?>
                    <a href="modules/initiator/edit_application.php?id=<?php echo $id; ?>" class="btn btn-danger">
                        <i class="fas fa-edit"></i> Edit Application
                    </a>
                <?php endif; ?>

                <?php if ($can_review): ?>
                    <a href="modules/reviewer/review_application.php?id=<?php echo $id; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Review Application
                    </a>
                <?php endif; ?>

                <?php if ($can_approve): ?>
                    <a href="modules/approver/approve_application.php?id=<?php echo $id; ?>" class="btn btn-success">
                        <i class="fas fa-check"></i> Process Approval
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-history text-primary"></i> Application History</h5>
                <a href="generate_audit_log.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                    <i class="fas fa-file-download me-1"></i> Download Log
                </a>
            </div>

            <?php if (empty($comments)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-clock fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0">No activity recorded yet.</p>
                </div>
            <?php else: ?>
                <div class="timeline mt-3">
                    <?php foreach ($comments as $index => $comment): 
                        // Determine color based on action
                        $action_lower = strtolower($comment['action']);
                        $dot_class = 'bg-secondary';
                        if (strpos($action_lower, 'approv') !== false) $dot_class = 'bg-success';
                        else if (strpos($action_lower, 'return') !== false) $dot_class = 'bg-danger';
                        else if (strpos($action_lower, 'submit') !== false || strpos($action_lower, 'forward') !== false) $dot_class = 'bg-primary';
                        else if (strpos($action_lower, 'review') !== false) $dot_class = 'bg-warning';
                    ?>
                        <div class="timeline-item position-relative pb-4 ps-4 border-start border-2 <?php echo $index === count($comments) - 1 ? 'border-transparent' : 'border-light'; ?>">
                            <!-- Timeline Dot -->
                            <div class="position-absolute top-0 start-0 translate-middle rounded-circle <?php echo $dot_class; ?>" style="width: 12px; height: 12px; border: 2px solid white;"></div>
                            
                            <div class="timeline-content bg-light p-3 rounded-3 shadow-sm">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-0 fw-bold text-dark"><?php echo htmlspecialchars($comment['full_name']); ?></h6>
                                        <small class="text-uppercase text-xs text-secondary fw-bold"><?php echo htmlspecialchars($comment['designation']); ?></small>
                                    </div>
                                    <span class="badge bg-white text-dark border text-xs"><?php echo date('d M, h:i A', strtotime($comment['created_at'])); ?></span>
                                </div>
                                
                                <div class="mb-2">
                                    <span class="<?php echo getStatusBadge($comment['action']); ?>"><?php echo $comment['action']; ?></span>
                                </div>
                                
                                <?php if ($comment['comment']): ?>
                                    <div class="bg-white p-2 rounded border border-light">
                                        <i class="fas fa-quote-left text-muted small me-1 opacity-50"></i>
                                        <span class="text-muted small fst-italic"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>