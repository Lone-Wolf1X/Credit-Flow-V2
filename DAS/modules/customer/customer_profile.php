<?php
session_start();
require_once '../../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit;
}

// Get profile ID from URL
$profile_id = $_GET['id'] ?? '';

if (empty($profile_id)) {
    header('Location: create_customer.php');
    exit;
}

// Fetch customer profile
$stmt = $conn->prepare("SELECT cp.*, u.full_name as created_by_name FROM customer_profiles cp LEFT JOIN users u ON cp.created_by = u.id WHERE cp.id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

if (!$profile) {
    header('Location: create_customer.php');
    exit;
}

// Page variables for layout
$pageTitle = 'Customer Profile - ' . $profile['customer_id'];
$activeMenu = 'customer_profile';
$userName = $_SESSION['full_name'] ?? 'User';
$userAvatar = '../../asstes/images/img_avatar.png';
$badgeText = $_SESSION['role_name'] ?? 'Maker';

// Check if user can edit (only Draft or Returned status, and only Maker role)
$canEdit = ($profile['status'] == 'Draft' || $profile['status'] == 'Returned') && $_SESSION['role_name'] == 'Maker' && ($profile['created_by'] == $_SESSION['user_id']);
$canApprove = $profile['status'] == 'Submitted' && $_SESSION['role_name'] == 'Checker';
$userRole = $_SESSION['role_name'] ?? 'Maker';

// Start output buffering for main content
ob_start();
?>

<div class="container-fluid">
    <!-- Customer Info Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="fw-bold mb-3">
                                <i class="bi bi-person-circle me-2"></i><?php echo htmlspecialchars($profile['full_name']); ?>
                            </h3>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <small class="text-white-50 d-block">Customer ID / ग्राहक आईडी</small>
                                    <strong class="fs-5"><?php echo htmlspecialchars($profile['customer_id']); ?></strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-white-50 d-block">Customer Type / प्रकार</small>
                                    <strong><?php echo htmlspecialchars($profile['customer_type']); ?></strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-white-50 d-block">Email / इमेल</small>
                                    <strong><?php echo htmlspecialchars($profile['email'] ?? 'N/A'); ?></strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-white-50 d-block">Contact / सम्पर्क</small>
                                    <strong><?php echo htmlspecialchars($profile['contact']); ?></strong>
                                </div>
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-3">
                                    <small class="text-white-50 d-block">Created By / सिर्जना गर्ने</small>
                                    <strong><?php echo htmlspecialchars($profile['created_by_name']); ?></strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-white-50 d-block">Province / प्रदेश</small>
                                    <strong><?php echo htmlspecialchars($profile['province'] ?? 'N/A'); ?></strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-white-50 d-block">SOL</small>
                                    <strong><?php echo htmlspecialchars($profile['sol'] ?? 'N/A'); ?></strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-white-50 d-block">Created Date / मिति</small>
                                    <strong><?php echo date('Y-m-d', strtotime($profile['created_at'])); ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php
                            $statusColors = [
                                'Draft' => 'warning',
                                'Submitted' => 'info',
                                'Approved' => 'success',
                                'Rejected' => 'danger'
                            ];
                            $statusColor = $statusColors[$profile['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $statusColor; ?> fs-4 px-4 py-3 mb-3">
                                <?php echo htmlspecialchars($profile['status']); ?>
                            </span>
                            
                            <?php if (!empty($profile['checker_comment']) && $profile['status'] == 'Returned'): ?>
                            <div class="alert alert-warning mt-3">
                                <h6 class="alert-heading"><i class="bi bi-chat-left-text me-2"></i>Checker Comments / टिप्पणी</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($profile['checker_comment'])); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($canEdit): ?>
                            <div class="mt-3">
                                <button class="btn btn-light btn-lg me-2" onclick="submitProfile()">
                                    <i class="bi bi-send me-2"></i>Submit for Approval
                                </button>
                            </div>
                            <?php endif; ?>

                            <?php if ($profile['status'] === 'Approved'): ?>
                            <div class="mt-3">
                                <button class="btn btn-outline-primary" onclick="openRenewalModal('<?php echo $profile['full_name']; ?>')">
                                    <i class="bi bi-arrow-repeat me-2"></i>New Application (Renew/Enhance)
                                </button>
                            </div>
                            <?php endif; ?>

                            <?php if ($canApprove): ?>
                            <div class="mt-3">
                                <button class="btn btn-success btn-lg me-2" onclick="approveProfile()">
                                    <i class="bi bi-check-circle me-2"></i>Approve
                                </button>
                                <button class="btn btn-warning btn-lg me-2" onclick="returnProfile()">
                                    <i class="bi bi-arrow-return-left me-2"></i>Return
                                </button>
                                <button class="btn btn-danger btn-lg" onclick="rejectProfile()">
                                    <i class="bi bi-x-circle me-2"></i>Reject
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Borrower Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-person-badge text-primary me-2"></i>Borrower Section / ऋणी विवरण
                        </h5>
                        <?php if ($canEdit): ?>
                        <button class="btn btn-primary" onclick="addBorrower()">
                            <i class="bi bi-plus-circle me-2"></i>Add Borrower
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div id="borrowerList" class="table-responsive">
                        <p class="text-center text-muted py-4">Loading borrowers...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Guarantor Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-shield-check text-success me-2"></i>Guarantor Section / जमानतकर्ता विवरण
                        </h5>
                        <?php if ($canEdit): ?>
                        <?php if ($profile['customer_type'] === 'Corporate'): ?>
                        <div class="dropdown">
                            <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-plus-circle me-2"></i>Add Guarantor
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); addGuarantor('Individual')"><i class="bi bi-person-circle me-2"></i>Individual Guarantor</a></li>
                                <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); addGuarantor('Corporate')"><i class="bi bi-building me-2"></i>Corporate Guarantor</a></li>
                            </ul>
                        </div>
                        <?php else: ?>
                        <button class="btn btn-success" onclick="addGuarantor('Individual')">
                            <i class="bi bi-plus-circle me-2"></i>Add Guarantor
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div id="guarantorList" class="table-responsive">
                        <p class="text-center text-muted py-4">Loading guarantors...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Collateral Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-building text-warning me-2"></i>Collateral/Security Section / धितो विवरण
                        </h5>
                        <?php if ($canEdit): ?>
                        <div>
                            <button class="btn btn-outline-warning me-2" onclick="openImportCollateralModal()">
                                <i class="bi bi-box-arrow-in-down me-2"></i>Import
                            </button>
                            <button class="btn btn-warning" onclick="addCollateral()">
                                <i class="bi bi-plus-circle me-2"></i>Add Collateral
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div id="collateralList" class="table-responsive">
                        <p class="text-center text-muted py-4">Loading collaterals...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Limit Details Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-cash-stack text-info me-2"></i>Limit Details Section / सीमा विवरण
                        </h5>
                        <?php if ($canEdit): ?>
                        <button class="btn btn-info" onclick="addLimit()">
                            <i class="bi bi-plus-circle me-2"></i>Add Limit
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div id="limitList" class="table-responsive">
                        <p class="text-center text-muted py-4">Loading limits...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loan Details Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-bank text-danger me-2"></i>Loan Details Section / ऋण विवरण
                        </h5>
                        <?php if ($canEdit): ?>
                        <button class="btn btn-danger" onclick="addLoan()">
                            <i class="bi bi-plus-circle me-2"></i>Add Loan
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div id="loanList" class="table-responsive">
                        <p class="text-center text-muted py-4">Loading loans...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Workflow Logs Section -->


    <!-- Documents Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-file-earmark-text text-info me-2"></i>Recent Documents / हालका कागजातहरू
                        </h5>
                        <div>
                            <a href="../history/profile_history.php?id=<?php echo $profile_id; ?>" class="btn btn-outline-info me-2">
                                <i class="bi bi-clock-history me-2"></i>Full History
                            </a>
                            <button class="btn btn-info me-2" onclick="downloadAllDocuments()">
                                <i class="bi bi-download me-2"></i>Download All (Latest)
                            </button>
                            <?php if ($userRole === 'Admin'): ?>
                            <button class="btn btn-success" onclick="uploadDocument()">
                                <i class="bi bi-upload me-2"></i>Upload Document
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="documentList" class="table-responsive">
                        <p class="text-center text-muted py-4">Loading documents...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modals will be loaded dynamically -->
