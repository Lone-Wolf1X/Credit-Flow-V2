<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

requireLogin();

// Get dashboard statistics
$total_customers = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'];
$pending_legal = $conn->query("SELECT COUNT(*) as count FROM cap_documents WHERE status = 'Pending Legal'")->fetch_assoc()['count'];
$approved_customers = $conn->query("SELECT COUNT(*) as count FROM cap_documents WHERE status = 'Approved'")->fetch_assoc()['count'];
$returned_cases = $conn->query("SELECT COUNT(*) as count FROM cap_documents WHERE status = 'Returned'")->fetch_assoc()['count'];

// Get recent customers
$recent_customers = $conn->query("SELECT c.*, u.full_name as created_by_name FROM customers c LEFT JOIN " . CF_DB_NAME . ".users u ON c.created_by = u.id ORDER BY c.created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Calculate dynamic status for each customer based on their CAPs
foreach ($recent_customers as &$cust) {
    $cap_res = $conn->query("SELECT status FROM cap_documents WHERE customer_id = " . $cust['id']);
    $stats = [];
    while ($row = $cap_res->fetch_assoc()) {
        $stats[] = $row['status'];
    }
    
    if (empty($stats)) {
        $cust['status'] = 'Draft';
    } elseif (in_array('Returned', $stats)) {
        $cust['status'] = 'Returned';
    } elseif (in_array('Pending Legal', $stats)) {
        $cust['status'] = 'Pending Legal';
    } elseif (in_array('Draft', $stats) || in_array('', $stats)) {
        $cust['status'] = 'Draft';
    } else {
        // If all are likely approved (or some unknown status, default to Approved if not caught above)
        $all_approved = true;
        foreach ($stats as $s) {
            if ($s != 'Approved') {
                $all_approved = false; 
                break;
            }
        }
        $cust['status'] = $all_approved ? 'Approved' : 'Pending';
    }
}
unset($cust); // Break reference

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-light border shadow-sm d-flex align-items-center" role="alert">
            <div class="me-3">
                <div class="user-avatar large" style="background-color: <?php echo getAvatarColor($current_user['role'], $current_user['full_name']); ?>">
                    <?php echo getUserInitials($current_user['full_name']); ?>
                </div>
            </div>
            <div>
                <h4 class="alert-heading mb-1">Welcome back, <?php echo htmlspecialchars($current_user['full_name']); ?>!</h4>
                <p class="mb-0 text-muted">Here's what's happening in the EDMS today.</p>
            </div>
            <div class="ms-auto">
                <a href="modules/customer/create_customer.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Create Customer
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4 g-4">
    <!-- Total Customers Card -->
    <div class="col-md-3">
        <div class="premium-stat-card gradient-primary">
            <div class="stat-card-icon">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-card-content">
                <p class="stat-label">Total Customers</p>
                <h2 class="stat-value"><?php echo $total_customers; ?></h2>
                <div class="stat-footer">
                    <i class="fas fa-chart-line me-1"></i>
                    <span>Active accounts</span>
                </div>
            </div>
            <div class="stat-card-glow"></div>
        </div>
    </div>

    <!-- Pending Legal Card -->
    <div class="col-md-3">
        <div class="premium-stat-card gradient-warning">
            <div class="stat-card-icon">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="stat-card-content">
                <p class="stat-label">Pending Legal</p>
                <h2 class="stat-value"><?php echo $pending_legal; ?></h2>
                <div class="stat-footer">
                    <i class="fas fa-clock me-1"></i>
                    <span>Awaiting review</span>
                </div>
            </div>
            <div class="stat-card-glow"></div>
        </div>
    </div>

    <!-- Approved Card -->
    <div class="col-md-3">
        <div class="premium-stat-card gradient-success">
            <div class="stat-card-icon">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="stat-card-content">
                <p class="stat-label">Approved</p>
                <h2 class="stat-value"><?php echo $approved_customers; ?></h2>
                <div class="stat-footer">
                    <i class="fas fa-shield-check me-1"></i>
                    <span>Verified docs</span>
                </div>
            </div>
            <div class="stat-card-glow"></div>
        </div>
    </div>

    <!-- Returned Card -->
    <div class="col-md-3">
        <div class="premium-stat-card gradient-danger">
            <div class="stat-card-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-card-content">
                <p class="stat-label">Returned</p>
                <h2 class="stat-value"><?php echo $returned_cases; ?></h2>
                <div class="stat-footer">
                    <i class="fas fa-redo me-1"></i>
                    <span>Needs attention</span>
                </div>
            </div>
            <div class="stat-card-glow"></div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-clock text-primary me-2"></i>Recent Customers</h5>
            <a href="modules/customer/customer_list.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recent_customers)): ?>
            <div class="p-4 text-center text-muted">
                <i class="fas fa-folder-open fa-3x mb-3 text-light"></i>
                <p>No customers found yet.</p>
                <a href="modules/customer/create_customer.php" class="btn btn-sm btn-primary">Create Customer</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Customer Name</th>
                            <th>Contact</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_customers as $customer): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                       <div class="user-avatar small me-2" style="background-color: <?php echo getAvatarColor(null, $customer['customer_name']); ?>">
                                            <?php echo getUserInitials($customer['customer_name']); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($customer['customer_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($customer['client_id'] ?? '-'); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($customer['contact_number']); ?></td>
                                <td><?php echo htmlspecialchars($customer['created_by_name']); ?></td>
                                <td><span class="<?php echo getStatusBadge($customer['status']); ?> rounded-pill px-3"><?php echo $customer['status']; ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                                <td class="text-end pe-4">
                                    <a href="modules/customer/customer_profile.php?id=<?php echo $customer['id']; ?>"
                                        class="btn btn-sm btn-outline-primary">
                                        Open <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>