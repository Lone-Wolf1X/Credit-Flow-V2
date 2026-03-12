<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

requireLogin();

$customers = $conn->query("SELECT c.*, u.full_name as created_by_name FROM customers c LEFT JOIN " . CF_DB_NAME . ".users u ON c.created_by = u.id ORDER BY c.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Calculate dynamic status for each customer based on their CAPs
foreach ($customers as &$cust) {
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

include '../../includes/header.php';
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-users text-primary me-2"></i>All Customers</h5>
        <a href="create_customer.php" class="btn btn-primary btn-sm rounded-pill px-3">
            <i class="fas fa-plus me-1"></i> New Customer
        </a>
    </div>

    <?php if (empty($customers)): ?>
        <div class="p-5 text-center text-muted">
            <div class="mb-3">
                <i class="fas fa-folder-open fa-3x text-light"></i>
            </div>
            <h5>No customers found</h5>
            <p class="small text-muted mb-4">Get started by creating your first customer profile.</p>
            <a href="create_customer.php" class="btn btn-primary">Create Customer</a>
        </div>
    <?php else: ?>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Customer Name</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar small me-3" style="background-color: <?php echo getAvatarColor(null, $customer['customer_name']); ?>">
                                            <?php echo getUserInitials($customer['customer_name']); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($customer['customer_name']); ?></div>
                                            <small class="text-muted">ID: <?php echo $customer['id']; ?> <?php echo isset($customer['client_id']) ? '• ' . htmlspecialchars($customer['client_id']) : ''; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($customer['contact_number']); ?></td>
                                <td><span class="text-truncate d-inline-block" style="max-width: 150px;" title="<?php echo htmlspecialchars($customer['address']); ?>"><?php echo htmlspecialchars($customer['address']); ?></span></td>
                                <td><?php echo htmlspecialchars($customer['created_by_name']); ?></td>
                                <td><span class="<?php echo getStatusBadge($customer['status']); ?> rounded-pill px-3 py-1 small fw-bold"><?php echo $customer['status']; ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                                <td class="text-end pe-4">
                                    <a href="customer_profile.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                        Open <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>