<div id="modalContainer"></div>

<script>
const profileId = <?php echo $profile_id; ?>;
const canEdit = <?php echo $canEdit ? 'true' : 'false'; ?>;
const customerType = '<?php echo $profile['customer_type']; ?>';

// Load all sections on page load
document.addEventListener('DOMContentLoaded', function() {
    loadBorrowers();
    loadGuarantors();
    loadCollaterals();
    loadLimits();
    loadLoans();
    loadDocuments(); // Always load documents if section is visible
    
    // Initialize modals
    const modals = ['submitModal', 'approveModal', 'rejectModal', 'returnModal', 'renewalModal', 'importCollateralModal'];
    modals.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            document.body.appendChild(el);
            el.addEventListener('shown.bs.modal', function () {
                const input = this.querySelector('textarea, input');
                if (input) input.focus();
            });
        }
    });
});

// Load Borrowers
function loadBorrowers() {
    fetch(`../api/customer_api.php?action=get_borrowers&profile_id=${profileId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayBorrowers(data.data);
            } else {
                document.getElementById('borrowerList').innerHTML = '<p class="text-center text-muted py-4">No borrowers found</p>';
            }
        });
}

function displayBorrowers(borrowers) {
    if (borrowers.length === 0) {
        document.getElementById('borrowerList').innerHTML = '<p class="text-center text-muted py-4">No borrowers added yet</p>';
        return;
    }
    
    let html = '<table class="table table-hover"><thead><tr>';
    html += '<th>Full Name / पूरा नाम</th>';
    
    if (borrowers[0].borrower_type === 'Individual') {
        html += '<th>Date of Birth / जन्म मिति</th>';
        html += '<th>Citizenship / नागरिकता</th>';
        html += '<th>Father Name / बुबाको नाम</th>';
        // Family Details removed as per request
    } else {
        html += '<th>Registration No / दर्ता नं</th>';
        html += '<th>PAN Number / प्यान नं</th>';
        html += '<th>Authorized Person / अधिकृत व्यक्ति</th>';
    }
    
    html += '<th>Actions</th></tr></thead><tbody>';
    
    borrowers.forEach(borrower => {
        html += '<tr>';
        html += `<td>${borrower.full_name || borrower.company_name}</td>`;
        
        if (borrower.borrower_type === 'Individual') {
            html += `<td>${borrower.date_of_birth || 'N/A'}</td>`;
            html += `<td>${borrower.citizenship_number || 'N/A'}</td>`;
            html += `<td><span class="father-name-${borrower.id}">${borrower.father_name || 'Loading...'}</span></td>`;
            // Family Details data cell removed
            
            // Fetch family details (still needed for Father Name if not present in main record)
            fetch(`../api/customer_api.php?action=get_family_details&person_id=${borrower.id}&person_type=Borrower`)
                .then(response => response.json())
                .then(data => {
                    let fatherName = 'N/A';
                    
                    if (data.success && data.data && data.data.length > 0) {
                        // Find father
                        const father = data.data.find(m => m.relation === 'बुबा' || m.relation === 'Father' || m.relation === 'Father-in-law' || m.relation === 'ससुरा');
                        if (father) fatherName = father.name;
                    }
                    
                    const elFather = document.querySelector(`.father-name-${borrower.id}`);
                    if(elFather) elFather.innerHTML = fatherName;
                })
                .catch(() => {
                    const elFather = document.querySelector(`.father-name-${borrower.id}`);
                    if(elFather) elFather.innerHTML = 'N/A';
                });
        } else {
            html += `<td>${borrower.registration_no || 'N/A'}</td>`;
            html += `<td>${borrower.pan_number || 'N/A'}</td>`;
            html += `<td>View Details</td>`;
        }
        
        html += '<td>';
        html += `<button class="btn btn-sm btn-info me-1" onclick="viewBorrower(${borrower.id})"><i class="bi bi-eye"></i></button>`;
        if (canEdit) {
            html += `<button class="btn btn-sm btn-warning me-1" onclick="editBorrower(${borrower.id})"><i class="bi bi-pencil"></i></button>`;
            html += `<button class="btn btn-sm btn-danger" onclick="deleteBorrower(${borrower.id})"><i class="bi bi-trash"></i></button>`;
        }
        html += '</td></tr>';
    });
    
    html += '</tbody></table>';
    document.getElementById('borrowerList').innerHTML = html;
}

// Load Guarantors
function loadGuarantors() {
    fetch(`../api/customer_api.php?action=get_guarantors&profile_id=${profileId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayGuarantors(data.data);
            } else {
                document.getElementById('guarantorList').innerHTML = '<p class="text-center text-muted py-4">No guarantors found</p>';
            }
        });
}

