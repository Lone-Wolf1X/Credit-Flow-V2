<?php
session_start();
require_once '../../../config/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../../login.php');
    exit;
}

$profile_id = $_GET['profile_id'] ?? '';
$limit_id = $_GET['id'] ?? null;
$view_mode = isset($_GET['view_mode']) && $_GET['view_mode'] == '1';

// Force Checker to View Mode
if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Checker') {
    $view_mode = true;
}

if (empty($profile_id)) {
    header('Location: ../create_customer.php');
    exit;
}

$limit_data = null;
if ($limit_id) {
    $stmt = $conn->prepare("SELECT * FROM limit_details WHERE id = ?");
    $stmt->bind_param("i", $limit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $limit_data = $result->fetch_assoc();
}

// Fetch Profile Metadata (Application Type & Parent)
$stmt = $conn->prepare("SELECT application_type, parent_profile_id FROM customer_profiles WHERE id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$prof_res = $stmt->get_result();
$prof_data = $prof_res->fetch_assoc();

$app_type = $prof_data['application_type'] ?? 'New';
$parent_id = $prof_data['parent_profile_id'] ?? null;
$is_comparative = ($app_type === 'Enhancement' || $app_type === 'Reduction') && $parent_id;

$parent_limit_amount = '';
if ($is_comparative && $limit_data) {
    // Attempt to find matching limit in parent profile by loan_type
    // Note: ideally we link by ID, but since it's a new row, we match by type
    $l_type = $limit_data['loan_type'];
    $stmt = $conn->prepare("SELECT amount FROM limit_details WHERE customer_profile_id = ? AND loan_type = ? LIMIT 1");
    $stmt->bind_param("is", $parent_id, $l_type);
    $stmt->execute();
    $p_res = $stmt->get_result();
    if ($p_row = $p_res->fetch_assoc()) {
        $parent_limit_amount = $p_row['amount'];
    }
}


function toNepaliNumber($num) {
    if (is_null($num) || $num === '') return '';
    $standard_num = (string)$num;
    $nepali_digits = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
    $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($english_digits, $nepali_digits, $standard_num);
}

$pageTitle = ($view_mode ? 'View' : ($limit_id ? 'Edit' : 'Add')) . ' Limit Details';
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
                <i class="bi bi-bank me-2"></i><?php echo $limit_id ? 'Edit' : 'Add'; ?> Limit Details / सीमा विवरण
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../customer_profile.php?id=<?php echo $profile_id; ?>">Customer Profile</a></li>
                    <li class="breadcrumb-item active"><?php echo $limit_id ? 'Edit' : 'Add'; ?> Limit</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <form id="limitForm">
                <input type="hidden" name="customer_profile_id" value="<?php echo $profile_id; ?>">
                <?php if ($limit_id): ?>
                <input type="hidden" name="limit_id" value="<?php echo $limit_id; ?>">
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-bank me-2"></i>Limit Details / बैंकिङ सुविधा सीमा</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Loan Type / रण प्रकार <span class="text-danger">*</span></label>
                                <input type="text" class="form-control nepali-input" name="loan_type" value="<?php echo $limit_data['loan_type'] ?? ''; ?>" required>
                            </div>

                            <?php if ($is_comparative): ?>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold text-muted">Existing Limit / हालको सीमा</label>
                                <input type="text" class="form-control bg-light" value="<?php echo toNepaliNumber($parent_limit_amount); ?>" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Proposed Limit / प्रस्तावित सीमा</label>
                                <input type="text" class="form-control nepali-input" name="amount" value="<?php echo toNepaliNumber($limit_data['amount'] ?? ''); ?>" required>
                            </div>
                            <?php else: ?>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Amount / रकम <span class="text-danger">*</span></label>
                                <input type="text" class="form-control nepali-input" name="amount" value="<?php echo toNepaliNumber($limit_data['amount'] ?? ''); ?>" required>
                            </div>
                            <?php endif; ?>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Amount in Words / रकम अक्षरमा</label>
                                <input type="text" class="form-control nepali-input" name="amount_in_words" value="<?php echo $limit_data['amount_in_words'] ?? ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Tenure (Months) / अवधि (महिना)</label>
                                <input type="text" class="form-control nepali-input" name="tenure" value="<?php echo toNepaliNumber($limit_data['tenure'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Interest Rate (%) / ब्याज दर</label>
                                <input type="text" class="form-control nepali-input" name="interest_rate" value="<?php echo toNepaliNumber($limit_data['interest_rate'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Base Rate (%) / आधार दर</label>
                                <input type="text" class="form-control nepali-input" name="base_rate" value="<?php echo toNepaliNumber($limit_data['base_rate'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Premium (%) / प्रिमियम</label>
                                <input type="text" class="form-control nepali-input" name="premium" value="<?php echo toNepaliNumber($limit_data['premium'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="bi bi-check-circle me-2"></i> Save Limit
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

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="/Credit/DAS/assets/css/preeti-font.css">
<script src="/Credit/DAS/assets/js/date-converter.js"></script>
<script src="/Credit/DAS/assets/js/preeti-converter.js?v=2.2"></script>
<script src="/Credit/DAS/modules/customer/js/nepali_handler.js?v=2.2"></script>
<script>
// Function to convert Nepali digits to English digits
function convertNepaliToEnglish(str) {
    if (!str) return str;
    const nepaliMap = {
        '०': '0', '१': '1', '२': '2', '३': '3', '४': '4',
        '५': '5', '६': '6', '७': '7', '८': '8', '९': '9'
    };
    return str.toString().split('').map(char => nepaliMap[char] || char).join('');
}

document.getElementById('limitForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Convert all Preeti inputs to Unicode before submission
    if (typeof window.convertAllPreetiToUnicode === 'function') {
        window.convertAllPreetiToUnicode();
    }
    
    const formData = new FormData(this);
    
    // Convert specific fields
    const fieldsToConvert = ['amount', 'tenure', 'interest_rate', 'base_rate', 'premium'];
    fieldsToConvert.forEach(field => {
        const val = formData.get(field);
        if (val) {
            formData.set(field, convertNepaliToEnglish(val));
        }
    });

    formData.append('action', 'save_limit');
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Saving...';
    
    fetch('../../api/customer_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Limit details saved successfully!');
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
</script>

<script src="../../../assets/js/view_only_mode.js"></script>

<?php
$mainContent = ob_get_clean();
$assetPath = '../../../asstes';
include '../../../Layout/layout_new.php';
?>
