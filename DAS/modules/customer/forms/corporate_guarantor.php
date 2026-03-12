<?php
// Corporate Guarantor Form
// Extracted from guarantor_form.php

// Set page title
$pageTitle = 'Corporate Guarantor Form';

// Include Header
include __DIR__ . '/shared/form_header.php';

// Ensure $guarantor_data is available
if (!isset($guarantor_data)) {
    $guarantor_data = []; 
}
?>

<form id="guarantorForm" class="needs-validation" novalidate>
    <input type="hidden" name="guarantor_type" value="Corporate">
    <input type="hidden" name="customer_profile_id" value="<?php echo $profile_id; ?>">
    <?php if ($guarantor_id): ?>
    <input type="hidden" name="guarantor_id" value="<?php echo $guarantor_id; ?>">
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

    <!-- Step 1: Corporate Guarantor Details -->
    <div class="form-step">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-building me-2"></i>Step 1: Corporate Guarantor Details / संस्थागत विवरण</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Company Name / कम्पनीको नाम <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="company_name" value="<?php echo $guarantor_data['company_name'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Registration No / दर्ता नं <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="registration_no" value="<?php echo $guarantor_data['registration_no'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Registration Date / दर्ता मिति</label>
                        <input type="text" class="form-control nepali-date-picker" name="registration_date" placeholder="YYYY-MM-DD" value="<?php echo $guarantor_data['registration_date'] ?? ''; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Registration Type / दर्ता प्रकार</label>
                        <select class="form-select" name="registration_type">
                            <option value="">Select / छान्नुहोस्</option>
                            <option value="Proprietorship" <?php echo ($guarantor_data['registration_type'] ?? '') == 'Proprietorship' ? 'selected' : ''; ?>>Proprietorship / एकल स्वामित्व</option>
                            <option value="Partnership" <?php echo ($guarantor_data['registration_type'] ?? '') == 'Partnership' ? 'selected' : ''; ?>>Partnership / साझेदारी</option>
                            <option value="Private Limited" <?php echo ($guarantor_data['registration_type'] ?? '') == 'Private Limited' ? 'selected' : ''; ?>>Private Limited / प्रा.लि.</option>
                            <option value="Public Limited" <?php echo ($guarantor_data['registration_type'] ?? '') == 'Public Limited' ? 'selected' : ''; ?>>Public Limited / पब्लिक लिमिटेड</option>
                            <option value="Cooperative" <?php echo ($guarantor_data['registration_type'] ?? '') == 'Cooperative' ? 'selected' : ''; ?>>Cooperative / सहकारी</option>
                            <option value="NGO/INGO" <?php echo ($guarantor_data['registration_type'] ?? '') == 'NGO/INGO' ? 'selected' : ''; ?>>NGO/INGO / गैर सरकारी संस्था</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">PAN Number / प्यान नं</label>
                        <input type="text" class="form-control" name="pan_number" value="<?php echo $guarantor_data['pan_number'] ?? ''; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">PAN Issue Date / प्यान जारी मिति</label>
                        <input type="text" class="form-control nepali-date-picker" name="pan_issue_date" placeholder="YYYY-MM-DD" value="<?php echo $guarantor_data['pan_issue_date'] ?? ''; ?>">
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
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Review Authorized Persons / अधिकृत व्यक्तिहरू</label>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i> Please add detailed information about Authorized Persons after saving.
                            <br><br>
                            कृपया यहाँ आधारभूत विवरण सुरक्षित गरेपछि अधिकृत व्यक्तिहरू बारे विस्तृत जानकारी थप्नुहोस्।
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Contact Person Name / सम्पर्क व्यक्तिको नाम</label>
                        <input type="text" class="form-control" name="contact_person" value="<?php echo $guarantor_data['contact_person'] ?? ''; ?>">
                    </div>
                     <div class="col-md-4">
                        <label class="form-label fw-semibold">Contact Number / सम्पर्क नं</label>
                        <input type="text" class="form-control" name="contact_number" value="<?php echo $guarantor_data['contact_number'] ?? ''; ?>">
                    </div>
                </div>
                
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

<script>
// Initialize multi-step form for Corporate
const multiStepForm = new MultiStepForm('guarantorForm');

// Form submission handler
document.getElementById('guarantorForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!this.checkValidity()) {
        e.stopPropagation();
        this.classList.add('was-validated');
        return;
    }

    const formData = new FormData(this);
    formData.append('action', 'save_guarantor');
    formData.append('customer_profile_id', '<?php echo $profile_id; ?>');

    fetch('../../api/customer_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Corporate Guarantor saved successfully!');
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