function displayGuarantors(guarantors) {
    if (guarantors.length === 0) {
        document.getElementById('guarantorList').innerHTML = '<p class="text-center text-muted py-4">No guarantors added yet</p>';
        return;
    }
    
    let html = '<table class="table table-hover"><thead><tr>';
    html += '<th>Full Name / पूरा नाम</th>';
    
    if (guarantors[0].guarantor_type === 'Individual') {
        html += '<th>Date of Birth / जन्म मिति</th>';
        html += '<th>Citizenship / नागरिकता</th>';
        html += '<th>Father Name / बुबाको नाम</th>';
        // Family Details removed as per request
    } else {
        html += '<th>Registration No / दर्ता नं</th>';
        html += '<th>PAN Number / प्यान नं</th>';
        html += '<th>Authorized Person / अधिकृत व्यक्ति</th>';
    }
    
    html += '<th>Actions</th></tr></thead><tbody>';
    
    guarantors.forEach(guarantor => {
        html += '<tr>';
        // Add Co-Borrower badge if applicable
        const coBorrowerBadge = guarantor.is_co_borrower == 1 
            ? ' <span class="badge bg-primary ms-2" title="This guarantor is also a Co-Borrower">Co-Borrower</span>' 
            : '';
        html += `<td>${guarantor.full_name || guarantor.company_name}${coBorrowerBadge}</td>`;
        
        if (guarantor.guarantor_type === 'Individual') {
            html += `<td>${guarantor.date_of_birth || 'N/A'}</td>`;
            html += `<td>${guarantor.citizenship_number || 'N/A'}</td>`;
            html += `<td><span class="father-name-g-${guarantor.id}">${guarantor.father_name || 'Loading...'}</span></td>`;
            // Family details removed
            
            // Fetch family details (still needed for Father Name if not present in main record)
            fetch(`../api/customer_api.php?action=get_family_details&person_id=${guarantor.id}&person_type=Guarantor`)
                .then(response => response.json())
                .then(data => {
                    let fatherName = 'N/A';
                    
                    if (data.success && data.data && data.data.length > 0) {
                        // Find father
                        const father = data.data.find(m => m.relation === 'बुबा' || m.relation === 'Father' || m.relation === 'Father-in-law' || m.relation === 'ससुरा');
                        if (father) fatherName = father.name;
                    }
                    
                    const elFather = document.querySelector(`.father-name-g-${guarantor.id}`);
                    if(elFather) elFather.innerHTML = fatherName;
                })
                .catch(() => {
                    const elFather = document.querySelector(`.father-name-g-${guarantor.id}`);
                    if(elFather) elFather.innerHTML = 'N/A';
                });
        } else {
            html += `<td>${guarantor.registration_no || 'N/A'}</td>`;
            html += `<td>${guarantor.pan_number || 'N/A'}</td>`;
            html += `<td>View Details</td>`;
        }
        
        html += '<td>';
        html += `<button class="btn btn-sm btn-info me-1" onclick="viewGuarantor(${guarantor.id})"><i class="bi bi-eye"></i></button>`;
        if (canEdit) {
            html += `<button class="btn btn-sm btn-warning me-1" onclick="editGuarantor(${guarantor.id})"><i class="bi bi-pencil"></i></button>`;
            html += `<button class="btn btn-sm btn-danger" onclick="deleteGuarantor(${guarantor.id})"><i class="bi bi-trash"></i></button>`;
        }
        html += '</td></tr>';
    });
    
    html += '</tbody></table>';
    document.getElementById('guarantorList').innerHTML = html;
}

