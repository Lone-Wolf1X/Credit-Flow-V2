<?php
session_start();
require_once '../../../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../../login.php');
    exit;
}

$profile_id = $_GET['profile_id'] ?? '';
$collateral_id = $_GET['id'] ?? null;
$view_mode = isset($_GET['view_mode']) && $_GET['view_mode'] == '1';

// Force Checker to View Mode
if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Checker') {
    $view_mode = true;
}

if (empty($profile_id)) {
    header('Location: ../create_customer.php');
    exit;
}

// If editing, fetch collateral data
$collateral_data = null;
if ($collateral_id) {
    $stmt = $conn->prepare("SELECT * FROM collateral WHERE id = ? AND customer_profile_id = ?");
    $stmt->bind_param("ii", $collateral_id, $profile_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $collateral_data = $result->fetch_assoc();
}

// Page variables for layout
$pageTitle = ($view_mode ? 'View' : ($collateral_id ? 'Edit' : 'Add')) . ' Collateral';
$activeMenu = 'customer_profile';
$userName = $_SESSION['full_name'] ?? 'User';
$userAvatar = '../../../asstes/images/img_avatar.png';
$badgeText = $_SESSION['role_name'] ?? 'Maker';

ob_start();

// Inject View Mode Flag for JS
if ($view_mode) {
    echo '<script>window.FORCE_VIEW_MODE = true;</script>';
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-building me-2"></i><?php echo $collateral_id ? 'Edit' : 'Add'; ?> Collateral / धितो <?php echo $collateral_id ? 'सम्पादन' : 'थप्नुहोस्'; ?>
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../customer_profile.php?id=<?php echo $profile_id; ?>">Customer Profile</a></li>
                    <li class="breadcrumb-item active"><?php echo $collateral_id ? 'Edit' : 'Add'; ?> Collateral</li>
                </ol>
            </nav>
        </div>
    </div>

    <form id="collateralForm" class="needs-validation" novalidate>
        <input type="hidden" name="customer_profile_id" value="<?php echo $profile_id; ?>">
        <?php if ($collateral_id): ?>
        <input type="hidden" name="collateral_id" value="<?php echo $collateral_id; ?>">
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-building me-2"></i>Collateral Details / धितो विवरण</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Collateral Type -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Collateral Type</label>
                        <select class="form-select" name="collateral_type" id="collateral_type" onchange="toggleCollateralFields()" required>
                            <option value="">Select</option>
                            <option value="Land" <?php echo ($collateral_data['collateral_type'] ?? '') == 'Land' ? 'selected' : ''; ?>>Land</option>
                            <option value="Vehicle" <?php echo ($collateral_data['collateral_type'] ?? '') == 'Vehicle' ? 'selected' : ''; ?>>Vehicle</option>
                        </select>
                    </div>

                    <!-- Owner Selection -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Owner Type</label>
                        <select class="form-select" name="owner_type" id="owner_type" onchange="loadOwners()" required>
                            <option value="">Select</option>
                            <option value="Borrower" <?php echo ($collateral_data['owner_type'] ?? '') == 'Borrower' ? 'selected' : ''; ?>>Borrower</option>
                            <option value="Guarantor" <?php echo ($collateral_data['owner_type'] ?? '') == 'Guarantor' ? 'selected' : ''; ?>>Guarantor</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Owner</label>
                        <select class="form-select" name="owner_id" id="owner_id" required <?php echo empty($collateral_data) ? 'disabled' : ''; ?>>
                            <option value="">Select owner first</option>
                        </select>
                    </div>
                </div>

                <!-- Land Fields -->
                <div id="landFields" style="display: none;">
                    <hr class="my-4">
                    <h6 class="fw-bold mb-3 text-primary">Land Details / जग्गा विवरण</h6>
                    <div class="row g-3">
                        <!-- Hidden inputs for land address -->
                        <input type="hidden" id="land_province_hidden" name="land_province" value="<?php echo $collateral_data['land_province'] ?? ''; ?>">
                        <input type="hidden" id="land_district_hidden" name="land_district" value="<?php echo $collateral_data['land_district'] ?? ''; ?>">
                        <input type="hidden" id="land_municipality_vdc_hidden" name="land_municipality_vdc" value="<?php echo $collateral_data['land_municipality_vdc'] ?? ''; ?>">
                        <input type="hidden" id="land_ward_no_hidden" name="land_ward_no" value="<?php echo $collateral_data['land_ward_no'] ?? ''; ?>">
                        
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Province / प्रदेश</label>
                            <select class="form-select" id="land_province" onchange="updateHiddenField('land_province', this.value)">
                                <option value="">Select / छान्नुहोस्</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">District / जिल्ला</label>
                            <select class="form-select" id="land_district" onchange="updateHiddenField('land_district', this.value)" disabled>
                                <option value="">Select / छान्नुहोस्</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Municipality/VDC / नगरपालिका/गाउँपालिका</label>
                            <select class="form-select" id="land_municipality_vdc" onchange="updateHiddenField('land_municipality_vdc', this.value)" disabled>
                                <option value="">Select / छान्नुहोस्</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Ward No / वडा नं</label>
                            <select class="form-select" id="land_ward_no" onchange="updateHiddenField('land_ward_no', this.value)" disabled>
                                <option value="">Select / छान्नुहोस्</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Sheet No / सिट नं</label>
                            <input type="text" class="form-control nepali-input" name="land_sheet_no" value="<?php echo $collateral_data['land_sheet_no'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Kitta No / कित्ता नं</label>
                            <input type="text" class="form-control nepali-input" name="land_kitta_no" value="<?php echo $collateral_data['land_kitta_no'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Area (sq ft) / क्षेत्रफल</label>
                            <input type="text" class="form-control nepali-input" name="land_area" value="<?php echo $collateral_data['land_area'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Malpot Office / मालपोत कार्यालय</label>
                            <input type="text" class="form-control nepali-input" name="land_malpot_office" value="<?php echo $collateral_data['land_malpot_office'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Kitta No in Words / कित्ता नं अक्षरमा</label>
                            <input type="text" class="form-control nepali-input" name="land_kitta_no_words" value="<?php echo $collateral_data['land_kitta_no_words'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Khande / खण्ड</label>
                            <input type="text" class="form-control nepali-input" name="land_khande" value="<?php echo $collateral_data['land_khande'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Dhito Parit Mulya / धितो परित मूल्य</label>
                            <input type="text" class="form-control nepali-input" name="land_dhito_parit_mulya" value="<?php echo $collateral_data['land_dhito_parit_mulya'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Dhito Parit Mulya in Words / धितो परित मूल्य अक्षरमा</label>
                            <input type="text" class="form-control nepali-input" name="land_dhito_parit_mulya_words" value="<?php echo $collateral_data['land_dhito_parit_mulya_words'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Biraha / बिराहा</label>
                            <input type="text" class="form-control nepali-input" name="land_biraha" value="<?php echo $collateral_data['land_biraha'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Kisim / किसिम</label>
                            <input type="text" class="form-control nepali-input" name="land_kisim" value="<?php echo $collateral_data['land_kisim'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Guthi / Mohi ko Name / गुठी/मोही को नाम</label>
                            <input type="text" class="form-control nepali-input" name="land_guthi_mohi_name" value="<?php echo $collateral_data['land_guthi_mohi_name'] ?? ''; ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Remarks / कैफियत</label>
                            <textarea class="form-control nepali-input" name="land_remarks" rows="3"><?php echo $collateral_data['land_remarks'] ?? ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Fields -->
                <div id="vehicleFields" style="display: none;">
                    <hr class="my-4">
                    <h6 class="fw-bold mb-3 text-primary">Vehicle Details / सवारी साधन विवरण</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Vehicle Model / गाडीको मोडेल</label>
                            <input type="text" class="form-control" name="vehicle_model_no" value="<?php echo $collateral_data['vehicle_model_no'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Engine No / इन्जिन नं</label>
                            <input type="text" class="form-control" name="vehicle_engine_no" value="<?php echo $collateral_data['vehicle_engine_no'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Chassis No / चेसिस नं</label>
                            <input type="text" class="form-control" name="vehicle_chassis_no" value="<?php echo $collateral_data['vehicle_chassis_no'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Vehicle No / गाडी नं</label>
                            <input type="text" class="form-control" name="vehicle_no" value="<?php echo $collateral_data['vehicle_no'] ?? ''; ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Legal Heir Details (New Section) -->
                <div class="mt-4 p-3 bg-light rounded border">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_legal_heir_applicable" name="is_legal_heir_applicable" value="1" onchange="toggleLegalHeirSection()">
                        <label class="form-check-label fw-bold" for="is_legal_heir_applicable">Legal Heir Applicable? / कानुनी हकवाला आवश्यक छ?</label>
                    </div>

                    <div id="legalHeirSection" style="display: none;">
                        <h6 class="fw-bold mb-3 text-primary">Legal Heir Details / कानुनी हकवाला विवरण</h6>
                        
                        <div id="legalHeirsContainer">
                            <!-- Dynamic Legal Heirs will be added here -->
                        </div>

                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addLegalHeirRow()">
                            <i class="bi bi-plus-circle me-1"></i>Add Legal Heir
                        </button>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success btn-lg px-5">
                        <i class="bi bi-check-circle me-2"></i>Save Collateral
                    </button>
                    <a href="../customer_profile.php?id=<?php echo $profile_id; ?>" class="btn btn-secondary btn-lg px-5 ms-2">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="/Credit/DAS/assets/js/preeti-converter.js?v=2.2"></script>
<script src="/Credit/DAS/assets/js/date-converter.js"></script>
<script src="/Credit/DAS/modules/customer/js/nepali_handler.js?v=2.2"></script>
<script src="../../../assets/js/location_cascade.js?v=<?= time() ?>"></script>
<script src="../../../assets/js/view_only_mode.js"></script>
<script>
// Toggle Legal Heir Section
function toggleLegalHeirSection() {
    const isChecked = document.getElementById('is_legal_heir_applicable').checked;
    const section = document.getElementById('legalHeirSection');
    section.style.display = isChecked ? 'block' : 'none';
    
    if (isChecked && document.querySelectorAll('.legal-heir-row').length === 0) {
        addLegalHeirRow();
    }
}

// Add Legal Heir Row
function addLegalHeirRow(data = null) {
    const container = document.getElementById('legalHeirsContainer');
    
    const row = document.createElement('div');
    row.className = 'legal-heir-row border p-3 mb-3 bg-white rounded position-relative';
    row.innerHTML = `
        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" onclick="this.parentElement.remove()">
            <i class="bi bi-trash"></i>
        </button>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Full Name / पूरा नाम</label>
                <input type="text" class="form-control form-control-sm nepali-input" name="legal_heir_name[]" value="${data ? data.name : ''}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Relation / नाता</label>
                <select class="form-select form-select-sm" name="legal_heir_relation[]" required>
                    <option value="">Select</option>
                    <option value="Son" ${data && data.relation === 'Son' ? 'selected' : ''}>Son / छोरा</option>
                    <option value="Daughter" ${data && data.relation === 'Daughter' ? 'selected' : ''}>Daughter / छोरी</option>
                    <option value="Wife" ${data && data.relation === 'Wife' ? 'selected' : ''}>Wife / श्रीमती</option>
                    <option value="Father" ${data && data.relation === 'Father' ? 'selected' : ''}>Father / बुबा</option>
                    <option value="Mother" ${data && data.relation === 'Mother' ? 'selected' : ''}>Mother / आमा</option>
                    <option value="Brother" ${data && data.relation === 'Brother' ? 'selected' : ''}>Brother / दाजु/भाइ</option>
                    <option value="Sister" ${data && data.relation === 'Sister' ? 'selected' : ''}>Sister / दिदी/बहिनी</option>
                    <option value="Daughter in Law" ${data && data.relation === 'Daughter in Law' ? 'selected' : ''}>Daughter in Law / बुहारी</option>
                    <option value="Grandson" ${data && data.relation === 'Grandson' ? 'selected' : ''}>Grandson / नाति</option>
                    <option value="Granddaughter" ${data && data.relation === 'Granddaughter' ? 'selected' : ''}>Granddaughter / नातिनी</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Father Name / बुबाको नाम</label>
                <input type="text" class="form-control form-control-sm nepali-input" name="legal_heir_father_name[]" value="${data ? data.father_name : ''}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Grandfather Name / बाजेको नाम</label>
                <input type="text" class="form-control form-control-sm nepali-input" name="legal_heir_grandfather_name[]" value="${data ? data.grandfather_name : ''}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">DOB / जन्म मिति</label>
                <input type="text" class="form-control form-control-sm nepali-input" name="legal_heir_dob[]" value="${data ? data.date_of_birth : ''}" placeholder="YYYY-MM-DD">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Citizenship No / नागरिकता नं</label>
                <input type="text" class="form-control form-control-sm nepali-input" name="legal_heir_citizenship_no[]" value="${data ? data.citizenship_no : ''}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Issue Date / जारी मिति</label>
                <input type="text" class="form-control form-control-sm nepali-input" name="legal_heir_citizenship_issue_date[]" value="${data ? data.citizenship_issue_date : ''}" placeholder="YYYY-MM-DD">
            </div>
             <div class="col-md-3">
                <label class="form-label small fw-bold">Issue District / जारी जिल्ला</label>
                <input type="text" class="form-control form-control-sm nepali-input" name="legal_heir_citizenship_issue_district[]" value="${data ? data.citizenship_issue_district : ''}">
            </div>
        </div>
    `;
    container.appendChild(row);
}
// Update hidden field when select value changes
function updateHiddenField(fieldName, value) {
    const hiddenField = document.getElementById(fieldName + '_hidden');
    if (hiddenField) {
        hiddenField.value = value;
    }
}

// Initialize location cascade for land
const landLocation = new LocationCascade('land_province', 'land_district', 'land_municipality_vdc', 'land_ward_no');

// Toggle collateral type fields
function toggleCollateralFields() {
    const type = document.getElementById('collateral_type').value;
    document.getElementById('landFields').style.display = type === 'Land' ? 'block' : 'none';
    document.getElementById('vehicleFields').style.display = type === 'Vehicle' ? 'block' : 'none';
}

// Load owners based on type
function loadOwners() {
    const ownerType = document.getElementById('owner_type').value;
    const ownerSelect = document.getElementById('owner_id');
    
    if (!ownerType) {
        ownerSelect.disabled = true;
        ownerSelect.innerHTML = '<option value="">Select owner type first</option>';
        return;
    }
    
    fetch(`../../api/customer_api.php?action=get_owners&profile_id=<?php echo $profile_id; ?>`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                ownerSelect.innerHTML = '<option value="">Select / छान्नुहोस्</option>';
                data.data.forEach(owner => {
                    if (owner.type === ownerType) {
                        const option = document.createElement('option');
                        option.value = owner.id;
                        option.textContent = owner.full_name;
                        ownerSelect.appendChild(option);
                    }
                });
                ownerSelect.disabled = false;
            }
        });
}

