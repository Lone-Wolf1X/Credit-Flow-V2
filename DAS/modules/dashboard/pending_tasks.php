<?php
session_start();
require_once '../../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit;
}

$role = $_SESSION['role_name'] ?? 'Guest';
$pageTitle = 'Pending Tasks';
$activeMenu = 'pending_tasks';
$userName = $_SESSION['full_name'] ?? 'User';

// Logic to fetch tasks based on role
$tasks = [];
$taskType = ''; // 'Review' or 'Edit'

if ($role === 'Checker') {
    // Checker sees Submitted profiles
    $taskType = 'Review';
    $stmt = $conn->query("
        SELECT cp.id, cp.customer_id, cp.full_name, cp.submitted_at as date, u.full_name as submitter, cp.status
        FROM customer_profiles cp
        LEFT JOIN users u ON cp.created_by = u.id
        WHERE cp.status = 'Submitted'
        ORDER BY cp.submitted_at DESC
    ");
} elseif ($role === 'Maker') {
    // Maker sees Drafts and Rejected profiles
    $taskType = 'Edit';
    $stmt = $conn->prepare("
        SELECT cp.id, cp.customer_id, cp.full_name, cp.updated_at as date, 'You' as submitter, cp.status
        FROM customer_profiles cp
        WHERE cp.created_by = ? AND cp.status IN ('Draft', 'Rejected')
        ORDER BY cp.updated_at DESC
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt = $stmt->get_result();
}

if ($stmt) {
    while ($row = $stmt->fetch_assoc()) {
        $tasks[] = $row;
    }
}

ob_start();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-list-check me-2"></i>Pending Tasks
            </h2>
            <p class="text-muted">
                <?php if ($role === 'Checker'): ?>
                    Profiles waiting for your approval.
                <?php else: ?>
                    Profiles requiring your attention (Drafts or Returned).
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if (empty($tasks)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle fs-1 text-success mb-3 d-block"></i>
                            <p class="text-muted mb-0">All caught up!</p>
                            <small class="text-muted">No pending tasks found.</small>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Customer ID</th>
                                        <th>Full Name</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <?php if ($role === 'Checker'): ?><th>Submitted By</th><?php endif; ?>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasks as $task): ?>
                                        <tr>
                                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($task['customer_id']); ?></span></td>
                                            <td><?php echo htmlspecialchars($task['full_name']); ?></td>
                                            <td>
                                                <?php 
                                                $statusClass = match($task['status']) {
                                                    'Draft' => 'warning',
                                                    'Rejected' => 'danger',
                                                    'Submitted' => 'info',
                                                    default => 'secondary'
                                                };
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($task['status']); ?></span>
                                            </td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($task['date'])); ?></td>
                                            <?php if ($role === 'Checker'): ?>
                                                <td><?php echo htmlspecialchars($task['submitter']); ?></td>
                                            <?php endif; ?>
                                            <td class="text-end">
                                                <?php if ($role === 'Checker'): ?>
                                                    <button onclick="viewProfile(<?php echo $task['id']; ?>)" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-eye me-1"></i>Review
                                                    </button>
                                                <?php else: ?>
                                                    <a href="../customer/customer_profile.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil me-1"></i>Edit
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Checker-Specific Modals & Scripts -->
<?php if ($role === 'Checker'): ?>
    <!-- View Profile Modal -->
    <div class="modal fade" id="viewProfileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-person-lines-fill me-2"></i>Profile Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="profileModalBody">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-danger me-auto" onclick="rejectProfile()">
                        <i class="bi bi-x-circle me-2"></i>Reject
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" onclick="approveProfile()">
                        <i class="bi bi-check-circle me-2"></i>Approve & Generate Docs
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-check-circle me-2"></i>Approve Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <i class="bi bi-info-circle me-2"></i>Approving will automatically generate all legal documents.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Approval Remarks (Optional)</label>
                        <textarea class="form-control" id="approveRemarks" rows="3" placeholder="Enter remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="btnConfirmApprove" onclick="confirmApprove()">
                        <i class="bi bi-check-lg me-1"></i>Approve & Generate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-x-circle me-2"></i>Reject Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectReason" rows="3" placeholder="Please explain why this profile is being rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmReject" onclick="confirmReject()">
                        <i class="bi bi-x-circle me-1"></i>Confirm Reject
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    let currentProfileId = null;

    function viewProfile(profileId) {
        currentProfileId = profileId;
        const modal = new bootstrap.Modal(document.getElementById('viewProfileModal'));
        modal.show();
        
        // Load profile data
        const modalBody = document.getElementById('profileModalBody');
        modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">Loading details...</p></div>';
        
        const formData = new FormData();
        formData.append('action', 'get_profile');
        formData.append('profile_id', profileId);
        
        fetch('../api/customer_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                renderProfileDetails(data.data);
            } else {
                modalBody.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(error => {
            modalBody.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        });
    }

    function renderProfileDetails(profile) {
        let html = `
            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="text-primary fw-bold mb-3">Personal Information</h6>
                    <table class="table table-sm table-borderless">
                        <tr><td class="text-muted" width="40%">Full Name:</td><td class="fw-medium">${profile.full_name}</td></tr>
                        <tr><td class="text-muted">Customer ID:</td><td class="fw-medium">${profile.customer_id}</td></tr>
                        <tr><td class="text-muted">Citizenship:</td><td class="fw-medium">${profile.citizenship_number || '-'}</td></tr>
                        <tr><td class="text-muted">Mobile:</td><td class="fw-medium">${profile.mobile_number || '-'}</td></tr>
                        <tr><td class="text-muted">Address:</td><td class="fw-medium">${profile.perm_municipality_vdc || ''}, ${profile.perm_district || ''}</td></tr>
                    </table>
                </div>
                
                <div class="col-md-6">
                    <h6 class="text-primary fw-bold mb-3">Loan Information</h6>
                    <div id="loanDetailsContainer">
                        <p class="text-muted"><small>Fetching loan details...</small></p>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('profileModalBody').innerHTML = html;
        fetchLoanDetails(profile.id);
    }

    function fetchLoanDetails(profileId) {
        const formData = new FormData();
        formData.append('action', 'get_loans');
        formData.append('profile_id', profileId);
        
        fetch('../api/customer_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('loanDetailsContainer');
            if (data.success && data.data.length > 0) {
                const loan = data.data[0];
                container.innerHTML = `
                    <table class="table table-sm table-borderless">
                        <tr><td class="text-muted" width="40%">Loan Request:</td><td class="fw-bold text-success">Rs. ${parseFloat(loan.sanctioned_amount).toLocaleString()}</td></tr>
                        <tr><td class="text-muted">Scheme:</td><td class="fw-medium">${loan.scheme_name || 'N/A'}</td></tr>
                        <tr><td class="text-muted">Interest Rate:</td><td class="fw-medium">${loan.interest_rate}%</td></tr>
                        <tr><td class="text-muted">Tenure:</td><td class="fw-medium">${loan.tenure_months} Months</td></tr>
                    </table>
                `;
            } else {
                container.innerHTML = '<p class="text-muted small">No loan details found.</p>';
            }
        });
    }

    function approveProfile() {
        // Hide view modal first to avoid overlay issues, or stack them. Bootstrap handles stacking if configured right.
        // But for safety, let's keep view modal open (stacking).
        new bootstrap.Modal(document.getElementById('approveModal')).show();
    }

    function confirmApprove() {
        const remarks = document.getElementById('approveRemarks').value;
        const btn = document.getElementById('btnConfirmApprove');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        
        const formData = new FormData();
        formData.append('action', 'approve_profile');
        formData.append('profile_id', currentProfileId);
        formData.append('remarks', remarks);
        
        fetch('../api/customer_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            alert('❌ Network Error: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }

    function rejectProfile() {
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    }

    function confirmReject() {
        const reason = document.getElementById('rejectReason').value;
        if (!reason.trim()) {
            alert("Rejection reason is required");
            return;
        }

        const btn = document.getElementById('btnConfirmReject');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Rejecting...';

        const formData = new FormData();
        formData.append('action', 'reject_profile');
        formData.append('profile_id', currentProfileId);
        formData.append('rejection_reason', reason);
        
        fetch('../api/customer_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Profile rejected.');
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            alert('❌ Network Error: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    </script>
<?php endif; ?>

<?php
$mainContent = ob_get_clean();
include '../../Layout/layout_new.php';
?>