// Load Collaterals
function loadCollaterals() {
    fetch(`../api/customer_api.php?action=get_collaterals&profile_id=${profileId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayCollaterals(data.data);
            } else {
                document.getElementById('collateralList').innerHTML = '<p class="text-center text-muted py-4">No collaterals found</p>';
            }
        });
}

function displayCollaterals(collaterals) {
    if (collaterals.length === 0) {
        document.getElementById('collateralList').innerHTML = '<p class="text-center text-muted py-4">No collaterals added yet</p>';
        return;
    }
    
    let html = '<table class="table table-hover"><thead><tr>';
    html += '<th>Type / प्रकार</th>';
    html += '<th>VDC/Municipality / नगरपालिका</th>';
    html += '<th>Kitta No / कित्ता नं</th>';
    html += '<th>Malpot Office / मालपोत</th>';
    html += '<th>Owner / मालिक</th>';
    html += '<th>Actions</th></tr></thead><tbody>';
    
    collaterals.forEach(collateral => {
        html += '<tr>';
        html += `<td><span class="badge bg-${collateral.collateral_type === 'Land' ? 'success' : 'primary'}">${collateral.collateral_type}</span></td>`;
        
        if (collateral.collateral_type === 'Land') {
            html += `<td>${collateral.land_municipality_vdc || 'N/A'}</td>`;
            html += `<td>${collateral.land_kitta_no || 'N/A'}</td>`;
            html += `<td>${collateral.land_malpot_office || 'N/A'}</td>`;
        } else {
            html += `<td colspan="3"><small>Vehicle No: ${collateral.vehicle_no || 'N/A'}, Model: ${collateral.vehicle_model_no || 'N/A'}</small></td>`;
        }
        
        html += `<td>${collateral.owner_name || 'N/A'} <small class="text-muted">(${collateral.owner_type})</small></td>`;
        html += '<td>';
        html += `<button class="btn btn-sm btn-info me-1" onclick="viewCollateral(${collateral.id})"><i class="bi bi-eye"></i></button>`;
        if (canEdit) {
            html += `<button class="btn btn-sm btn-warning me-1" onclick="editCollateral(${collateral.id})"><i class="bi bi-pencil"></i></button>`;
            html += `<button class="btn btn-sm btn-danger" onclick="deleteCollateral(${collateral.id})"><i class="bi bi-trash"></i></button>`;
        }
        html += '</td></tr>';
    });
    
    html += '</tbody></table>';
    document.getElementById('collateralList').innerHTML = html;
}

// Load Limits
function loadLimits() {
    fetch(`../api/customer_api.php?action=get_limits&profile_id=${profileId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayLimits(data.data);
            } else {
                document.getElementById('limitList').innerHTML = '<p class="text-center text-muted py-4">No limits found</p>';
            }
        });
}


function toNepaliNumber(num) {
    if (num === null || num === undefined) return '';
    const str = num.toString();
    const nepaliMap = {
        '0': '०', '1': '१', '2': '२', '3': '३', '4': '४',
        '5': '५', '6': '६', '7': '७', '8': '८', '9': '९'
    };
    return str.replace(/[0-9]/g, match => nepaliMap[match]);
}

function displayLimits(limits) {
    if (limits.length === 0) {
        document.getElementById('limitList').innerHTML = '<p class="text-center text-muted py-4">No limits added yet</p>';
        return;
    }
    
    let html = '<table class="table table-hover"><thead><tr>';
    html += '<th>Loan Type</th><th>Amount</th><th>Tenure</th><th>Interest Rate</th><th>Base Rate</th><th>Premium</th><th>Actions</th></tr></thead><tbody>';
    
    limits.forEach(limit => {
        html += '<tr>';
        html += `<td>${limit.loan_type}</td>`;
        html += `<td>Rs. ${toNepaliNumber(parseFloat(limit.amount).toLocaleString())}</td>`;
        html += `<td>${toNepaliNumber(limit.tenure)} months</td>`;
        html += `<td>${toNepaliNumber(limit.interest_rate)}%</td>`;
        html += `<td>${limit.base_rate ? toNepaliNumber(limit.base_rate) + '%' : 'N/A'}</td>`;
        html += `<td>${limit.premium ? toNepaliNumber(limit.premium) + '%' : 'N/A'}</td>`;
        html += '<td>';
        html += `<button class="btn btn-sm btn-info me-1" onclick="viewLimit(${limit.id})"><i class="bi bi-eye"></i></button>`;
        if (canEdit) {
            html += `<button class="btn btn-sm btn-warning me-1" onclick="editLimit(${limit.id})"><i class="bi bi-pencil"></i></button>`;
            html += `<button class="btn btn-sm btn-danger" onclick="deleteLimit(${limit.id})"><i class="bi bi-trash"></i></button>`;
        }
        html += '</td></tr>';
    });
    
    html += '</tbody></table>';
    document.getElementById('limitList').innerHTML = html;
}

