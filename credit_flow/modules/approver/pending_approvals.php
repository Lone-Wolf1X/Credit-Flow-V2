<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

requireLogin();
requireLogin();

$stmt = $conn->prepare("SELECT * FROM loan_applications WHERE approver_id = ? AND current_stage = 'Approver' ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include '../../includes/header.php';
?>

<div class="table-card">
    <h4 class="mb-3"><i class="fas fa-check-circle"></i> Pending Approvals</h4>

    <?php if (empty($applications)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No applications pending for your approval.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>CAP ID</th>
                        <th>Applicant Name</th>
                        <th>Loan Type</th>
                        <th>Proposed Limit</th>
                        <th>Reviewers</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <?php
                        // Fetch reviewers
                        $reviewers = $conn->query("
                            SELECT u.full_name 
                            FROM application_reviewers ar
                            JOIN users u ON ar.reviewer_id = u.id
                            WHERE ar.application_id = {$app['id']}
                        ")->fetch_all(MYSQLI_ASSOC);
                        $reviewer_names = array_column($reviewers, 'full_name');
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($app['cap_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($app['applicant_name']); ?></td>
                            <td><?php echo htmlspecialchars($app['loan_type']); ?></td>
                            <td><?php echo formatCurrency($app['proposed_limit']); ?></td>
                            <td><?php echo htmlspecialchars(implode(', ', $reviewer_names)); ?></td>
                            <td><?php echo date('d M Y', strtotime($app['created_at'])); ?></td>
                            <td>
                                <a href="approve_application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-check"></i> Process
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