// Form submission
document.getElementById('collateralForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Convert all Preeti inputs to Unicode before submission
    if (typeof window.convertAllPreetiToUnicode === 'function') {
        window.convertAllPreetiToUnicode();
    }
    
    const formData = new FormData(this);
    formData.append('action', 'save_collateral');
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Saving...';
    
    fetch('../../api/customer_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert('Collateral saved successfully!');
                window.location.href = '../customer_profile.php?id=<?php echo $profile_id; ?>';
            } else {
                throw new Error(data.message || 'Unknown error occurred');
            }
        } catch (e) {
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server. Check console for details.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

// Initialize form for edit mode
<?php if ($collateral_data): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Show correct collateral fields
    toggleCollateralFields();
    
    // Load owners and select the current owner
    const ownerType = '<?php echo $collateral_data['owner_type'] ?? ''; ?>';
    const ownerId = '<?php echo $collateral_data['owner_id'] ?? ''; ?>';
    
    if (ownerType && ownerId) {
        fetch(`../../api/customer_api.php?action=get_owners&profile_id=<?php echo $profile_id; ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const ownerSelect = document.getElementById('owner_id');
                    ownerSelect.innerHTML = '<option value="">Select / छान्नुहोस्</option>';
                    data.data.forEach(owner => {
                        if (owner.type === ownerType) {
                            const option = document.createElement('option');
                            option.value = owner.id;
                            option.textContent = owner.full_name;
                            if (owner.id == ownerId) {
                                option.selected = true;
                            }
                            ownerSelect.appendChild(option);
                        }
                    });
                    ownerSelect.disabled = false;
                }
            });
    }
    
    // Load location data for land
    <?php if (($collateral_data['collateral_type'] ?? '') == 'Land'): ?>
    const province = '<?php echo $collateral_data['land_province'] ?? ''; ?>';
    const district = '<?php echo $collateral_data['land_district'] ?? ''; ?>';
    const municipality = '<?php echo $collateral_data['land_municipality_vdc'] ?? ''; ?>';
    const ward = '<?php echo $collateral_data['land_ward_no'] ?? ''; ?>';
    
    if (province) {
        landLocation.setValues(province, district, municipality, ward);
    }
    <?php endif; ?>


    // Load Legal Heir Data if applicable
    const isLegalHeirApplicable = <?php echo ($collateral_data['is_legal_heir_applicable'] ?? 0) == 1 ? 'true' : 'false'; ?>;
    if (isLegalHeirApplicable) {
        document.getElementById('is_legal_heir_applicable').checked = true;
        toggleLegalHeirSection();
        
        // Fetch saved legal heirs
        fetch(`../../api/customer_api.php?action=get_legal_heirs&collateral_id=<?php echo $collateral_id ?? 0; ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    document.getElementById('legalHeirsContainer').innerHTML = ''; // Clear default
                    data.data.forEach(heir => {
                        addLegalHeirRow(heir);
                    });
                }
            });
    }

});
<?php endif; ?>

</script>

<?php
$mainContent = ob_get_clean();
$assetPath = '../../../asstes';
include '../../../Layout/layout_new.php';
?>
