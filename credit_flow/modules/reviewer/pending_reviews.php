<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

requireLogin();
requireLogin();

$stmt = $conn->prepare("
    SELECT la.* 
    FROM loan_applications la 
    JOIN application_reviewers ar ON la.id = ar.application_id 
    WHERE ar.reviewer_id = ? AND la.current_stage = 'Reviewer' AND ar.status = 'Pending'
    ORDER BY la.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include '../../includes/header.php';
?>

<div class="table-card">
    <h4 class="mb-3"><i class="fas fa-tasks"></i> Pending Reviews</h4>

    <?php if (empty($applications)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No applications pending for your review.
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
                        <th>Initiator</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <?php
                        $initiator = $conn->query("SELECT full_name FROM users WHERE id = {$app['initiator_id']}")->fetch_assoc();
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($app['cap_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($app['applicant_name']); ?></td>
                            <td><?php echo htmlspecialchars($app['loan_type']); ?></td>
                            <td><?php echo formatCurrency($app['proposed_limit']); ?></td>
                            <td><?php echo htmlspecialchars($initiator['full_name']); ?></td>
                            <td><?php echo date('d M Y', strtotime($app['created_at'])); ?></td>
                            <td>
                                <a href="review_application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Review
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