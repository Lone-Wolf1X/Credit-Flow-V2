<?php
session_start();
require_once '../../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit;
}

// Page variables for layout
$pageTitle = 'Customer Application Hub';
$activeMenu = 'create_customer';
$userName = $_SESSION['full_name'] ?? 'User';
$userAvatar = '../../asstes/images/img_avatar.png';
$badgeText = $_SESSION['role_name'] ?? 'Maker';

// Start output buffering for main content
ob_start();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold text-dark mb-1">
                    <i class="bi bi-people-fill me-2"></i>Customer Application Hub
                </h2>
                <p class="text-muted mb-0">Manage existing profiles or create new customers.</p>
            </div>
            <button class="btn btn-success btn-lg" type="button" data-bs-toggle="modal" data-bs-target="#createCustomerModal">
                <i class="bi bi-person-plus-fill me-2"></i>Create New Customer
            </button>
        </div>
    </div>

    <!-- Hub Content -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 text-primary"><i class="bi bi-database me-2"></i>Existing Customer Database</h5>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0 bg-light" id="customerSearch" 
                               placeholder="Search by Name, ID, or Contact...">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="customerTable">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Customer</th>
                            <th>Contact</th>
                            <th>Latest Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="customerTableBody">
                        <tr><td colspan="4" class="text-center py-5 text-muted">Loading customers...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Customer Modal -->
<div class="modal fade" id="createCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
             <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>New Customer Registration</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="createCustomerForm">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="customer_type" class="form-label fw-semibold">Customer Type / ग्राहक प्रकार <span class="text-danger">*</span></label>
                            <select class="form-select" id="customer_type" name="customer_type" required>
                                <option value="">Select Type</option>
                                <option value="Individual">Individual / व्यक्तिगत</option>
                                <option value="Corporate">Corporate / संस्थागत</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="full_name" class="form-label fw-semibold">Full Name / पूरा नाम <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Email / इमेल</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="col-md-6">
                            <label for="province" class="form-label fw-semibold">Province / प्रदेश <span class="text-danger">*</span></label>
                            <select class="form-select" id="province" name="province" required>
                                <option value="">Select Province</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="sol" class="form-label fw-semibold">Branch (SOL) / शाखा <span class="text-danger">*</span></label>
                            <select class="form-select" id="sol" name="sol" required>
                                <option value="">Select Branch</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="contact" class="form-label fw-semibold">Contact / सम्पर्क <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="contact" name="contact" required>
                        </div>
                        <div class="col-12 mt-4 text-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle me-1"></i> Register Customer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- HIDDEN: Inputs for Clone Logic -->
<input type="hidden" id="selectedProfileId">

<!-- Application Type Modal (Simplified for Confirmation) -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="confirmationTitle">Confirm Action</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p id="confirmationMessage" class="fs-5 text-center mb-4"></p>
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary px-4" id="confirmActionBtn">Proceed</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let searchTimeout;

document.addEventListener('DOMContentLoaded', () => {
    loadCustomers();
    loadDropdowns();
});

function loadDropdowns() {
    // Load Provinces
    fetch('../api/customer_api.php?action=get_provinces')
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                const select = document.getElementById('province');
                select.innerHTML = '<option value="">Select Province</option>';
                data.data.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.innerText = p.name;
                    select.appendChild(opt);
                });
            }
        });

    // Load All Branches
    loadBranches();
}

function loadBranches() {
    const select = document.getElementById('sol');
    select.innerHTML = '<option value="">Select Branch / SOL</option>';
    
    fetch('../api/customer_api.php?action=get_branches')
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                data.data.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.sol_id;
                    opt.innerText = `${b.sol_id} - ${b.sol_name}`;
                    select.appendChild(opt);
                });
            }
        });
}

document.getElementById('customerSearch').addEventListener('keyup', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadCustomers(this.value);
    }, 400);
});

