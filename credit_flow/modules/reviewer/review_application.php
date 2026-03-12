<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/avatar_helpers.php';

requireLogin();
requireLogin();

$id = intval($_GET['id'] ?? 0);
$application = getApplication($id);

// Check if user is an assigned reviewer
$check_stmt = $conn->prepare("SELECT 1 FROM application_reviewers WHERE application_id = ? AND reviewer_id = ?");
$check_stmt->bind_param("ii", $id, $_SESSION['user_id']);
$check_stmt->execute();
$is_reviewer = $check_stmt->get_result()->num_rows > 0;

if (!$application || !$is_reviewer) {
    header('Location: ../../dashboard.php');
    exit;
}

if ($application['current_stage'] !== 'Reviewer') {
    header('Location: ../../view_application.php?id=' . $id);
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $comment = sanitize($_POST['comment'] ?? '');

    if (empty($comment)) {
        $error = 'Please provide a comment';
    } elseif ($action === 'recommend') {
        if ($application['approver_id'] == $_SESSION['user_id']) {
            // Direct Approval (Approver is also Reviewer, and chose to approve directly)
            $stmt = $conn->prepare("UPDATE loan_applications SET status = 'Approved', current_stage = 'Completed', updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                addAuditLog($id, $_SESSION['user_id'], 'Approved', $comment . ' (Direct Approval from Review)');
                
                // Mark reviewer entry as reviewed too (for consistency)
                $mark_stmt = $conn->prepare("UPDATE application_reviewers SET status = 'Reviewed', reviewed_at = NOW() WHERE application_id = ? AND reviewer_id = ?");
                $mark_stmt->bind_param("ii", $id, $_SESSION['user_id']);
                $mark_stmt->execute();
                
                // Notify Initiator
                createNotification(
                    $application['initiator_id'],
                    $id,
                    'Application Approved',
                    "Application {$application['cap_id']} has been approved.",
                    "modules/initiator/view_application.php?id=$id"
                );

                // Notify All Reviewers (including self/other reviewers)
                $rev_stmt = $conn->prepare("SELECT reviewer_id FROM application_reviewers WHERE application_id = ?");
                $rev_stmt->bind_param("i", $id);
                $rev_stmt->execute();
                $rev_result = $rev_stmt->get_result();
                while($row = $rev_result->fetch_assoc()) {
                    if ($row['reviewer_id'] != $_SESSION['user_id']) {
                        createNotification(
                            $row['reviewer_id'],
                            $id,
                            'Application Approved',
                            "Application {$application['cap_id']} has been approved.",
                            "modules/reviewer/review_application.php?id=$id"
                        );
                    }
                }

                $success = 'Application approved successfully!';
                header("refresh:2;url=../../view_application.php?id=$id");
            } else {
                $error = 'Failed to update application';
            }
        } else {
            // Standard Review: Mark THIS reviewer as done
            $mark_stmt = $conn->prepare("UPDATE application_reviewers SET status = 'Reviewed', reviewed_at = NOW() WHERE application_id = ? AND reviewer_id = ?");
            $mark_stmt->bind_param("ii", $id, $_SESSION['user_id']);
            
            if ($mark_stmt->execute()) {
                addAuditLog($id, $_SESSION['user_id'], 'Recommended', $comment);

                // Check if ALL assigned reviewers have finished
                $check_all = $conn->prepare("SELECT COUNT(*) as pending_count FROM application_reviewers WHERE application_id = ? AND status = 'Pending'");
                $check_all->bind_param("i", $id);
                $check_all->execute();
                $pending_count = $check_all->get_result()->fetch_assoc()['pending_count'];

                if ($pending_count == 0) {
                    // All reviews complete -> Move to Approver
                    $stmt = $conn->prepare("UPDATE loan_applications SET status = 'Under Review', current_stage = 'Approver', updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();

                    // Notify Approver
                    if ($application['approver_id']) {
                        createNotification(
                            $application['approver_id'],
                            $id,
                            'Pending Approval',
                            "Application {$application['cap_id']} recommended by all reviewers. Pending your approval.",
                            "modules/approver/approve_application.php?id=$id"
                        );
                    }
                    $success = 'Review submitted. Application moved to Approver.';
                } else {
                    // Still pending other reviewers -> Keep status 'Under Review', Stage 'Reviewer'
                    // Just update timestamp
                    $stmt = $conn->prepare("UPDATE loan_applications SET updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();

                    $success = "Review submitted successfully! Waiting for $pending_count other reviewer(s).";
                }

                header("refresh:2;url=../../view_application.php?id=$id");
            } else {
                $error = 'Failed to update review status';
            }
        }
    } elseif ($action === 'return') {
        // Return to initiator
        $stmt = $conn->prepare("UPDATE loan_applications SET status = 'Returned', current_stage = 'Initiator', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            addAuditLog($id, $_SESSION['user_id'], 'Returned', $comment);

            // Notify Initiator
            createNotification(
                $application['initiator_id'],
                $id,
                'Application Returned',
                "Application {$application['cap_id']} returned by reviewer.",
                "modules/initiator/view_application.php?id=$id"
            );
            $success = 'Application returned to initiator successfully!';
            header("refresh:2;url=../../dashboard.php");
        } else {
            $error = 'Failed to update application';
        }
    } else {
        $error = 'Invalid action';
    }
}

$files = getApplicationFiles($id);
$comments = getApplicationComments($id);

include '../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <h4 class="mb-4"><i class="fas fa-tasks"></i> Review Application</h4>

            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i> <strong>CAP ID:</strong>
                <?php echo htmlspecialchars($application['cap_id']); ?>
            </div>

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
                <div class="col-md-6">
                    <strong>Loan Segment:</strong> <?php echo htmlspecialchars($application['loan_segment']); ?><br>
                    <strong>Loan Type:</strong> <?php echo htmlspecialchars($application['loan_type']); ?>
                </div>
                <div class="col-md-6">
                    <strong>Existing Limit:</strong>
                    <?php echo $application['loan_limit'] ? formatCurrency($application['loan_limit']) : 'N/A'; ?><br>
                    <strong>Proposed Limit:</strong> <span
                        class="text-success"><?php echo formatCurrency($application['proposed_limit']); ?></span>
                </div>
            </div>

            <?php if (!empty($files)): ?>
                <hr>
                <h5 class="mb-3">Documents</h5>
                <div class="list-group mb-3">
                    <?php foreach ($files as $file): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-file-<?php echo $file['file_type'] === 'pdf' ? 'pdf' : ($file['file_type'] === 'xls' || $file['file_type'] === 'xlsx' ? 'excel' : ($file['file_type'] === 'doc' || $file['file_type'] === 'docx' ? 'word' : 'image')); ?>"></i>
                                <?php echo htmlspecialchars($file['original_filename']); ?>
                            </span>
                            <?php $is_pdf = strtolower($file['file_type']) === 'pdf'; ?>
                            <a href="../../<?php echo $file['file_path']; ?>" 
                               target="_blank"
                               <?php echo $is_pdf ? '' : 'download="' . htmlspecialchars($file['original_filename']) . '"'; ?>
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-<?php echo $is_pdf ? 'eye' : 'download'; ?>"></i> <?php echo $is_pdf ? 'View' : 'Download'; ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <hr>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="comment" class="form-label">Reviewer Comment <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="comment" name="comment" rows="4" required
                        placeholder="Enter your review comments here..."></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="../../view_application.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                    <div>
                        <button type="submit" name="action" value="return" class="btn btn-danger"
                            onclick="return confirmAction('Are you sure you want to return this application to the initiator?')">
                            <i class="fas fa-undo"></i> Return to Initiator
                        </button>
                        <?php if ($application['approver_id'] == $_SESSION['user_id']): ?>
                            <button type="submit" name="action" value="recommend" class="btn btn-success"
                                onclick="return confirmAction('You are also the Approver. This will instantly APPROVE the application. Continue?')">
                                <i class="fas fa-check-double"></i> Approve & Sign-off
                            </button>
                        <?php else: ?>
                            <button type="submit" name="action" value="recommend" class="btn btn-success"
                                onclick="return confirmAction('Are you sure you want to recommend this application for approval?')">
                                <i class="fas fa-thumbs-up"></i> Recommend for Approval
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-card">
            <h5 class="mb-3"><i class="fas fa-history"></i> Audit Trail</h5>
            <div class="timeline">
                <?php foreach ($comments as $comment): ?>
                    <div class="timeline-item">
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <span class="timeline-user"><?php echo htmlspecialchars($comment['full_name']); ?></span>
                                <span
                                    class="timeline-date"><?php echo date('d M Y', strtotime($comment['created_at'])); ?></span>
                            </div>
                            <div class="timeline-action">
                                <span
                                    class="<?php echo getStatusBadge($comment['action']); ?>"><?php echo $comment['action']; ?></span>
                            </div>
                            <?php if ($comment['comment']): ?>
                                <div class="mt-2">
                                    <small><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>