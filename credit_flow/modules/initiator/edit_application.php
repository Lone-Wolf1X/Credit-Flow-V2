<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/avatar_helpers.php';

requireLogin();

$id = intval($_GET['id'] ?? 0);
$application = getApplication($id);

// Access check: Must be Initiator and Status must be Returned
if (!$application || $application['initiator_id'] != $_SESSION['user_id'] || $application['status'] !== 'Returned') {
    die('Unauthorized access or invalid application status.');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $applicant_name = sanitize($_POST['applicant_name'] ?? '');
    $contact_number = sanitize($_POST['contact_number'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $loan_segment = sanitize($_POST['loan_segment'] ?? '');
    $loan_scheme = sanitize($_POST['loan_scheme'] ?? '');
    $loan_type = sanitize($_POST['loan_type'] ?? '');
    $loan_limit = floatval($_POST['loan_limit'] ?? 0);
    $relationship_start_date = sanitize($_POST['relationship_start_date'] ?? '');
    $proposed_limit = floatval($_POST['proposed_limit'] ?? 0);
    $relationship_manager = sanitize($_POST['relationship_manager'] ?? '');
    $initiator_comment = sanitize($_POST['initiator_comment'] ?? '');

    // Multiple reviewers and single approver
    $reviewer_ids = isset($_POST['reviewer_ids']) ? json_decode($_POST['reviewer_ids'], true) : [];
    $approver_id = intval($_POST['approver_id'] ?? 0);

    if (
        empty($applicant_name) || empty($contact_number) || empty($address) ||
        empty($loan_segment) || empty($loan_type) || $proposed_limit <= 0 ||
        empty($reviewer_ids) || $approver_id <= 0
    ) {
        $error = 'Please fill all required fields including at least one Reviewer and Approver';
    } else {
        // Update loan application
        // Reset status to 'Under Review' (Reviewer stage) as we are re-initiating
        $stmt = $conn->prepare("
            UPDATE loan_applications 
            SET applicant_name=?, contact_number=?, address=?, loan_segment=?, loan_scheme=?, 
                loan_type=?, loan_limit=?, relationship_start_date=?, proposed_limit=?, relationship_manager=?, 
                status='Under Review', current_stage='Reviewer', updated_at=NOW(), approver_id=?
            WHERE id=?
        ");

        $stmt->bind_param(
            "sssssssdsdii",
            $applicant_name,
            $contact_number,
            $address,
            $loan_segment,
            $loan_scheme,
            $loan_type,
            $loan_limit,
            $relationship_start_date,
            $proposed_limit,
            $relationship_manager,
            $approver_id,
            $id
        );

        if ($stmt->execute()) {
            // Update Reviewers: Clear old and insert new
            $conn->query("DELETE FROM application_reviewers WHERE application_id = $id");

            $reviewer_stmt = $conn->prepare("INSERT INTO application_reviewers (application_id, reviewer_id) VALUES (?, ?)");
            foreach ($reviewer_ids as $reviewer_id) {
                $reviewer_stmt->bind_param("ii", $id, $reviewer_id);
                $reviewer_stmt->execute();
            }

            // Add audit log
            $reviewer_names = [];
            foreach ($reviewer_ids as $rid) {
                $user_stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
                $user_stmt->bind_param("i", $rid);
                $user_stmt->execute();
                $result = $user_stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $reviewer_names[] = $row['full_name'];
                }
            }
            $comment = "Re-initiated/Updated Application. " . $initiator_comment . "\nReviewers: " . implode(', ', $reviewer_names);
            addAuditLog($id, $_SESSION['user_id'], 'Re-initiated', $comment);

            // Send Notifications to Reviewers
            foreach ($reviewer_ids as $rid) {
                createNotification(
                    $rid,
                    $id,
                    'Application Updated',
                    "Application {$application['cap_id']} has been re-submitted for your review.",
                    "modules/reviewer/review_application.php?id=$id"
                );
            }

            // Handle file uploads
            if (!empty($_FILES['documents']['name'][0])) {
                foreach ($_FILES['documents']['name'] as $key => $name) {
                    if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['documents']['name'][$key],
                            'type' => $_FILES['documents']['type'][$key],
                            'tmp_name' => $_FILES['documents']['tmp_name'][$key],
                            'error' => $_FILES['documents']['error'][$key],
                            'size' => $_FILES['documents']['size'][$key]
                        ];
                        uploadFile($file, $id, $_SESSION['user_id'], $applicant_name);
                    }
                }
            }

            $success = "Application re-initiated successfully!";
            header("refresh:2;url=../../view_application.php?id=$id");
        } else {
            $error = 'Failed to update application. Please try again.';
        }
    }
}

