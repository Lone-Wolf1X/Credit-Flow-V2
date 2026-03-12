<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

requireLogin();

$current_user = getCurrentUser();
// Ensure only Reviewer or Admin can access
if ($current_user['role'] !== 'Reviewer' && $current_user['role'] !== 'Admin') {
    die("Access Denied");
}

/*
// Simple Filter Schema
$status_filter = $_GET['status'] ?? 'Pending Legal';
*/

// Get Profiles based on status
// We fetch 'Pending Legal' (Inbox) and 'Picked' (My/All Tasks)
$stmt = $conn->prepare("
    SELECT c.*, u.full_name as created_by_name 
    FROM customers c 
    LEFT JOIN " . CF_DB_NAME . ".users u ON c.created_by = u.id 
    WHERE c.status IN ('Pending Legal', 'Picked', 'Approved', 'Returned') 
    ORDER BY c.id DESC
");
$stmt->execute();
$profiles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="fas fa-gavel"></i> Legal Dashboard</h2>
        <p class="text-muted">Manage and review customer profiles submitted for legal vetting.</p>
    </div>
</div>

<div class="table-card">
    <ul class="nav nav-tabs mb-3" id="dashboardTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button">
                <i class="fas fa-inbox text-warning"></i> Pending Review
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="processed-tab" data-bs-toggle="tab" data-bs-target="#processed" type="button">
                <i class="fas fa-history text-muted"></i> Processed (Approved/Returned)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="dashboardTabsContent">
        <!-- Pending / Picked -->
        <div class="tab-pane fade show active" id="pending" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th>Client ID</th>
                            <th>Customer Name</th>
                            <th>Status</th>
                            <th>Reviewer</th> <!-- If Picked -->
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $has_pending = false;
                        foreach ($profiles as $profile): 
                            if (!in_array($profile['status'], ['Pending Legal', 'Picked'])) continue;
                            $has_pending = true;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($profile['client_id']); ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($profile['customer_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($profile['contact_number']); ?></small>
                                </td>
                                <td>
                                    <span class="<?php echo getStatusBadge($profile['status']); ?>">
                                        <?php echo $profile['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $profile['status'] === 'Picked' ? '<span class="badge bg-info">Assigned</span>' : '<span class="text-muted">-</span>'; ?>
                                </td>
                                <td>
                                    <a href="../customer/customer_profile.php?id=<?php echo $profile['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-search"></i> Review
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (!$has_pending): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                                    No pending profiles for review.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Processed -->
        <div class="tab-pane fade" id="processed" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th>Client ID</th>
                            <th>Customer Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $has_processed = false;
                        foreach ($profiles as $profile): 
                            if (!in_array($profile['status'], ['Approved', 'Returned'])) continue;
                            $has_processed = true;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($profile['client_id']); ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($profile['customer_name']); ?></div>
                                </td>
                                <td>
                                    <span class="<?php echo getStatusBadge($profile['status']); ?>">
                                        <?php echo $profile['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="../customer/customer_profile.php?id=<?php echo $profile['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$has_processed): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No processed profiles found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>