// Load Loans
function loadLoans() {
    fetch(`../api/customer_api.php?action=get_loans&profile_id=${profileId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayLoans(data.data);
            } else {
                document.getElementById('loanList').innerHTML = '<p class="text-center text-muted py-4">No loans found</p>';
            }
        });
}

function displayLoans(loans) {
    if (loans.length === 0) {
        document.getElementById('loanList').innerHTML = '<p class="text-center text-muted py-4">No loans added yet</p>';
        return;
    }
    
    let html = '<table class="table table-hover"><thead><tr>';
    html += '<th>Loan Type</th><th>Loan Scheme</th><th>Approved Date</th><th>Actions</th></tr></thead><tbody>';
    
    loans.forEach(loan => {
        html += '<tr>';
        html += `<td>${loan.loan_type}</td>`;
        // Display loan_scheme_name (plain text field)
        const schemeName = loan.loan_scheme_name || loan.scheme_name || 'Not specified';
        html += `<td><span class="badge bg-primary">${schemeName}</span></td>`;
        // Handle date display - convert to Nepali if valid
        const approvedDate = loan.loan_approved_date && loan.loan_approved_date !== '0000-00-00' 
            ? toNepaliNumber(loan.loan_approved_date) 
            : 'Not set';
        html += `<td>${approvedDate}</td>`;
        html += '<td>';
        html += `<button class="btn btn-sm btn-info me-1" onclick="viewLoan(${loan.id})"><i class="bi bi-eye"></i></button>`;
        if (canEdit) {
            html += `<button class="btn btn-sm btn-warning me-1" onclick="editLoan(${loan.id})"><i class="bi bi-pencil"></i></button>`;
            html += `<button class="btn btn-sm btn-danger me-1" onclick="deleteLoan(${loan.id})"><i class="bi bi-trash"></i></button>`;
        }
        html += '</td></tr>';
    });
    
    html += '</tbody></table>';
    document.getElementById('loanList').innerHTML = html;
}

// Load Documents
function loadDocuments() {
    console.log('Loading documents for profile:', profileId);
    
    const formData = new FormData();
    formData.append('action', 'get_generated_documents');
    formData.append('profile_id', profileId);
    formData.append('latest_only', 'true');

    fetch(`../api/document_generation_api.php`, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            console.log('Documents API response:', data);
            if (data.success) {
                console.log('Found', data.data.length, 'documents');
                displayDocuments(data.data);
            } else {
                console.warn('No documents found:', data.message);
                document.getElementById('documentList').innerHTML = '<p class="text-center text-muted py-4">No documents generated yet</p>';
            }
        })
        .catch(error => {
            console.error('Error loading documents:', error);
            document.getElementById('documentList').innerHTML = '<p class="text-center text-danger py-4">Error loading documents</p>';
        });
}

function displayDocuments(documents) {
    if (documents.length === 0) {
        document.getElementById('documentList').innerHTML = '<p class="text-center text-muted py-4">No documents generated yet</p>';
        return;
    }
    
    let html = '<table class="table table-hover"><thead><tr>';
    html += '<th><i class="bi bi-file-earmark-word me-2"></i>Document Name</th>';
    html += '<th><i class="bi bi-calendar me-2"></i>Generated Date</th>';
    html += '<th><i class="bi bi-person me-2"></i>Generated By</th>';
    html += '<th><i class="bi bi-download me-2"></i>Actions</th>';
    html += '</tr></thead><tbody>';
    
    documents.forEach(doc => {
        html += '<tr>';
        html += `<td><i class="bi bi-file-earmark-word text-primary me-2"></i>${doc.template_name}</td>`;
        html += `<td>${new Date(doc.generated_at).toLocaleString()}</td>`;
        html += `<td>${doc.generated_by_name || 'System'}</td>`;
        html += `<td>`;
        // Use full file_path (includes folder structure) for download
        // Remove 'generated_documents/' prefix if present since download script adds it
        const filePath = doc.file_path.replace('generated_documents/', '');
        
        // Group buttons
        html += `<div class="btn-group" role="group">`;
        html += `<a href="../../download_document.php?file=${encodeURIComponent(filePath)}" class="btn btn-sm btn-primary" target="_blank" title="Download Word"><i class="bi bi-file-earmark-word me-1"></i>Word</a>`;
        html += `<a href="../../download_pdf.php?file=${encodeURIComponent(filePath)}" class="btn btn-sm btn-danger" target="_blank" title="Download PDF"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>`;
        html += `<a href="../../test_pdf_html.php?file=${encodeURIComponent(filePath)}" class="btn btn-sm btn-secondary" target="_blank" title="Debug HTML"><i class="bi bi-code-slash"></i></a>`;
        html += `</div>`;
        
        html += `</td></tr>`;
    });
    
    html += '</tbody></table>';
    document.getElementById('documentList').innerHTML = html;
}

function downloadAllDocuments() {
    window.location.href = '../../download_zip.php?profile_id=' + profileId;
}


// Workflow functions
function loadHistory() {
    fetch(`../api/customer_api.php?action=get_comments&profile_id=${profileId}`)
        .then(response => response.json())
        .then(data => {
            const historyElement = document.getElementById('workflowHistory');
            if (!historyElement) {
                // Element doesn't exist on this page, skip
                return;
            }
            
            if (data.success && data.data.length > 0) {
                let html = '<div class="timeline">';
                data.data.forEach(log => {
                    let color = 'secondary';
                    if (log.stage === 'Approval') color = 'success';
                    if (log.stage === 'Rejection') color = 'danger';
                    if (log.stage === 'Return') color = 'warning';
                    if (log.stage === 'Submission') color = 'primary';
                    
                    html += `
                        <div class="d-flex mb-3 border-bottom pb-2">
                            <div class="me-3">
                                <span class="badge bg-${color}">${log.stage}</span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <strong>${log.user_name || 'Unknown'}</strong>
                                    <small class="text-muted">${log.created_at}</small>
                                </div>
                                <p class="mb-0 mt-1">${log.comment_text}</p>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                historyElement.innerHTML = html;
            } else {
                historyElement.innerHTML = '<p class="text-center text-muted">No remarks recorded yet.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading history:', error);
        });
}

// Workflow Functions with Modals

// SUBMIT
function submitProfile() {
    new bootstrap.Modal(document.getElementById('submitModal')).show();
}

