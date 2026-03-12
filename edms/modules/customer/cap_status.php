<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

requireLogin();

// Get filter status
$status_filter = $_GET['status'] ?? 'All';
$statuses = ['All', 'Pending Legal', 'Approved', 'Returned'];

// Build Query
$where_sql = "1=1";
if ($status_filter !== 'All') {
    $where_sql .= " AND cd.status = '$status_filter'";
}

// Fetch all CAPs related to the current user (if we want to restrict) or All CAPs if admin?
// Usually Makers want to see CAPs *they* submitted or CAPs for customers *they* manage.
// For now, let's fetch ALL CAPs handled by the system for visibility, or filter by user if specific role.
// Assuming "Maker" wants to track "My Applications".
// But EDMS users might be centralized. I'll show ALL with sorting by date.

$query = "
    SELECT cd.*, 
           c.customer_name, 
           c.contact_number,
           u_sub.full_name as submitted_by_name,
           u_rev.full_name as reviewed_by_name
    FROM cap_documents cd
    LEFT JOIN customers c ON cd.customer_id = c.id
    LEFT JOIN " . CF_DB_NAME . ".users u_sub ON cd.submitted_by = u_sub.id
    LEFT JOIN " . CF_DB_NAME . ".users u_rev ON cd.reviewed_by = u_rev.id
    WHERE $where_sql
    ORDER BY cd.submitted_at DESC
";

$caps = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

include '../../includes/header.php';
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
        <div class="d-flex align-items-center">
            <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 me-3">
                <i class="fas fa-check"></i>
            </div>
            <div>
                <h6 class="mb-0 text-success fw-bold">Success</h6>
                <div class="small text-muted"><?php echo htmlspecialchars($_GET['success']); ?></div>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold text-dark"><i class="fas fa-tasks text-primary me-2"></i>CAP Status Tracking</h3>
        <p class="text-muted mb-0">Monitor the legal vetting status of all Capital Application Proposals.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <div class="btn-group shadow-sm">
            <?php foreach ($statuses as $status): ?>
                <a href="?status=<?php echo $status; ?>"
                    class="btn btn-white border px-4 <?php echo $status_filter === $status ? 'active bg-primary text-white border-primary' : 'text-muted'; ?>">
                    <?php echo $status; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-uppercase small text-muted">
                <tr>
                    <th class="ps-4">CAP ID</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Submitted By</th>
                    <th>Reviewer</th>
                    <th>Timeline</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($caps)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-folder-open fa-3x mb-3 text-light"></i>
                                <p class="mb-0">No CAP documents found for this criteria.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($caps as $cap): ?>
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-dark border rounded px-2 py-1 bg-light">
                                    <?php echo htmlspecialchars($cap['cap_id']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar small me-2" style="background-color: <?php echo getAvatarColor(null, $cap['customer_name']); ?>">
                                        <?php echo getUserInitials($cap['customer_name']); ?>
                                    </div>
                                    <div>
                                        <a href="customer_profile.php?id=<?php echo $cap['customer_id']; ?>" class="text-dark fw-bold text-decoration-none">
                                            <?php echo htmlspecialchars($cap['customer_name']); ?>
                                        </a>
                                        <div class="small text-muted"><?php echo htmlspecialchars($cap['contact_number']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="<?php echo getStatusBadge($cap['status']); ?> rounded-pill px-3">
                                    <?php echo $cap['status']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="small">
                                    <i class="fas fa-user-circle text-muted me-1"></i>
                                    <?php echo htmlspecialchars($cap['submitted_by_name']); ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($cap['reviewed_by_name']): ?>
                                    <div class="small text-success">
                                        <i class="fas fa-user-check me-1"></i>
                                        <?php echo htmlspecialchars($cap['reviewed_by_name']); ?>
                                    </div>
                                <?php elseif ($cap['status'] === 'Pending Legal'): ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Pending Review</span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small text-muted">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="fas fa-clock w-20px text-center me-1"></i>
                                        <?php echo date('M d, Y', strtotime($cap['submitted_at'])); ?>
                                    </div>
                                    <?php if ($cap['reviewed_at']): ?>
                                        <div class="d-flex align-items-center text-success">
                                            <i class="fas fa-check-double w-20px text-center me-1"></i>
                                            <?php echo date('M d, Y', strtotime($cap['reviewed_at'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <a href="customer_profile.php?id=<?php echo $cap['customer_id']; ?>"
                                    class="btn btn-sm btn-outline-primary">
                                    View Details <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>