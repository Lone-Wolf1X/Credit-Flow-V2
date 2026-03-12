<?php
// Individual Guarantor Form
// Extracted from guarantor_form.php

// Set page title
$pageTitle = 'Individual Guarantor Form';

// Include Header
include __DIR__ . '/shared/form_header.php';

// Ensure $guarantor_data is available
if (!isset($guarantor_data)) {
    $guarantor_data = []; 
}
?>

<form id="guarantorForm" class="needs-validation" novalidate>
    <input type="hidden" name="guarantor_type" value="Individual">
    <input type="hidden" name="customer_profile_id" value="<?php echo $profile_id; ?>">
    <?php if ($guarantor_id): ?>
    <input type="hidden" name="guarantor_id" value="<?php echo $guarantor_id; ?>">
    <?php endif; ?>

    <!-- Person Selector Button -->
    <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="text-white">
                    <i class="fas fa-lightbulb"></i>
                    <strong>Quick Fill:</strong> Select an existing person to auto-fill this form
                </div>
                <a href="../pages/person_selector.php?form=guarantor&profile_id=<?php echo $profile_id; ?>&customer_type=Individual" 
                   class="btn btn-light btn-sm">
                    <i class="fas fa-user-check"></i> Select Existing Person
                </a>
            </div>
        </div>
    </div>


    <!-- Progress Bar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="progress" style="height: 30px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: 33%;">Step 1 of 3</div>
            </div>
            <div class="d-flex justify-content-between mt-3">
                <span class="step-indicator active">
                    <i class="bi bi-1-circle-fill"></i> Personal Details
                </span>
                <span class="step-indicator">
                    <i class="bi bi-2-circle"></i> Address
                </span>
                <span class="step-indicator">
                    <i class="bi bi-3-circle"></i> Family Details
                </span>
            </div>
        </div>
    </div>

    <!-- Step 1: Personal Details -->
    <div class="form-step">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-person me-2"></i>Step 1: Personal Details / व्यक्तिगत विवरण</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Full Name (English) / पूरा नाम (अंग्रेजी) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="full_name_en" value="<?php echo $guarantor_data['full_name_en'] ?? $guarantor_data['full_name'] ?? ''; ?>" required oninput="document.getElementById('hidden_full_name').value = this.value">
                        <input type="hidden" name="full_name" id="hidden_full_name" value="<?php echo $guarantor_data['full_name'] ?? ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Full Name (Nepali) / पूरा नाम (नेपाली)</label>
                        <input type="text" class="form-control nepali-input" name="full_name_np" value="<?php echo $guarantor_data['full_name_np'] ?? ''; ?>" placeholder="पूरा नाम">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Gender / लिङ्ग <span class="text-danger">*</span></label>
                        <select class="form-select" name="gender" required>
                            <option value="">Select / छान्नुहोस्</option>
                            <option value="Male" <?php echo ($guarantor_data['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male / पुरुष</option>
                            <option value="Female" <?php echo ($guarantor_data['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female / महिला</option>
                            <option value="Other" <?php echo ($guarantor_data['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other / अन्य</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Relationship Status / वैवाहिक स्थिति</label>
                        <select class="form-select" name="relationship_status">
                            <option value="">Select / छान्नुहोस्</option>
                            <option value="Single" <?php echo ($guarantor_data['relationship_status'] ?? '') == 'Single' ? 'selected' : ''; ?>>Single / अविवाहित</option>
                            <option value="Married" <?php echo ($guarantor_data['relationship_status'] ?? '') == 'Married' ? 'selected' : ''; ?>>Married / विवाहित</option>
                            <option value="Divorced" <?php echo ($guarantor_data['relationship_status'] ?? '') == 'Divorced' ? 'selected' : ''; ?>>Divorced / सम्बन्ध विच्छेद</option>
                            <option value="Widowed" <?php echo ($guarantor_data['relationship_status'] ?? '') == 'Widowed' ? 'selected' : ''; ?>>Widowed / विधवा/विधुर</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Date of Birth (BS) / जन्म मिति <span class="text-danger">*</span></label>
                        <input type="text" class="form-control nepali-date-picker" id="date_of_birth" name="date_of_birth" placeholder="YYYY-MM-DD" value="<?php echo $guarantor_data['date_of_birth'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Date of Birth (AD)</label>
                        <input type="text" class="form-control" id="date_of_birth_ad" name="date_of_birth_ad" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Age / उमेर</label>
                        <input type="text" class="form-control" id="age" name="age" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Citizenship Number / नागरिकता नं <span class="text-danger">*</span></label>
                        <input type="text" class="form-control nepali-input" name="citizenship_number" value="<?php echo $guarantor_data['citizenship_number'] ?? ''; ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">ID Issue Date (BS) / जारी मिति</label>
                        <input type="text" class="form-control nepali-date-picker" name="id_issue_date" placeholder="YYYY-MM-DD" value="<?php echo $guarantor_data['id_issue_date'] ?? ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">ID Issue Date (AD)</label>
                        <input type="text" class="form-control" id="id_issue_date_ad" name="id_issue_date_ad" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">ID Issue District / जारी जिल्ला</label>
                        <input type="text" class="form-control nepali-input" name="id_issue_district" value="<?php echo $guarantor_data['id_issue_district'] ?? ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">ID Customer Authority / जारी गर्ने निकाय</label>
                        <select class="form-select" name="id_issue_authority">
                            <option value="">छान्नुहोस्</option>
                            <option value="जिल्ला प्रशासन कार्यालय" <?php echo ($guarantor_data['id_issue_authority'] ?? '') == 'जिल्ला प्रशासन कार्यालय' ? 'selected' : ''; ?>>जिल्ला प्रशासन कार्यालय</option>
                            <option value="इलाका प्रशासन कार्यालय" <?php echo ($guarantor_data['id_issue_authority'] ?? '') == 'इलाका प्रशासन कार्यालय' ? 'selected' : ''; ?>>इलाका प्रशासन कार्यालय</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">CTZ Reissued / नागरिकता पुनः जारी</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="ctz_reissued" name="ctz_reissued" value="1" <?php echo ($guarantor_data['ctz_reissued'] ?? 0) == 1 ? 'checked' : ''; ?> onchange="toggleReissueFields()">
                            <label class="form-check-label" for="ctz_reissued">
                                Citizenship has been reissued
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">पुनः जारी मिति (BS)</label>
                        <input type="text" class="form-control nepali-date-picker" id="id_reissue_date" name="id_reissue_date" value="<?php echo $guarantor_data['id_reissue_date'] ?? ''; ?>" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">पुनः जारी मिति (AD)</label>
                        <input type="text" class="form-control" id="id_reissue_date_ad" name="id_reissue_date_ad" disabled readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Reissue Count / पुनः जारी पटक</label>
                        <select class="form-select" id="reissue_count" name="reissue_count" disabled>
                            <option value="0" <?php echo ($guarantor_data['reissue_count'] ?? 0) == 0 ? 'selected' : ''; ?>>First Issue / पहिलो पटक</option>
                            <option value="1" <?php echo ($guarantor_data['reissue_count'] ?? 0) == 1 ? 'selected' : ''; ?>>First Reissue / पहिलो पुनः जारी</option>
                            <option value="2" <?php echo ($guarantor_data['reissue_count'] ?? 0) == 2 ? 'selected' : ''; ?>>Second Reissue / दोस्रो पुनः जारी</option>
                            <option value="3" <?php echo ($guarantor_data['reissue_count'] ?? 0) == 3 ? 'selected' : ''; ?>>Third Reissue / तेस्रो पुनः जारी</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Co-borrower / सह-ऋणी</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="is_co_borrower" value="1" <?php echo ($guarantor_data['is_co_borrower'] ?? 0) == 1 ? 'checked' : ''; ?>>
                            <label class="form-check-label">Mark as Co-borrower for Joint Loan</label>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="button" class="btn btn-primary btn-lg px-5 btn-next">
                        Next <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Address Details -->
    <div class="form-step" style="display: none;">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Step 2: Address Details / ठेगाना विवरण</h5>
            </div>
            <div class="card-body p-4">
                <?php 
                // data for address section
                $data = $guarantor_data;
                $addressLabels = [
                    'permanent' => 'Permanent Address / स्थायी ठेगाना',
                    'temporary' => 'Temporary Address / अस्थायी ठेगाना',
                    'sameAs' => 'Same as Permanent Address / स्थायी ठेगाना जस्तै'
                ];
                include __DIR__ . '/shared/address_section.php'; 
                ?>
                
                <div class="mt-4">
                    <button type="button" class="btn btn-secondary btn-lg px-5 me-2 btn-prev">
                        <i class="bi bi-arrow-left me-2"></i> Previous
                    </button>
                    <button type="button" class="btn btn-primary btn-lg px-5 btn-next">
                        Next <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: Family Details -->
    <div class="form-step" style="display: none;">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-people me-2"></i>Step 3: Family Details / परिवार विवरण</h5>
            </div>
            <div class="card-body p-4">
                <div id="familyContainer">
                    <h6 class="fw-bold mb-3 text-primary">Family Members / परिवारका सदस्यहरू</h6>
                     <div class="dynamic-row row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Name / नाम</label>
                            <input type="text" class="form-control nepali-input" name="family_name[]">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Relation / नाता</label>
                            <select class="form-select" name="family_relation[]">
                                <option value="">Select / छान्नुहोस्</option>
                                <option value="बुबा">बुबा</option>
                                <option value="आमा">आमा</option>
                                <option value="बाजे">बाजे</option>
                                <option value="बज्यै">बज्यै</option>
                                <option value="छोरा">छोरा</option>
                                <option value="छोरी">छोरी</option>
                                <option value="पति">पति</option>
                                <option value="पत्नी">पत्नी</option>
                                <option value="ससुरा">ससुरा</option>
                                <option value="सासु">सासु</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">&nbsp;</label>
                            <div class="d-grid">
                                <button type="button" class="btn btn-danger" onclick="removeDynamicRow(this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-success mb-4" onclick="addFamilyRow()">
                    <i class="bi bi-plus-circle me-2"></i>Add Family Member
                </button>
                <div class="mt-4">
                    <button type="button" class="btn btn-secondary btn-lg px-5 me-2 btn-prev">
                        <i class="bi bi-arrow-left me-2"></i> Previous
                    </button>
                    <button type="submit" class="btn btn-success btn-lg px-5">
                        <i class="bi bi-check-circle me-2"></i> Save Guarantor
                    </button>
                    <a href="../customer_profile.php?id=<?php echo $profile_id; ?>" class="btn btn-danger btn-lg px-5 ms-2">
                        <i class="bi bi-x-circle me-2"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php include __DIR__ . '/shared/form_footer.php'; ?>

<!-- Converters & Handlers -->
<script src="/Credit/DAS/assets/js/preeti-converter.js?v=2.2"></script>
<script src="/Credit/DAS/assets/js/date-converter.js"></script>
<script src="../js/nepali_handler.js?v=2.2"></script>

<!-- NEW Text Handler (Text Inputs Only) -->
<script src="/Credit/DAS/assets/js/preeti-form-handler.js?v=1.1"></script>

<script>
// Prevent legacy handler from interfering with text inputs (but keep it for dates)
$(document).ready(function() {
    $(document).off('focus', '.nepali-input');
    $(document).off('blur', '.nepali-input');
    console.log("[Integration] Legacy text handler disabled (Date logic preserved).");
});
</script>

<script>
// Initialize multi-step form
const multiStepForm = new MultiStepForm('guarantorForm');

// Toggle reissue fields based on CTZ Reissued checkbox
function toggleReissueFields() {
    const checkbox = document.getElementById('ctz_reissued');
    const reissueDateField = document.getElementById('id_reissue_date');
    const reissueCountField = document.getElementById('reissue_count');
    
    if (checkbox && checkbox.checked) {
        reissueDateField.disabled = false;
        reissueCountField.disabled = false;
    } else {
        reissueDateField.disabled = true;
        reissueCountField.disabled = true;
        // Clear values when disabled
        reissueDateField.value = '';
        reissueCountField.value = '0';
    }
}

// Initialize reissue fields on page load
window.addEventListener('DOMContentLoaded', function() {
    toggleReissueFields();
    
    // Initialize saved address data (for edit mode)
    <?php if ($guarantor_id && !empty($guarantor_data)): ?>
    // Permanent Address
    const permProvince = '<?php echo $guarantor_data['perm_province'] ?? ''; ?>';
    const permDistrict = '<?php echo $guarantor_data['perm_district'] ?? ''; ?>';
    const permMunicipality = '<?php echo $guarantor_data['perm_municipality_vdc'] ?? ''; ?>';
    const permWard = '<?php echo $guarantor_data['perm_ward_no'] ?? ''; ?>';
    
    if (permProvince) {
        permLocation.setValues(permProvince, permDistrict, permMunicipality, permWard);
    }
    
    // Temporary Address
    const tempProvince = '<?php echo $guarantor_data['temp_province'] ?? ''; ?>';
    const tempDistrict = '<?php echo $guarantor_data['temp_district'] ?? ''; ?>';
    const tempMunicipality = '<?php echo $guarantor_data['temp_municipality_vdc'] ?? ''; ?>';
    const tempWard = '<?php echo $guarantor_data['temp_ward_no'] ?? ''; ?>';
    
    if (tempProvince) {
        tempLocation.setValues(tempProvince, tempDistrict, tempMunicipality, tempWard);
    }
    <?php endif; ?>
    
    // Load existing family members (for edit mode)
    <?php if ($guarantor_id): ?>
    fetch('../../api/customer_api.php?action=get_family_details&person_id=<?php echo $guarantor_id; ?>&person_type=Guarantor')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                // Clear the default empty row
                document.getElementById('familyContainer').innerHTML = '<h6 class="fw-bold mb-3 text-primary">Family Members / परिवारका सदस्यहरू</h6>';
                
                // Add each family member
                data.data.forEach(member => {
                    addFamilyRow(member.name, member.relation);
                });
            }
        })
        .catch(error => console.error('Error loading family details:', error));
    <?php endif; ?>
});

function addFamilyRow(name = '', relation = '') {
    const template = `
        <div class="dynamic-row row g-3 mb-3">
            <div class="col-md-5">
                <label class="form-label fw-semibold">Name / नाम</label>
                <input type="text" class="form-control nepali-input" name="family_name[]" value="${name}">
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold">Relation / नाता</label>
                <select class="form-select" name="family_relation[]">
                    <option value="">Select / छान्नुहोस्</option>
                    <option value="बुबा" ${relation == 'बुबा' ? 'selected' : ''}>बुबा</option>
                    <option value="आमा" ${relation == 'आमा' ? 'selected' : ''}>आमा</option>
                    <option value="बाजे" ${relation == 'बाजे' ? 'selected' : ''}>बाजे</option>
                    <option value="बज्यै" ${relation == 'बज्यै' ? 'selected' : ''}>बज्यै</option>
                    <option value="छोरा" ${relation == 'छोरा' ? 'selected' : ''}>छोरा</option>
                    <option value="छोरी" ${relation == 'छोरी' ? 'selected' : ''}>छोरी</option>
                    <option value="पति" ${relation == 'पति' ? 'selected' : ''}>पति</option>
                    <option value="पत्नी" ${relation == 'पत्नी' ? 'selected' : ''}>पत्नी</option>
                    <option value="ससुरा" ${relation == 'ससुरा' ? 'selected' : ''}>ससुरा</option>
                    <option value="सासु" ${relation == 'सासु' ? 'selected' : ''}>सासु</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">&nbsp;</label>
                <div class="d-grid">
                    <button type="button" class="btn btn-danger" onclick="removeDynamicRow(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    document.getElementById('familyContainer').insertAdjacentHTML('beforeend', template);
}

function removeDynamicRow(button) {
    button.closest('.dynamic-row').remove();
}

// Form submission handler
document.getElementById('guarantorForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validate form
    if (!this.checkValidity()) {
        e.stopPropagation();
        this.classList.add('was-validated');
        alert('Please fill all required fields correctly.');
        return;
    }
    
    if (typeof window.convertAllPreetiToUnicode === 'function') {
        window.convertAllPreetiToUnicode();
    }

    // EXPLICIT CONVERSION for Family Names (Extra safe)
    const familyInputs = this.querySelectorAll('input[name="family_name[]"]');
    familyInputs.forEach(input => {
        if (input.value && typeof PreetiConverterCore !== 'undefined') {
            let hasUnicode = false;
            for (let i = 0; i < input.value.length; i++) {
                if (input.value.charCodeAt(i) >= 2304 && input.value.charCodeAt(i) <= 2431) {
                    hasUnicode = true; break;
                }
            }
            if (!hasUnicode) {
                try {
                     input.value = PreetiConverterCore.toUnicode(input.value, 'preeti');
                     input.style.fontFamily = ''; 
                     input.style.fontSize = ''; 
                } catch(e) { console.error("Explicit family conversion failed", e); }
            }
        }
    });
    
    const formData = new FormData(this);
    formData.append('action', 'save_guarantor');
    formData.append('customer_profile_id', '<?php echo $profile_id; ?>');
    
    // Debug: Log form data
    console.log('Form data being sent:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + value);
    }

    fetch('/Credit/DAS/modules/api/customer_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('API Response:', data);
        if (data.success) {
            alert('Guarantor saved successfully!');
            // Redirect to customer profile page
            window.location.href = '../customer_profile.php?id=<?php echo $profile_id; ?>';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving.');
    });
});
</script>

<!-- Person Selector Modal -->
<?php include __DIR__ . '/../components/person_selector_modal.php'; ?>

<!-- Person Selector JavaScript -->
<script src="../js/person_selector.js"></script>

<!-- Auto-fill from Person Selector -->
<?php include __DIR__ . '/guarantor_autofill.php'; ?>

<!-- View-Only Mode Handler -->
<script src="../../../assets/js/view_only_mode.js"></script>
