<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

requireLogin();
requireRole('Admin');

$success = '';
$error = '';

// Handle matrix actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $loan_segment = sanitize($_POST['loan_segment']);
        $loan_type = sanitize($_POST['loan_type']);
        $reviewer_designation = sanitize($_POST['reviewer_designation']);
        $approver_designation = sanitize($_POST['approver_designation']);

        $stmt = $conn->prepare("INSERT INTO escalation_matrix (loan_segment, loan_type, reviewer_designation, approver_designation) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $loan_segment, $loan_type, $reviewer_designation, $approver_designation);

        if ($stmt->execute()) {
            $success = 'Escalation matrix entry added successfully!';
        } else {
            $error = 'Failed to add entry.';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM escalation_matrix WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = 'Entry deleted successfully!';
    }
}

// Get all matrix entries
$matrix_entries = $conn->query("SELECT * FROM escalation_matrix ORDER BY loan_segment, loan_type")->fetch_all(MYSQLI_ASSOC);

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

<div class="row">
    <div class="col-md-4">
        <div class="form-card">
            <h5 class="mb-3"><i class="fas fa-plus-circle"></i> Add Matrix Entry</h5>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">

                <div class="mb-3">
                    <label class="form-label">Loan Segment <span class="text-danger">*</span></label>
                    <select class="form-select" name="loan_segment" id="loan_segment"
                        onchange="updateLoanTypes(this.value)" required>
                        <option value="">Select Segment</option>
                        <?php foreach (getLoanSegments() as $segment): ?>
                            <option value="<?php echo $segment; ?>"><?php echo $segment; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Loan Type <span class="text-danger">*</span></label>
                    <select class="form-select" name="loan_type" id="loan_type" required>
                        <option value="">Select Loan Type</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Reviewer Designation <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="reviewer_designation"
                        placeholder="e.g., Branch Manager" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Approver Designation <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="approver_designation"
                        placeholder="e.g., Retail Credit Head" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-plus"></i> Add Entry
                </button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="table-card">
            <h5 class="mb-3"><i class="fas fa-sitemap"></i> Escalation Matrix</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Loan Segment</th>
                            <th>Loan Type</th>
                            <th>Reviewer</th>
                            <th>Approver</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matrix_entries as $entry): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($entry['loan_segment']); ?></strong></td>
                                <td><?php echo htmlspecialchars($entry['loan_type']); ?></td>
                                <td><?php echo htmlspecialchars($entry['reviewer_designation']); ?></td>
                                <td><?php echo htmlspecialchars($entry['approver_designation']); ?></td>
                                <td>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $entry['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirmAction('Delete this entry?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>