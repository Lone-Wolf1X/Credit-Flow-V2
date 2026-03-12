<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

requireLogin();

// Fetch returned applications for the current user (Initiator)
$stmt = $conn->prepare("SELECT * FROM loan_applications WHERE initiator_id = ? AND status = 'Returned' ORDER BY updated_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include '../../includes/header.php';
?>

<div class="table-card">
    <h4 class="mb-3 text-danger"><i class="fas fa-undo"></i> Returned Applications</h4>

    <?php if (empty($applications)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> No returned applications found. Great job!
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-circle"></i> The following applications have been returned for correction. Please
            review and re-submit.
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>CAP ID</th>
                        <th>Applicant Name</th>
                        <th>Loan Type</th>
                        <th>Proposed Limit</th>
                        <th>Returned Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($app['cap_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($app['applicant_name']); ?></td>
                            <td><?php echo htmlspecialchars($app['loan_type']); ?></td>
                            <td><?php echo formatCurrency($app['proposed_limit']); ?></td>
                            <td><?php echo date('d M Y', strtotime($app['updated_at'])); ?></td>
                            <td>
                                <a href="edit_application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-danger">
                                    <i class="fas fa-edit"></i> Edit & Re-submit
                                </a>
                                <a href="../../view_application.php?id=<?php echo $app['id']; ?>"
                                    class="btn btn-sm btn-secondary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>