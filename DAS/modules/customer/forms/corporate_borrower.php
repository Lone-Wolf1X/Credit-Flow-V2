<?php
// Corporate Borrower Form
// Extracted from borrower_form.php to ensure clean separation of logic and no JS errors

// Set page title
$pageTitle = 'Corporate Borrower Form';

// Include Header
include __DIR__ . '/shared/form_header.php';

// Ensure $borrower_data is available
if (!isset($borrower_data)) {
    $borrower_data = []; 
}
?>

<form id="borrowerForm" class="needs-validation" novalidate>
    <input type="hidden" name="borrower_type" value="Corporate">
    <?php if ($borrower_id): ?>
    <input type="hidden" name="borrower_id" value="<?php echo $borrower_id; ?>">
    <?php endif; ?>

    <!-- Progress Bar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="progress" style="height: 30px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: 33%;">Step 1 of 3</div>
            </div>
            <div class="d-flex justify-content-between mt-3">
                <span class="step-indicator active">
                    <i class="bi bi-building-fill"></i> Corporate Details
                </span>
                <span class="step-indicator">
                    <i class="bi bi-geo-alt"></i> Address
                </span>
                <span class="step-indicator">
                    <i class="bi bi-person-badge"></i> Authorized Person
                </span>
            </div>
        </div>
    </div>

    <!-- Step 1: Corporate Details -->
    <div class="form-step">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-building me-2"></i>Step 1: Corporate Details / संस्थागत विवरण</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Company Name / कम्पनीको नाम <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="company_name" value="<?php echo $borrower_data['company_name'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Registration No / दर्ता नं <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="registration_no" value="<?php echo $borrower_data['registration_no'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Registration Date / दर्ता मिति</label>
                        <input type="text" class="form-control nepali-date-picker" name="registration_date" placeholder="YYYY-MM-DD" value="<?php echo $borrower_data['registration_date'] ?? ''; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Registration Type / दर्ता प्रकार</label>
                        <select class="form-select" name="registration_type">
                            <option value="">Select / छान्नुहोस्</option>
                            <option value="Proprietorship" <?php echo ($borrower_data['registration_type'] ?? '') == 'Proprietorship' ? 'selected' : ''; ?>>Proprietorship / एकल स्वामित्व</option>
                            <option value="Partnership" <?php echo ($borrower_data['registration_type'] ?? '') == 'Partnership' ? 'selected' : ''; ?>>Partnership / साझेदारी</option>
                            <option value="Private Limited" <?php echo ($borrower_data['registration_type'] ?? '') == 'Private Limited' ? 'selected' : ''; ?>>Private Limited / प्रा.लि.</option>
                            <option value="Public Limited" <?php echo ($borrower_data['registration_type'] ?? '') == 'Public Limited' ? 'selected' : ''; ?>>Public Limited / पब्लिक लिमिटेड</option>
                            <option value="Cooperative" <?php echo ($borrower_data['registration_type'] ?? '') == 'Cooperative' ? 'selected' : ''; ?>>Cooperative / सहकारी</option>
                            <option value="NGO/INGO" <?php echo ($borrower_data['registration_type'] ?? '') == 'NGO/INGO' ? 'selected' : ''; ?>>NGO/INGO / गैर सरकारी संस्था</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">PAN Number / प्यान नं</label>
                        <input type="text" class="form-control" name="pan_number" value="<?php echo $borrower_data['pan_number'] ?? ''; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">PAN Issue Date / प्यान जारी मिति</label>
                        <input type="text" class="form-control nepali-date-picker" name="pan_issue_date" placeholder="YYYY-MM-DD" value="<?php echo $borrower_data['pan_issue_date'] ?? ''; ?>">
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
                $data = $borrower_data;
                $addressLabels = [
                    'permanent' => 'Registered Address / दर्ता भएको ठेगाना',
                    'temporary' => 'Branch/Contact Address / शाखा/सम्पर्क ठेगाना',
                    'sameAs' => 'Same as Registered Address / दर्ता भएको ठेगाना जस्तै'
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

    <!-- Step 3: Authorized Person -->
    <div class="form-step" style="display: none;">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Step 3: Authorized Person / अधिकृत व्यक्ति</h5>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Required:</strong> At least one authorized person must be added before saving.
                    <br>
                    <strong>आवश्यक:</strong> सुरक्षित गर्नु अघि कम्तिमा एक अधिकृत व्यक्ति थप्नुपर्छ।
                </div>
                
                <div id="authorizedPersonsContainer">
                    <h6 class="fw-bold mb-3 text-primary">Authorized Person Details / अधिकृत व्यक्ति विवरण</h6>
                    
                    <!-- First Authorized Person (Required) -->
                    <div class="authorized-person-form border rounded p-3 mb-3" data-person-index="0">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-secondary">Authorized Person #1 (Required)</h6>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Full Name (English) / पूरा नाम (अंग्रेजी) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="auth_person_name_en[]" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Full Name (Nepali) / पूरा नाम (नेपाली)</label>
                                <input type="text" class="form-control preeti-font" name="auth_person_name_np[]" style="font-size: 1.2rem;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Date of Birth / जन्म मिति <span class="text-danger">*</span></label>
                                <input type="text" class="form-control nepali-date-picker" name="auth_person_dob[]" placeholder="YYYY-MM-DD" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Gender / लिङ्ग <span class="text-danger">*</span></label>
                                <select class="form-select" name="auth_person_gender[]" required>
                                    <option value="">Select / छान्नुहोस्</option>
                                    <option value="Male">Male / पुरुष</option>
                                    <option value="Female">Female / महिला</option>
                                    <option value="Other">Other / अन्य</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Citizenship Number / नागरिकता नं <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="auth_person_citizenship[]" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">ID Issue Date / जारी मिति</label>
                                <input type="text" class="form-control nepali-date-picker" name="auth_person_id_issue_date[]" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">ID Issue District / जारी जिल्ला</label>
                                <input type="text" class="form-control" name="auth_person_id_issue_district[]">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Contact Number / सम्पर्क नं <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="auth_person_contact[]" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Email / इमेल</label>
                                <input type="email" class="form-control" name="auth_person_email[]">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Designation / पद <span class="text-danger">*</span></label>
                                <select class="form-select" name="auth_person_designation[]" required>
                                    <option value="">Select / छान्नुहोस्</option>
                                    <option value="Director">Director / निर्देशक</option>
                                    <option value="Managing Director">Managing Director / प्रबन्ध निर्देशक</option>
                                    <option value="Chairman">Chairman / अध्यक्ष</option>
                                    <option value="CEO">CEO / प्रमुख कार्यकारी अधिकृत</option>
                                    <option value="Partner">Partner / साझेदार</option>
                                    <option value="Proprietor">Proprietor / स्वामित्व</option>
                                    <option value="Authorized Signatory">Authorized Signatory / अधिकृत हस्ताक्षरकर्ता</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Relationship Status / वैवाहिक स्थिति</label>
                                <select class="form-select" name="auth_person_marital_status[]">
                                    <option value="">Select / छान्नुहोस्</option>
                                    <option value="Single">Single / अविवाहित</option>
                                    <option value="Married">Married / विवाहित</option>
                                    <option value="Divorced">Divorced / सम्बन्ध विच्छेद</option>
                                    <option value="Widowed">Widowed / विधवा/विधुर</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Father's Name / बुबाको नाम</label>
                                <input type="text" class="form-control" name="auth_person_father_name[]">
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-success mb-4" onclick="addAuthorizedPerson()">
                    <i class="bi bi-plus-circle me-2"></i>Add Another Authorized Person
                </button>
                
                <div class="mt-4">
                    <button type="button" class="btn btn-secondary btn-lg px-5 me-2 btn-prev">
                        <i class="bi bi-arrow-left me-2"></i> Previous
                    </button>
                    <button type="submit" class="btn btn-success btn-lg px-5">
                        <i class="bi bi-check-circle me-2"></i> Save Borrower
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

<script>
// Initialize multi-step form for Corporate
const multiStepForm = new MultiStepForm('borrowerForm');

// Counter for authorized persons
let authorizedPersonCount = 1;

// Function to add another authorized person
function addAuthorizedPerson() {
    authorizedPersonCount++;
    const container = document.getElementById('authorizedPersonsContainer');
    
    const personHtml = `
        <div class="authorized-person-form border rounded p-3 mb-3" data-person-index="${authorizedPersonCount}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-secondary">Authorized Person #${authorizedPersonCount}</h6>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeAuthorizedPerson(this)">
                    <i class="bi bi-trash"></i> Remove
                </button>
            </div>
            
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Full Name (English) / पूरा नाम (अंग्रेजी) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="auth_person_name_en[]" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Full Name (Nepali) / पूरा नाम (नेपाली)</label>
                    <input type="text" class="form-control preeti-font" name="auth_person_name_np[]" style="font-size: 1.2rem;">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Date of Birth / जन्म मिति <span class="text-danger">*</span></label>
                    <input type="text" class="form-control nepali-date-picker" name="auth_person_dob[]" placeholder="YYYY-MM-DD" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Gender / लिङ्ग <span class="text-danger">*</span></label>
                    <select class="form-select" name="auth_person_gender[]" required>
                        <option value="">Select / छान्नुहोस्</option>
                        <option value="Male">Male / पुरुष</option>
                        <option value="Female">Female / महिला</option>
                        <option value="Other">Other / अन्य</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Citizenship Number / नागरिकता नं <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="auth_person_citizenship[]" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">ID Issue Date / जारी मिति</label>
                    <input type="text" class="form-control nepali-date-picker" name="auth_person_id_issue_date[]" placeholder="YYYY-MM-DD">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">ID Issue District / जारी जिल्ला</label>
                    <input type="text" class="form-control" name="auth_person_id_issue_district[]">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Contact Number / सम्पर्क नं <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="auth_person_contact[]" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Email / इमेल</label>
                    <input type="email" class="form-control" name="auth_person_email[]">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Designation / पद <span class="text-danger">*</span></label>
                    <select class="form-select" name="auth_person_designation[]" required>
                        <option value="">Select / छान्नुहोस्</option>
                        <option value="Director">Director / निर्देशक</option>
                        <option value="Managing Director">Managing Director / प्रबन्ध निर्देशक</option>
                        <option value="Chairman">Chairman / अध्यक्ष</option>
                        <option value="CEO">CEO / प्रमुख कार्यकारी अधिकृत</option>
                        <option value="Partner">Partner / साझेदार</option>
                        <option value="Proprietor">Proprietor / स्वामित्व</option>
                        <option value="Authorized Signatory">Authorized Signatory / अधिकृत हस्ताक्षरकर्ता</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Relationship Status / वैवाहिक स्थिति</label>
                    <select class="form-select" name="auth_person_marital_status[]">
                        <option value="">Select / छान्नुहोस्</option>
                        <option value="Single">Single / अविवाहित</option>
                        <option value="Married">Married / विवाहित</option>
                        <option value="Divorced">Divorced / सम्बन्ध विच्छेद</option>
                        <option value="Widowed">Widowed / विधवा/विधुर</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Father's Name / बुबाको नाम</label>
                    <input type="text" class="form-control" name="auth_person_father_name[]">
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', personHtml);
    
    // Re-initialize date pickers for newly added fields
    $('.nepali-date-picker').nepaliDatePicker({
        dateFormat: 'YYYY-MM-DD',
        closeOnDateSelect: true,
        ndpYear: true,
        ndpMonth: true,
        ndpYearCount: 10
    });
}

// Function to remove an authorized person
function removeAuthorizedPerson(button) {
    const personForm = button.closest('.authorized-person-form');
    personForm.remove();
    authorizedPersonCount--;
}

// Form submission handler
document.getElementById('borrowerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!this.checkValidity()) {
        e.stopPropagation();
        this.classList.add('was-validated');
        return;
    }

    const formData = new FormData(this);
    formData.append('action', 'save_borrower');
    formData.append('customer_profile_id', '<?php echo $profile_id; ?>');

    fetch('../../api/customer_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Corporate Borrower saved successfully!');
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

<!-- View-Only Mode Handler -->
<script src="../../../assets/js/view_only_mode.js"></script>