function loadCustomers(query = '') {
    const tbody = document.getElementById('customerTableBody');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-muted"><div class="spinner-border text-primary spinner-border-sm me-2"></div>Loading...</td></tr>';

    fetch(`../api/customer_api.php?action=search_profiles&query=${encodeURIComponent(query)}`)
    .then(response => response.json())
    .then(data => {
        if (!data.success || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-muted">No customers found. Create a new one.</td></tr>';
            return;
        }
        
        let html = '';
        data.data.forEach(cust => {
            const statusBadge = getStatusBadge(cust.latest_status);
            const typeBadge = cust.customer_type === 'Corporate' ? 
                '<span class="badge bg-dark ms-2">Corp</span>' : 
                '<span class="badge bg-info text-dark ms-2">Indv</span>';

            html += `
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-dark">${cust.full_name} ${typeBadge}</div>
                        <small class="text-muted">ID: ${cust.customer_id || 'N/A'}</small>
                    </td>
                    <td>
                        <div><i class="bi bi-telephone me-2 text-muted"></i>${cust.contact}</div>
                        <small class="text-muted">${cust.email || ''}</small>
                    </td>
                    <td>${statusBadge}</td>
                    <td class="text-end pe-4">
                        <div class="dropdown">
                            <button class="btn btn-light border btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><h6 class="dropdown-header">New Application</h6></li>
                                <li>
                                    <button class="dropdown-item text-primary" onclick="confirmClone(${cust.id}, '${cust.full_name}', 'Renewal')">
                                        <i class="bi bi-arrow-repeat me-2"></i>Renewal
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item text-success" onclick="confirmClone(${cust.id}, '${cust.full_name}', 'Enhancement')">
                                        <i class="bi bi-graph-up-arrow me-2"></i>Enhancement
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item text-warning" onclick="confirmClone(${cust.id}, '${cust.full_name}', 'Reduction')">
                                        <i class="bi bi-graph-down-arrow me-2"></i>Reduction
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Manage</h6></li>
                                <li>
                                    <a class="dropdown-item" href="customer_profile.php?id=${cust.id}">
                                        <i class="bi bi-eye me-2"></i>View Profile
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    });
}

function getStatusBadge(status) {
    if (!status) return '<span class="badge bg-secondary">Unknown</span>';
    const map = {
        'Draft': 'bg-secondary',
        'Submitted': 'bg-warning text-dark',
        'Approved': 'bg-success',
        'Rejected': 'bg-danger',
        'Returned': 'bg-info text-dark'
    };
    return `<span class="badge ${map[status] || 'bg-light text-dark'}">${status}</span>`;
}

// Clone Logic
let selectedCloneType = '';
let cloneModal;

function confirmClone(profileId, name, type) {
    document.getElementById('selectedProfileId').value = profileId;
    selectedCloneType = type;
    
    document.getElementById('confirmationTitle').innerText = `${type} Application`;
    document.getElementById('confirmationMessage').innerHTML = `Start a <strong>${type}</strong> application for <strong>${name}</strong>?`;
    
    if (!cloneModal) {
        cloneModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    }
    cloneModal.show();
}

document.getElementById('confirmActionBtn').addEventListener('click', function() {
    const profileId = document.getElementById('selectedProfileId').value;
    const type = selectedCloneType;
    
    const btn = this;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Processing...';

    const formData = new FormData();
    formData.append('action', 'clone_profile');
    formData.append('profile_id', profileId);
    formData.append('application_type', type);
    
    fetch('../api/customer_api.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = `customer_profile.php?id=${data.new_profile_id}`;
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(err => {
        alert('System error');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});

</script>

<script>
// Create Customer Logic
document.getElementById('createCustomerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'create_customer');
    
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Creating...';
    
    fetch('../api/customer_api.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            window.location.href = 'customer_profile.php?id=' + d.profile_id;
        } else {
            alert(d.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(err => {
        alert('System error: ' + err);
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});
</script>

<style>
.hover-shadow:hover {
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    transform: translateY(-2px);
    transition: all 0.2s ease;
    cursor: pointer;
}

</style>

<script>
// Fix for Modal Stacking/Backdrop Issues
document.addEventListener('DOMContentLoaded', () => {
    // Move modals to body to correct z-index/backdrop issues
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        document.body.appendChild(modal);
    });
});
</script>

<style>
/* Additional z-index safety */
.modal-backdrop {
    z-index: 1050;
}
.modal {
    z-index: 1055;
}
</style>

<?php
$mainContent = ob_get_clean();
include '../../Layout/layout_new.php';
?>
