<?php
session_start();
require_once '../../../config/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../../login.php');
    exit;
}

$profile_id = $_GET['profile_id'] ?? '';
$loan_id = $_GET['id'] ?? null;
$view_mode = isset($_GET['view_mode']) && $_GET['view_mode'] == '1';

// Force Checker to View Mode
if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Checker') {
    $view_mode = true;
}

if (empty($profile_id)) {
    header('Location: ../create_customer.php');
    exit;
}

$loan_data = null;
if ($loan_id) {
    $stmt = $conn->prepare("SELECT * FROM loan_details WHERE id = ?");
    $stmt->bind_param("i", $loan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $loan_data = $result->fetch_assoc();
}

// Note: Loan scheme is now a plain text field (loan_scheme_name) instead of dropdown
// No need to fetch schemes from database


$pageTitle = ($view_mode ? 'View' : ($loan_id ? 'Edit' : 'Add')) . ' Loan Details';
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
                <i class="bi bi-bank me-2"></i><?php echo $loan_id ? 'Edit' : 'Add'; ?> Loan Details / ऋण विवरण
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../customer_profile.php?id=<?php echo $profile_id; ?>">Customer Profile</a></li>
                    <li class="breadcrumb-item active"><?php echo $loan_id ? 'Edit' : 'Add'; ?> Loan</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <form id="loanForm">
                <input type="hidden" name="customer_profile_id" value="<?php echo $profile_id; ?>">
                <?php if ($loan_id): ?>
                <input type="hidden" name="loan_id" value="<?php echo $loan_id; ?>">
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-bank me-2"></i>Loan Details / ऋण विवरण</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Loan Type / रण प्रकार <span class="text-danger">*</span></label>
                                <select class="form-select" name="loan_type" required>
                                    <option value="">Select / छान्नुहोस्</option>
                                    <option value="New" <?php echo ($loan_data['loan_type'] ?? '') == 'New' ? 'selected' : ''; ?>>New / नयाँ</option>
                                    <option value="Renewal" <?php echo ($loan_data['loan_type'] ?? '') == 'Renewal' ? 'selected' : ''; ?>>Renewal / नवीकरण</option>
                                    <option value="Enhancement" <?php echo ($loan_data['loan_type'] ?? '') == 'Enhancement' ? 'selected' : ''; ?>>Enhancement / वृद्धि</option>
                                    <option value="Reduction" <?php echo ($loan_data['loan_type'] ?? '') == 'Reduction' ? 'selected' : ''; ?>>Reduction / कटौती</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Loan Approved Date (BS) / स्वीकृत मिति <span class="text-danger">*</span></label>
                                <input type="text" class="form-control smart-nepali-num" id="loan_approved_date" name="loan_approved_date" placeholder="2081-10-15" value="<?php echo $loan_data['loan_approved_date'] ?? ''; ?>" required>
                                <small class="text-muted">BS (e.g. 2081-10-15)</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Loan Approved Date (AD) / अंग्रेजी मिति</label>
                                <input type="text" class="form-control smart-nepali-num" id="loan_approved_date_ad" name="loan_approved_date_ad" placeholder="YYYY-MM-DD">
                                <small class="text-muted">Auto-converts to/from BS</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Approval Ref No / स्वीकृति सन्दर्भ नं</label>
                                <input type="text" class="form-control nepali-input" name="approval_ref_no" placeholder="LSL/2080/001" value="<?php echo $loan_data['approval_ref_no'] ?? ''; ?>">
                                <small class="text-muted">Type in Preeti (Converts to Unicode)</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Loan Scheme Name / योजनाको नाम</label>
                                <input type="text" class="form-control" name="loan_scheme_name" value="<?php echo $loan_data['loan_scheme_name'] ?? ''; ?>" placeholder="Enter Scheme Name">
                            </div>
                            
                            <div class="col-md-9">
                                <label class="form-label fw-semibold">Loan Purpose / ऋणको उद्देश्य</label>
                                <textarea class="form-control" name="loan_purpose" rows="1" placeholder="Describe the purpose..."><?php echo $loan_data['loan_purpose'] ?? ''; ?></textarea>
                            </div>
                         
                            <div class="col-12">
                                <label class="form-label fw-semibold">Remarks / कैफियत</label>
                                <textarea class="form-control" name="remarks" rows="2"><?php echo $loan_data['remarks'] ?? ''; ?></textarea>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="bi bi-check-circle me-2"></i> Save Loan
                            </button>
                            <a href="../customer_profile.php?id=<?php echo $profile_id; ?>" class="btn btn-secondary btn-lg px-5 ms-2">
                                <i class="bi bi-x-circle me-2"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Preeti Font -->
<link rel="stylesheet" href="/Credit/DAS/assets/css/preeti-font.css">
<!-- Preeti Converter Core -->
<script src="/Credit/DAS/assets/js/preeti-converter.js?v=2.2"></script>
<!-- Nepali Date Converter -->
<script src="/Credit/DAS/assets/js/nepali-date-converter.js?v=1.0"></script>
<!-- Standard Preeti Form Handler (Matches Borrower Form) -->
<script src="/Credit/DAS/assets/js/preeti-form-handler.js?v=1.1"></script>

<!-- Custom Smart Converter JS -->
<script>
// Prevent legacy handler from interfering (Matches Borrower Form)
$(document).ready(function() {
    $(document).off('focus', '.nepali-input');
    $(document).off('blur', '.nepali-input');
    console.log("[Integration] Legacy text handler disabled (Loan Form).");
});

document.addEventListener('DOMContentLoaded', function() {
    
    // Helper: Nepali Digits -> English Digits
    const toEnglishDigits = (str) => {
        if (!str) return str;
        const map = {'०':'0','१':'1','२':'2','३':'3','४':'4','५':'5','६':'6','७':'7','८':'8','९':'9'};
        return str.replace(/[०-९]/g, m => map[m]);
    };

    // Helper: English Digits -> Nepali Digits
    const toNepaliDigits = (str) => {
        if (!str) return str;
        const map = {'0':'०','1':'१','2':'२','3':'३','4':'४','5':'५','6':'६','7':'७','8':'८','9':'९'};
        return str.toString().replace(/[0-9]/g, m => map[m]);
    };

    // SMART CONVERTER: English Digits -> Nepali Digits (Preserves everything else)
    const convertToSmartNepali = (input) => {
        const val = input.value;
        if (!val) return;
        const converted = toNepaliDigits(val);
        if (val !== converted) {
            input.value = converted;
            input.style.borderColor = '#198754';
            setTimeout(() => input.style.borderColor = '', 500);
        }
    };
    
    // Apply Smart Converter to target fields
    const smartInputs = document.querySelectorAll('.smart-nepali-num');
    smartInputs.forEach(input => {
        input.addEventListener('blur', function() {
            convertToSmartNepali(this);
            // Trigger Sync based on ID
            if(this.id === 'loan_approved_date') syncBsToAd(this.value);
            if(this.id === 'loan_approved_date_ad') syncAdToBs(this.value);
        });
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                convertToSmartNepali(this);
                if(this.id === 'loan_approved_date') syncBsToAd(this.value);
                if(this.id === 'loan_approved_date_ad') syncAdToBs(this.value);
            }
        });
    });

    // --- BIDIRECTIONAL DATE SYNC ---
    const bsInput = document.getElementById('loan_approved_date');
    const adInput = document.getElementById('loan_approved_date_ad');

    // IS Initial Load: If BS exists, convert to AD
    if (bsInput.value) {
        // Convert existing BS (Nepali Digits) to English for calculator
        const bsDateEn = toEnglishDigits(bsInput.value);
        if (typeof NepaliDateConverter !== 'undefined') {
             const adDate = NepaliDateConverter.toAD(bsDateEn);
             if (adDate) adInput.value = toNepaliDigits(adDate); // Display in Nepali
        }
    }

    // 1. BS -> AD
    function syncBsToAd(bsVal) {
        if (!bsVal) { adInput.value = ''; return; }
        // Ensure accurate conversion basis (English Digits)
        const bsEn = toEnglishDigits(bsVal); 
        if (typeof NepaliDateConverter !== 'undefined') {
            const ad = NepaliDateConverter.toAD(bsEn);
            if (ad) adInput.value = toNepaliDigits(ad); // Display in Nepali
        }
    }

    // 2. AD -> BS
    function syncAdToBs(adVal) {
        if (!adVal) { bsInput.value = ''; return; }
        
        // Input is now Nepali Digits (due to smart-nepali-num class). Convert to English for Calc.
        const adEn = toEnglishDigits(adVal);
        
        // Validate format YYYY-MM-DD roughly
        if(/^\d{4}-\d{2}-\d{2}$/.test(adEn)) {
            if (typeof NepaliDateConverter !== 'undefined') {
                const bs = NepaliDateConverter.toBS(adEn);
                if (bs) {
                    // Convert resulting English BS date to Nepali Digits for display
                    bsInput.value = toNepaliDigits(bs);
                }
            }
        }
    }

    // Form Submission Handler
    document.getElementById('loanForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        // Final conversions
        // 1. Process Smart Inputs (Date) -> Convert Nepali Digits back to English for DB (YYYY-MM-DD)
        smartInputs.forEach(input => {
            input.value = toEnglishDigits(input.value);
        });
        
        // 2. Process Preeti Inputs (Ref No) -> Explicitly convert if missed
        const nepaliInputs = document.querySelectorAll('.nepali-input');
        nepaliInputs.forEach(input => {
            if (input.value && typeof PreetiConverterCore !== 'undefined') {
                try {
                     if (!/[\u0900-\u097F]/.test(input.value)) {
                         input.value = PreetiConverterCore.toUnicode(input.value, 'preeti');
                     }
                } catch(e) {}
            }
             input.style.fontFamily = ''; 
             input.style.fontSize = '';
        });

        const formData = new FormData(this);
        formData.append('action', 'save_loan');
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Saving...';
        
        fetch('../../api/customer_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Loan details saved successfully!');
                window.location.href = '../customer_profile.php?id=<?php echo $profile_id; ?>';
            } else {
                alert('Error: ' + data.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
});
</script>

<script src="../../../assets/js/view_only_mode.js"></script>

<?php
$mainContent = ob_get_clean();
$assetPath = '../../../asstes';
include '../../../Layout/layout_new.php';
?>
