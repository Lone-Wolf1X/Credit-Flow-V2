<?php
session_start();
require_once '../../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit;
}

// Page variables for layout
$pageTitle = 'Profile History Hub';
$activeMenu = 'profile_history';
$userName = $_SESSION['full_name'] ?? 'User';
$userAvatar = '../../asstes/images/img_avatar.png';
$badgeText = $_SESSION['role_name'] ?? 'Maker';

// Fetch all customer profiles for history tracking
$query = "SELECT cp.*, u.full_name as created_by_name 
          FROM customer_profiles cp 
          LEFT JOIN users u ON cp.created_by = u.id 
          ORDER BY cp.created_at DESC";
$result = $conn->query($query);

ob_start();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-clock-history me-2"></i>Profile History Hub
            </h2>
            <p class="text-muted">Audit trail and document generation history for all customers</p>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Search Customer</label>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search by name, ID, contact...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Customer Type</label>
                            <select class="form-select" id="filterType">
                                <option value="">All Types</option>
                                <option value="Individual">Individual</option>
                                <option value="Corporate">Corporate</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">&nbsp;</label>
                            <button class="btn btn-primary w-100" onclick="applyFilters()">
                                <i class="bi bi-funnel me-2"></i>Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer List -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0">Customer Audit Registry</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="customerTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Customer ID</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Created By</th>
                                    <th>Base Status</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($customer = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($customer['customer_id']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $customer['customer_type'] == 'Individual' ? 'info' : 'warning'; ?>">
                                                    <?php echo htmlspecialchars($customer['customer_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($customer['created_by_name']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($customer['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($customer['updated_at'] ?? $customer['created_at'])); ?></td>
                                            <td>
                                                <a href="profile_history.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-dark">
                                                    <i class="bi bi-clock-history me-1"></i> View Audit Trail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="bi bi-inbox display-4 text-muted d-block mb-3"></i>
                                            <p class="text-muted">No customers found.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function applyFilters() {
    const type = document.getElementById('filterType').value;
    const search = document.getElementById('searchInput').value.toLowerCase();
    
    const table = document.getElementById('customerTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let row of rows) {
        const cells = row.getElementsByTagName('td');
        if (cells.length === 0) continue;
        
        const customerType = cells[2].textContent.trim();
        const rowText = row.textContent.toLowerCase();
        
        let showRow = true;
        
        // Filter by type
        if (type && !customerType.includes(type)) {
            showRow = false;
        }
        
        // Filter by search
        if (search && !rowText.includes(search)) {
            showRow = false;
        }
        
        row.style.display = showRow ? '' : 'none';
    }
}

// Real-time search
document.getElementById('searchInput').addEventListener('keyup', applyFilters);
</script>

<?php
$mainContent = ob_get_clean();
include '../../Layout/layout_new.php';
?>
