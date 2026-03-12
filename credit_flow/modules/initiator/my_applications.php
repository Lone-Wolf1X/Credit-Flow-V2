<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

requireLogin();
requireLogin();

$stmt = $conn->prepare("SELECT * FROM loan_applications WHERE initiator_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include '../../includes/header.php';
?>

<div class="table-card">
    <h4 class="mb-3"><i class="fas fa-list"></i> My Applications</h4>

    <?php if (empty($applications)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> You haven't initiated any applications yet.
            <a href="initiate_loan.php" class="alert-link">Initiate your first loan application</a>
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
                        <th>Status</th>
                        <th>Current Stage</th>
                        <th>Date</th>
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
                            <td><span class="<?php echo getStatusBadge($app['status']); ?>"><?php echo $app['status']; ?></span>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo $app['current_stage']; ?></span></td>
                            <td><?php echo date('d M Y', strtotime($app['created_at'])); ?></td>
                            <td>
                                <a href="../../view_application.php?id=<?php echo $app['id']; ?>"
                                    class="btn btn-sm btn-primary">
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