// Prepare data for JS population
// Fetch current reviewers
$curr_reviewers = [];
$r_stmt = $conn->prepare("SELECT u.id, u.full_name, u.designation FROM application_reviewers ar JOIN users u ON ar.reviewer_id = u.id WHERE ar.application_id = ?");
$r_stmt->bind_param("i", $id);
$r_stmt->execute();
$curr_reviewers = $r_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$curr_reviewer_ids = array_column($curr_reviewers, 'id');

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

<div class="form-card">
    <h4 class="mb-4 text-danger"><i class="fas fa-edit"></i> Edit Returned Application</h4>
    <div class="alert alert-warning">
        <strong>CAP ID:</strong> <?php echo htmlspecialchars($application['cap_id']); ?>
    </div>

    <form method="POST" action="" enctype="multipart/form-data" id="loanForm" onsubmit="return validateLoanForm()">
        <!-- Applicant Details -->
        <h5 class="text-primary mb-3"><i class="fas fa-user"></i> Applicant Details</h5>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="applicant_name" class="form-label">Applicant Full Name <span
                        class="text-danger">*</span></label>
                <input type="text" class="form-control" id="applicant_name" name="applicant_name"
                    value="<?php echo htmlspecialchars($application['applicant_name']); ?>" required>
            </div>

            <div class="col-md-4 mb-3">
                <label for="contact_number" class="form-label">Contact Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="contact_number" name="contact_number"
                    value="<?php echo htmlspecialchars($application['contact_number']); ?>" required>
            </div>

            <div class="col-md-4 mb-3">
                <label for="relationship_start_date" class="form-label">Relationship Start Date</label>
                <input type="date" class="form-control" id="relationship_start_date" name="relationship_start_date"
                    value="<?php echo htmlspecialchars($application['relationship_start_date']); ?>">
            </div>
        </div>

        <div class="mb-3">
            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
            <textarea class="form-control" id="address" name="address" rows="2"
                required><?php echo htmlspecialchars($application['address']); ?></textarea>
        </div>

        <!-- Loan Details -->
        <h5 class="text-primary mb-3 mt-4"><i class="fas fa-money-bill-wave"></i> Loan Details</h5>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="loan_segment" class="form-label">Loan Segment <span class="text-danger">*</span></label>
                <select class="form-select" id="loan_segment" name="loan_segment" onchange="updateLoanTypes(this.value)"
                    required>
                    <option value="">Select Segment</option>
                    <?php foreach (getLoanSegments() as $segment): ?>
                        <option value="<?php echo $segment; ?>" <?php echo $application['loan_segment'] == $segment ? 'selected' : ''; ?>><?php echo $segment; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label for="loan_type" class="form-label">Loan Type <span class="text-danger">*</span></label>
                <select class="form-select" id="loan_type" name="loan_type" required>
                    <option value="<?php echo htmlspecialchars($application['loan_type']); ?>" selected>
                        <?php echo htmlspecialchars($application['loan_type']); ?></option>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label for="loan_scheme" class="form-label">Loan Scheme</label>
                <input type="text" class="form-control" id="loan_scheme" name="loan_scheme"
                    value="<?php echo htmlspecialchars($application['loan_scheme']); ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="loan_limit" class="form-label">Existing Loan Limit (NPR)</label>
                <input type="number" step="0.01" class="form-control" id="loan_limit" name="loan_limit"
                    value="<?php echo htmlspecialchars($application['loan_limit']); ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label for="proposed_limit" class="form-label">Proposed Limit (NPR) <span
                        class="text-danger">*</span></label>
                <input type="number" step="0.01" class="form-control" id="proposed_limit" name="proposed_limit"
                    value="<?php echo htmlspecialchars($application['proposed_limit']); ?>" required>
            </div>

            <div class="col-md-4 mb-3">
                <label for="relationship_manager" class="form-label">Relationship Manager</label>
                <input type="text" class="form-control" id="relationship_manager" name="relationship_manager"
                    value="<?php echo htmlspecialchars($application['relationship_manager']); ?>">
            </div>
        </div>

        <!-- Workflow Assignment -->
        <h5 class="text-primary mb-3 mt-4"><i class="fas fa-users-cog"></i> Workflow Assignment</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="reviewer_input" class="form-label">Select Reviewers <span
                        class="text-danger">*</span></label>
                <div class="autocomplete-container">
                    <input type="text" class="form-control" id="reviewer_input" placeholder="Type name or staff ID..."
                        autocomplete="off">
                    <input type="hidden" id="reviewer_ids" name="reviewer_ids"
                        value='<?php echo json_encode($curr_reviewer_ids); ?>' required>
                    <div id="reviewer_input_results" class="autocomplete-results"></div>
                </div>
                <div id="selected_reviewers" class="selected-users-container"></div>
                <small class="text-muted">Modify reviewers if needed</small>
            </div>

            <div class="col-md-6 mb-3">
                <label for="approver_input" class="form-label">Select Approver <span
                        class="text-danger">*</span></label>
                <div class="autocomplete-container">
                    <input type="text" class="form-control" id="approver_input"
                        value="<?php echo htmlspecialchars($application['approver_name'] . ' - ' . $application['approver_staff_id']); ?>"
                        placeholder="Type name or staff ID to search..." autocomplete="off">
                    <input type="hidden" id="approver_id" name="approver_id"
                        value="<?php echo $application['approver_id']; ?>" required>
                    <div id="approver_input_results" class="autocomplete-results"></div>
                </div>
            </div>
        </div>

        <!-- Comments & Documents -->
        <h5 class="text-primary mb-3 mt-4"><i class="fas fa-comment-alt"></i> Additional Re-submission Notes</h5>
        <div class="mb-3">
            <label for="initiator_comment" class="form-label">Notes for Reviewer</label>
            <textarea class="form-control" id="initiator_comment" name="initiator_comment" rows="3"
                placeholder="Explain what changes you made..."></textarea>
        </div>

        <div class="mb-4">
            <label class="form-label"><i class="fas fa-paperclip"></i> Add More Documents (Optional)</label>
            <input type="file" class="form-control" id="loanDocuments" name="documents[]" multiple
                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" onchange="previewFile(this)">
            <small class="text-muted">New files will be added to the existing list.</small>
            <div id="fileList" class="mt-2"></div>
        </div>

        <div class="text-end">
            <a href="../../view_application.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-redo"></i> Update & Re-submit
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        setupMultiUserSelect('reviewer_input', 'reviewer_ids', 'selected_reviewers', 'reviewer');
        setupAutocomplete('approver_input', 'approver_id', 'approver');

        // Pre-populate reviewers in JS visual list
        const existingReviewers = <?php echo json_encode($curr_reviewers); ?>;
        const container = document.getElementById('selected_reviewers');
        const hidden = document.getElementById('reviewer_ids');

        // We need to inject these into the global 'selectedReviewers' array in main.js scope ideally, 
        // OR manually re-render. Since main.js handles 'selectedReviewers' variable globally, 
        // we can try to push to it if it's accessible, or just rely on our init function.
        // Actually main.js defines 'selectedReviewers' at top level.
        // We can access it here.
        if (typeof selectedReviewers !== 'undefined') {
            existingReviewers.forEach(u => selectedReviewers.push(u));
            renderSelectedUsers(container, hidden);
        }
    });

    // We also need to trigger updateLoanTypes to populate the 2nd dropdown properly based on 1st, 
    // although we manually set the "selected" attribute in PHP, the options might be missing if we don't init.
    // Let's call it.
    updateLoanTypes('<?php echo $application['loan_segment']; ?>');
    // Re-select the type after population (since updateLoanTypes wipes options)
    setTimeout(() => {
        document.getElementById('loan_type').value = '<?php echo htmlspecialchars($application['loan_type']); ?>';
    }, 100);

</script>

<?php include '../../includes/footer.php'; ?>