function confirmSubmit() {
    const remarks = document.getElementById('submitRemarks').value;
    const btn = document.getElementById('btnConfirmSubmit');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
    
    const formData = new FormData();
    formData.append('action', 'submit_profile');
    formData.append('profile_id', profileId);
    formData.append('remarks', remarks);
    
    fetch('../api/customer_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Success: ' + data.message);
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

// APPROVE
function approveProfile() {
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
    formData.append('profile_id', profileId);
    formData.append('remarks', remarks);
    
    fetch('../api/customer_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Check for warnings in the message
            if (data.message.includes('WARNING')) {
                alert('⚠️ Profile Approved with Warnings:\n' + data.message);
            } else {
                alert('✅ ' + data.message);
            }
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

// REJECT
function rejectProfile() {
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function confirmReject() {
    const reason = document.getElementById('rejectReason').value;
    if (!reason.trim()) {
        alert('Please enter a reason for rejection.');
        return;
    }

    const btn = document.getElementById('btnConfirmReject');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Rejecting...';

    const formData = new FormData();
    formData.append('action', 'reject_profile');
    formData.append('profile_id', profileId);
    formData.append('rejection_reason', reason);
    
    fetch('../api/customer_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Profile Rejected.');
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

// RETURN
function returnProfile() {
    new bootstrap.Modal(document.getElementById('returnModal')).show();
}

function confirmReturn() {
    const comment = document.getElementById('returnComment').value;
    if (!comment.trim()) {
        alert('Please enter comments for the maker.');
        return;
    }

    const btn = document.getElementById('btnConfirmReturn');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Returning...';

    const formData = new FormData();
    formData.append('action', 'return_profile');
    formData.append('profile_id', profileId);
    formData.append('checker_comment', comment);
    
    fetch('../api/customer_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Profile Returned to Maker.');
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



// Add/Edit/Delete functions (to be implemented with modals)
function addBorrower() {
    window.location.href = `forms/borrower_form.php?profile_id=${profileId}&type=${customerType}`;
}

function addGuarantor(type) {
    window.location.href = `forms/guarantor_form.php?profile_id=${profileId}&type=${type}`;
}

function addCollateral() {
    window.location.href = `forms/collateral_form.php?profile_id=${profileId}`;
}

function addLimit() {
    window.location.href = `forms/limit_form.php?profile_id=${profileId}`;
}

function addLoan() {
    window.location.href = `forms/loan_form.php?profile_id=${profileId}`;
}

// Edit functions
function editBorrower(borrowerId) {
    window.location.href = `forms/borrower_form.php?profile_id=${profileId}&type=${customerType}&id=${borrowerId}`;
}

function editGuarantor(guarantorId) {
    window.location.href = `forms/guarantor_form.php?profile_id=${profileId}&type=${customerType}&id=${guarantorId}`;
}

function editCollateral(collateralId) {
    window.location.href = `forms/collateral_form.php?profile_id=${profileId}&id=${collateralId}`;
}

function editLimit(limitId) {
    window.location.href = `forms/limit_form.php?profile_id=${profileId}&id=${limitId}`;
}

function editLoan(loanId) {
    window.location.href = `forms/loan_form.php?profile_id=${profileId}&id=${loanId}`;
}

// View functions
function viewBorrower(borrowerId) {
    window.location.href = `forms/borrower_form.php?profile_id=${profileId}&type=${customerType}&id=${borrowerId}&view_mode=1`;
}

function viewGuarantor(guarantorId) {
    window.location.href = `forms/guarantor_form.php?profile_id=${profileId}&type=${customerType}&id=${guarantorId}&view_mode=1`;
}

function viewCollateral(collateralId) {
    window.location.href = `forms/collateral_form.php?profile_id=${profileId}&id=${collateralId}&view_mode=1`;
}

function viewLimit(limitId) {
    window.location.href = `forms/limit_form.php?profile_id=${profileId}&id=${limitId}&view_mode=1`;
}

function viewLoan(loanId) {
    window.location.href = `forms/loan_form.php?profile_id=${profileId}&id=${loanId}&view_mode=1`;
}

// Delete functions
function deleteBorrower(id) {
    if (confirm('Are you sure you want to delete this borrower?')) {
        deleteItem('borrower', id, loadBorrowers);
    }
}

function deleteGuarantor(id) {
    if (confirm('Are you sure you want to delete this guarantor?')) {
        deleteItem('guarantor', id, loadGuarantors);
    }
}

function deleteCollateral(id) {
    if (confirm('Are you sure you want to delete this collateral?')) {
        deleteItem('collateral', id, loadCollaterals);
    }
}

function deleteLimit(id) {
    if (confirm('Are you sure you want to delete this limit?')) {
        deleteItem('limit', id, loadLimits);
    }
}

function deleteLoan(id) {
    if (confirm('Are you sure you want to delete this loan?')) {
        deleteItem('loan', id, loadLoans);
    }
}

function deleteItem(type, id, reloadCallback) {
    const formData = new FormData();
    formData.append('action', 'delete_item');
    formData.append('type', type);
    formData.append('id', id);
    
    fetch('../api/customer_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            reloadCallback();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Conflicting document functions removed to fix display issue


function deleteDocument(docId) {
    if (confirm('Are you sure you want to delete this document?')) {
        const formData = new FormData();
        formData.append('action', 'delete_document');
        formData.append('doc_id', docId);
        
        fetch('../api/document_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                loadDocuments();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function uploadDocument() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.docx,.pdf,.doc';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('action', 'upload_document');
            formData.append('profile_id', profileId);
            formData.append('document', file);
            
            fetch('../api/document_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadDocuments();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    };
    input.click();
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    else if (bytes < 1048576) return (bytes / 1024).toFixed(2) + ' KB';
    else return (bytes / 1048576).toFixed(2) + ' MB';
}

// Log Pagination Variables
let allLogData = [];
let currentLogPage = 1;
const logsPerPage = 5;

function loadComments() {
    const list = document.getElementById('commentLogList');
    
    fetch(`../api/customer_api.php?action=get_comments&profile_id=${profileId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            allLogData = data.data; // Store all data
            if (allLogData.length > 0) {
                renderLogPage(1); // Render first page
            } else {
                list.innerHTML = '<div class="alert alert-light text-center">No workflow logs found.</div>';
                document.getElementById('logPagination').classList.add('d-none');
            }
        } else {
            list.innerHTML = '<div class="alert alert-light text-center">No workflow logs found.</div>';
        }
    })
    .catch(error => {
        console.error('Error loading comments:', error);
        list.innerHTML = '<p class="text-danger text-center">Failed to load logs.</p>';
    });
}

function renderLogPage(page) {
    const list = document.getElementById('commentLogList');
    const pagination = document.getElementById('logPagination');
    const totalPages = Math.ceil(allLogData.length / logsPerPage);
    
    // Validate page
    if (page < 1) page = 1;
    if (page > totalPages) page = totalPages;
    currentLogPage = page;
    
    // Calculate slice
    const start = (page - 1) * logsPerPage;
    const end = start + logsPerPage;
    const pageData = allLogData.slice(start, end);
    
    let html = '<div class="timeline">';
    pageData.forEach(log => {
        const date = new Date(log.commented_at);
        const dateStr = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        
        let badgeClass = 'bg-secondary';
        if (log.comment_type === 'Submission') badgeClass = 'bg-info';
        if (log.comment_type === 'Approval') badgeClass = 'bg-success';
        if (log.comment_type === 'Rejection') badgeClass = 'bg-danger';
        if (log.comment_type === 'Return') badgeClass = 'bg-warning text-dark';
        
        html += `
            <div class="border-start border-2 ps-3 pb-4 ms-2 position-relative">
                <div class="position-absolute top-0 start-0 translate-middle rounded-circle bg-primary" style="width: 12px; height: 12px;"></div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge ${badgeClass}">${log.comment_type}</span>
                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i>${dateStr}</small>
                </div>
                <div class="card bg-light border-0">
                    <div class="card-body py-2">
                        <p class="mb-1 text-dark">${log.comment_text}</p>
                        <small class="text-muted">By: <strong>${log.commenter_name}</strong> (${log.commenter_role})</small>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    list.innerHTML = html;
    
    // Update Pagination UI
    if (totalPages > 1) {
        pagination.classList.remove('d-none');
        document.getElementById('logPageIndicator').innerText = `Page ${currentLogPage} of ${totalPages}`;
        
        // Disable/Enable Prev
        const prevBtn = document.getElementById('logPrevBtn');
        if (currentLogPage === 1) prevBtn.classList.add('disabled');
        else prevBtn.classList.remove('disabled');
        
        // Disable/Enable Next
        const nextBtn = document.getElementById('logNextBtn');
        if (currentLogPage === totalPages) nextBtn.classList.add('disabled');
        else nextBtn.classList.remove('disabled');
    } else {
        pagination.classList.add('d-none');
    }
}

function changeLogPage(delta) {
    const newPage = currentLogPage + delta;
    if (newPage >= 1 && newPage <= Math.ceil(allLogData.length / logsPerPage)) {
        renderLogPage(newPage);
    }
}

</script>



<style>
.table-hover tbody tr:hover {
    background-color: #f8f9fa;
    cursor: pointer;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Add borders to tables */
.table {
    border: 1px solid #dee2e6;
}
.table th, .table td {
    border: 1px solid #dee2e6;
}
</style>


<!-- MODALS -->

<!-- Submit Modal -->
<div class="modal fade" id="submitModal" tabindex="-1" aria-labelledby="submitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="submitModalLabel"><i class="bi bi-send me-2"></i>Submit Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to submit this profile for approval?</p>
                <div class="mb-3">
                    <label for="submitRemarks" class="form-label">Remarks (Optional)</label>
                    <textarea class="form-control" id="submitRemarks" rows="3" placeholder="Enter any remarks for the checker..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnConfirmSubmit" onclick="confirmSubmit()">
                    <i class="bi bi-check-lg me-1"></i>Confirm Submit
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="approveModalLabel"><i class="bi bi-check-circle me-2"></i>Approve Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2">
                    <i class="bi bi-info-circle me-2"></i>Approving will automatically generate all legal documents.
                </div>
                <div class="mb-3">
                    <label for="approveRemarks" class="form-label">Approval Remarks (Optional)</label>
                    <textarea class="form-control" id="approveRemarks" rows="3" placeholder="Enter any approval remarks..."></textarea>
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
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectModalLabel"><i class="bi bi-x-circle me-2"></i>Reject Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="rejectReason" class="form-label fw-bold">Rejection Reason <span class="text-danger">*</span></label>
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

<!-- Return Modal -->
<div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="returnModalLabel"><i class="bi bi-arrow-return-left me-2"></i>Return to Maker</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Return this profile to the maker for corrections.</p>
                <div class="mb-3">
                    <label for="returnComment" class="form-label fw-bold">Comments for Maker <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="returnComment" rows="3" placeholder="What needs to be corrected?"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="btnConfirmReturn" onclick="confirmReturn()">
                    <i class="bi bi-arrow-return-left me-1"></i>Confirm Return
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Explicitly define workflow triggering functions to ensure they use Bootstrap API
function approveProfile() {
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function rejectProfile() {
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function returnProfile() {
    new bootstrap.Modal(document.getElementById('returnModal')).show();
}

function submitProfile() {
    new bootstrap.Modal(document.getElementById('submitModal')).show();
}

// Transaction Functions
function confirmApprove() {
    const remarks = document.getElementById('approveRemarks').value;
    const btn = document.getElementById('btnConfirmApprove');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';
    
    // Use proper API call
    fetch('../api/customer_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=approve_profile&profile_id=${profileId}&remarks=${encodeURIComponent(remarks)}`
    })
    .then(async response => {
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Server output:', text);
            throw new Error('Invalid JSON response: ' + text);
        }
    })
    .then(data => {
        if (data.success) {
            // Show documents section immediately or reload
            alert('Profile approved and documents generated successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during approval. See console for details.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function confirmReject() {
    const reason = document.getElementById('rejectReason').value;
    
    if (!reason || !reason.trim()) {
        alert('Please enter a rejection reason');
        document.getElementById('rejectReason').focus();
        return;
    }
    
    const btn = document.getElementById('btnConfirmReject');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Rejecting...';
    
    const formData = new FormData();
    formData.append('action', 'reject_profile');
    formData.append('profile_id', profileId);
    formData.append('rejection_reason', reason);
    
    fetch('../api/customer_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Profile rejected successfully');
            location.reload();
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function confirmReturn() {
    const comments = document.getElementById('returnComment').value;
    
    if (!comments || !comments.trim()) {
        alert('Please enter comments for the maker');
        document.getElementById('returnComment').focus();
        return;
    }
    
    const btn = document.getElementById('btnConfirmReturn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Returning...';
    
    const formData = new FormData();
    formData.append('action', 'return_profile');
    formData.append('profile_id', profileId);
    formData.append('comments', comments);
    
    fetch('../api/customer_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Profile returned to maker successfully');
            location.reload();
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

// Download All Documents
function downloadAllDocuments() {
    const pid = '<?php echo $profile_id; ?>'; 
    if (!pid) {
        alert('Profile ID is missing!');
        return;
    }
    // Redirect to the download script
    window.location.href = '../api/download_zip.php?profile_id=' + pid;
}

</script>

<style>
/* Fix for body padding when modal opens */
body.modal-open {
    padding-right: 0 !important;
    overflow: hidden;
}
</style>

<!-- Renewal Modal -->
<div class="modal fade" id="renewalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Create New Application</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Using <strong id="renewalCustomerName"></strong>'s existing profile as a base.
                </div>
                <div class="d-grid gap-3">
                    <button class="btn btn-outline-primary p-3 text-start" onclick="submitClone('Renewal')">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><i class="bi bi-arrow-repeat me-2"></i>Renewal</h5>
                        </div>
                        <p class="mb-1 small text-muted">Renew existing facility with same limits.</p>
                    </button>
                    
                    <button class="btn btn-outline-success p-3 text-start" onclick="submitClone('Enhancement')">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><i class="bi bi-graph-up-arrow me-2"></i>Enhancement</h5>
                        </div>
                        <p class="mb-1 small text-muted">Increase existing limits or add new facility.</p>
                    </button>
                    
                    <button class="btn btn-outline-warning p-3 text-start" onclick="submitClone('Reduction')">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><i class="bi bi-graph-down-arrow me-2"></i>Reduction</h5>
                        </div>
                        <p class="mb-1 small text-muted">Decrease existing limits.</p>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openRenewalModal(name) {
    document.getElementById('renewalCustomerName').textContent = name;
    new bootstrap.Modal(document.getElementById('renewalModal')).show();
}

function submitClone(type) {
    if (!confirm(`Confirm creating a ${type} application?`)) return;
    
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
        }
    })
    .catch(err => alert('System error'));
}
</script>

</script>

<!-- Import Collateral Modal -->
<div class="modal fade" id="importCollateralModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-box-arrow-in-down me-2"></i>Import Collateral from Other Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Search -->
                <div class="input-group mb-4">
                    <input type="text" class="form-control" id="collateralSearchInput" placeholder="Search Customer Name or ID...">
                    <button class="btn btn-primary" type="button" onclick="searchSourceCollateral()">Search</button>
                </div>
                
                <!-- Results -->
                <div id="importCollateralList" class="mb-3">
                    <p class="text-muted text-center">Search for a customer to see their available collateral.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" onclick="submitImportCollateral()">Import Selected</button>
            </div>
        </div>
    </div>
</div>

<script>
function openImportCollateralModal() {
    new bootstrap.Modal(document.getElementById('importCollateralModal')).show();
    // Load all collaterals by default
    document.getElementById('collateralSearchInput').value = '';
    searchSourceCollateral();
}

function searchSourceCollateral() {
    const query = document.getElementById('collateralSearchInput').value;
    // Removed length check to allow "Show All" via empty query
    
    document.getElementById('importCollateralList').innerHTML = '<p class="text-center">Loading...</p>';
    
    fetch(`../api/customer_api.php?action=search_collateral_source&query=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('importCollateralList');
            if (!data.success || data.data.length === 0) {
                list.innerHTML = '<p class="text-center text-muted">No collateral found.</p>';
                return;
            }
            
            let html = `
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="50" class="text-center">
                                    <input class="form-check-input" type="checkbox" id="selectAllCollateraSearch" onclick="toggleAllCollateralSearch(this)">
                                </th>
                                <th>Owner / मालिक</th>
                                <th>Type / प्रकार</th>
                                <th>Details / विवरण</th>
                                <th>Value / मूल्य</th>
                                <th>Source / स्रोत</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            data.data.forEach(item => {
                html += `
                    <tr>
                        <td class="text-center">
                            <input class="form-check-input" type="checkbox" value="${item.id}" name="import_col_ids">
                        </td>
                        <td>
                            <div class="fw-bold">${item.owner_name}</div>
                            <small class="text-muted">${item.owner_type}</small>
                        </td>
                        <td><span class="badge bg-secondary">${item.type}</span></td>
                        <td><small>${item.description}</small></td>
                        <td>${item.fair_market_value}</td>
                        <td><small class="text-primary">${item.source_profile_name}</small></td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            list.innerHTML = html;
        });
}

function toggleAllCollateralSearch(source) {
    const checkboxes = document.querySelectorAll('input[name="import_col_ids"]');
    checkboxes.forEach(cb => cb.checked = source.checked);
}

function submitImportCollateral() {
    const checkboxes = document.querySelectorAll('input[name="import_col_ids"]:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one item');
        return;
    }
    
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    if(!confirm(`Import ${ids.length} collateral items?`)) return;
    
    const formData = new FormData();
    formData.append('action', 'import_collateral');
    formData.append('target_profile_id', profileId);
    formData.append('source_collateral_ids', JSON.stringify(ids));
    
    fetch('../api/customer_api.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Collateral imported successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>

<?php
$mainContent = ob_get_clean();

// Include the layout
include '../../Layout/layout_new.php';
?>
```
