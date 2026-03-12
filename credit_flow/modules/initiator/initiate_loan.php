<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/avatar_helpers.php';

requireLogin();

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
        // Generate CAP ID
        $cap_id = generateCapId($loan_segment, $loan_type);

        if (!$cap_id) {
            $error = 'Failed to generate CAP ID. Please contact administrator.';
        } else {
            // Insert loan application (reviewer_id will be NULL, we use application_reviewers table)
            $stmt = $conn->prepare("
                INSERT INTO loan_applications 
                (cap_id, applicant_name, contact_number, address, loan_segment, loan_scheme, 
                 loan_type, loan_limit, relationship_start_date, proposed_limit, relationship_manager, 
                 initiator_id, reviewer_id, approver_id, status, current_stage) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, 'Initiated', 'Reviewer')
            ");

            $stmt->bind_param(
                "sssssssdsdsii",
                $cap_id,
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
                $_SESSION['user_id'],
                $approver_id
            );

            if ($stmt->execute()) {
                $application_id = $conn->insert_id;

                // Insert multiple reviewers
                $reviewer_stmt = $conn->prepare("INSERT INTO application_reviewers (application_id, reviewer_id) VALUES (?, ?)");
                foreach ($reviewer_ids as $reviewer_id) {
                    $reviewer_stmt->bind_param("ii", $application_id, $reviewer_id);
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
                $comment = $initiator_comment . "\nAssigned to reviewers: " . implode(', ', $reviewer_names);
                addAuditLog($application_id, $_SESSION['user_id'], 'Initiated', $comment);

                // Send Notifications to Reviewers
                foreach ($reviewer_ids as $rid) {
                    createNotification(
                        $rid,
                        $application_id,
                        'New Review',
                        "New application $cap_id assigned for your review.",
                        "modules/reviewer/review_application.php?id=$application_id"
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
                            uploadFile($file, $application_id, $_SESSION['user_id'], $applicant_name);
                        }
                    }
                }

                $success = "Loan application initiated successfully! CAP ID: <strong>$cap_id</strong>";

                // Redirect after 2 seconds
                header("refresh:2;url=../../view_application.php?id=$application_id");
            } else {
                $error = 'Failed to create application. Please try again.';
            }
        }
    }
}

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
    <h4 class="mb-4"><i class="fas fa-file-alt"></i> Loan Initiation Form</h4>

    <form method="POST" action="" enctype="multipart/form-data" id="loanForm" onsubmit="return validateLoanForm()">
        <!-- Applicant Details -->
        <h5 class="text-primary mb-3"><i class="fas fa-user"></i> Applicant Details</h5>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="applicant_name" class="form-label">Applicant Full Name <span
                        class="text-danger">*</span></label>
                <input type="text" class="form-control" id="applicant_name" name="applicant_name" required>
            </div>

            <div class="col-md-4 mb-3">
                <label for="contact_number" class="form-label">Contact Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="contact_number" name="contact_number" required>
            </div>

            <div class="col-md-4 mb-3">
                <label for="relationship_start_date" class="form-label">Relationship Start Date</label>
                <input type="date" class="form-control" id="relationship_start_date" name="relationship_start_date">
            </div>
        </div>

        <div class="mb-3">
            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
            <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
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
                        <option value="<?php echo $segment; ?>"><?php echo $segment; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label for="loan_type" class="form-label">Loan Type <span class="text-danger">*</span></label>
                <select class="form-select" id="loan_type" name="loan_type" required>
                    <option value="">Select Loan Type</option>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label for="loan_scheme" class="form-label">Loan Scheme</label>
                <input type="text" class="form-control" id="loan_scheme" name="loan_scheme">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="loan_limit" class="form-label">Existing Loan Limit (NPR)</label>
                <input type="number" step="0.01" class="form-control" id="loan_limit" name="loan_limit">
            </div>

            <div class="col-md-4 mb-3">
                <label for="proposed_limit" class="form-label">Proposed Limit (NPR) <span
                        class="text-danger">*</span></label>
                <input type="number" step="0.01" class="form-control" id="proposed_limit" name="proposed_limit"
                    required>
            </div>

            <div class="col-md-4 mb-3">
                <label for="relationship_manager" class="form-label">Relationship Manager</label>
                <input type="text" class="form-control" id="relationship_manager" name="relationship_manager">
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
                    <input type="hidden" id="reviewer_ids" name="reviewer_ids" value="[]" required>
                    <div id="reviewer_input_results" class="autocomplete-results"></div>
                </div>
                <div id="selected_reviewers" class="selected-users-container"></div>
                <small class="text-muted">You can select multiple reviewers</small>
            </div>

            <div class="col-md-6 mb-3">
                <label for="approver_input" class="form-label">Select Approver <span
                        class="text-danger">*</span></label>
                <div class="autocomplete-container">
                    <input type="text" class="form-control" id="approver_input"
                        placeholder="Type name or staff ID to search..." autocomplete="off">
                    <input type="hidden" id="approver_id" name="approver_id" required>
                    <div id="approver_input_results" class="autocomplete-results"></div>
                </div>
                <small class="text-muted">Start typing to search for approvers</small>
            </div>
        </div>

        <!-- Comments & Documents -->
        <h5 class="text-primary mb-3 mt-4"><i class="fas fa-comment-alt"></i> Additional Information</h5>
        <div class="mb-3">
            <label for="initiator_comment" class="form-label">Initiator Comment</label>
            <textarea class="form-control" id="initiator_comment" name="initiator_comment" rows="3"
                placeholder="Add any relevant comments or notes"></textarea>
        </div>

        <div class="mb-4">
            <label class="form-label"><i class="fas fa-paperclip"></i> Upload Documents</label>
            <input type="file" class="form-control" id="loanDocuments" name="documents[]" multiple
                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" onchange="previewFile(this)">
            <small class="text-muted">PDF, JPG, PNG - Max 5MB each. You can select multiple files.</small>
            <div id="fileList" class="mt-2"></div>
        </div>

        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <strong>Note:</strong> CAP ID will be auto-generated upon submission.
        </div>

        <div class="text-end">
            <a href="../../dashboard.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Submit Application
            </button>
        </div>
    </form>
</div>

<script>
    // Initialize autocomplete
    document.addEventListener('DOMContentLoaded', function () {
        setupMultiUserSelect('reviewer_input', 'reviewer_ids', 'selected_reviewers', 'reviewer');
        setupAutocomplete('approver_input', 'approver_id', 'approver');
    });
</script>

<?php include '../../includes/footer.php'; ?>