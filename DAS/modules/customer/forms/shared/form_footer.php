        </main>
    </div>
</div>

<?php
// Capture the main content
$contentBuffer = ob_get_clean();

// Add scripts to the content (or use a layout variable if available)
ob_start();
?>
<!-- jQuery (Required for Nepali Date Picker) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Nepali Date Picker JS -->
<script src="https://cdn.jsdelivr.net/npm/@sajanm/nepali-date-picker@latest/dist/nepali.datepicker.v5.0.6.min.js"></script>

<!-- Custom JS -->
<!-- Note: Relative path adjustment for scripts relative to URL -->
<script src="../../../assets/js/location_cascade.js?v=<?= time() ?>"></script>
<script src="../../../assets/js/multistep_form.js"></script>
<script src="../../../assets/js/view_only_mode.js"></script>

<script>
// Initialize Nepali Date Picker
window.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('.nepali-date-picker');
    dateInputs.forEach(input => {
        $(input).nepaliDatePicker({
            dateFormat: 'YYYY-MM-DD',
            closeOnDateSelect: true,
            ndpYear: true,
            ndpMonth: true,
            ndpYearCount: 10
        });
    });
});

// Initialize location cascades
// Permanent only needs Province/District now as Mun/Ward are text
const permLocation = new LocationCascade('perm_province', 'perm_district', null, null);
const tempLocation = new LocationCascade('temp_province', 'temp_district', 'temp_municipality_vdc', 'temp_ward_no');

// Update hidden field when select value changes
function updateHiddenField(fieldName, value) {
    const hiddenField = document.getElementById(fieldName + '_hidden');
    if (hiddenField) {
        hiddenField.value = value;
        console.log('Updated hidden field:', fieldName, '=', value);
    }
}

// Logic for Permanent Text Inputs (Municipality & Ward)
// Since they have 'name' directly, we don't need hidden fields, 
// BUT we need to sync them to Temporary if 'Same as' is checked.
$(document).on('input blur', '#perm_municipality_vdc, #perm_ward_no', function() {
    if (document.getElementById('sameAsPermanent').checked) {
        syncSameAsPermanent();
    }
});

function toggleSameAddress() {
    const isChecked = document.getElementById('sameAsPermanent').checked;
    const tempMunSelect = document.getElementById('temp_municipality_container');
    const tempMunText = document.getElementById('temp_municipality_vdc_text');
    const tempWardSelect = document.getElementById('temp_ward_container');
    const tempWardText = document.getElementById('temp_ward_no_text');

    if (isChecked) {
        // Show text inputs, hide dropdowns
        tempMunSelect.classList.add('d-none');
        tempMunText.classList.remove('d-none');
        tempWardSelect.classList.add('d-none');
        tempWardText.classList.remove('d-none');
        
        // Disable dropdowns to be safe
        document.getElementById('temp_municipality_vdc').disabled = true;
        document.getElementById('temp_ward_no').disabled = true;

        syncSameAsPermanent();
    } else {
        // Hide text inputs, show dropdowns
        tempMunSelect.classList.remove('d-none');
        tempMunText.classList.add('d-none');
        tempWardSelect.classList.remove('d-none');
        tempWardText.classList.add('d-none');
        
        // Let cascade handle enabling if district is selected
        if (document.getElementById('temp_district').value) {
            document.getElementById('temp_municipality_vdc').disabled = false;
        }
    }
}

function syncSameAsPermanent() {
    if (!document.getElementById('sameAsPermanent').checked) return;

    // 1. Copy simple select fields (Province, District)
    const prov = document.getElementById('perm_province').value;
    const dist = document.getElementById('perm_district').value;
    
    const tempProv = document.getElementById('temp_province');
    const tempDist = document.getElementById('temp_district');

    if (tempProv.value !== prov) {
        tempProv.value = prov;
        tempProv.dispatchEvent(new Event('change'));
    }
    
    // We might need to wait for districts to load if not already there
    // But for "Same As", we can forcefully set and update hidden fields
    setTimeout(() => {
        if (tempDist.value !== dist) {
            tempDist.value = dist;
            tempDist.dispatchEvent(new Event('change'));
        }
    }, 100);

    // 2. Copy Text Inputs
    const permMun = document.getElementById('perm_municipality_vdc').value;
    const permWard = document.getElementById('perm_ward_no').value;
    const permStreet = document.querySelector('[name="perm_street_name"]').value;

    document.getElementById('temp_municipality_vdc_text').value = permMun;
    document.getElementById('temp_ward_no_text').value = permWard;
    document.getElementById('temp_street_name').value = permStreet;

    // 3. Update Hidden Fields (Critical for backend)
    updateHiddenField('temp_municipality_vdc', permMun);
    updateHiddenField('temp_ward_no', permWard);
}

// Ensure text inputs also update hidden fields when manually typed (for Temporary)
$(document).on('input blur', '#temp_municipality_vdc_text', function() {
    updateHiddenField('temp_municipality_vdc', this.value);
});
$(document).on('input blur', '#temp_ward_no_text', function() {
    updateHiddenField('temp_ward_no', this.value);
});

// Initial trigger for Same as Permanent (Edit Mode)
window.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        if (document.getElementById('sameAsPermanent') && document.getElementById('sameAsPermanent').checked) {
            toggleSameAddress();
        }
    }, 500); // Wait for location cascades to start loading
});
</script>
<?php
$extraScripts = ob_get_clean();

// Combine content and scripts
$mainContent = $contentBuffer . $extraScripts;

// User session data for layout (ensure keys match what layout expects)
// Assuming session is already started in form_router/proxy
$userName = $_SESSION['full_name'] ?? 'User';
$userAvatar = '../../../assets/images/img_avatar.png'; // Relative to form location
$badgeText = $_SESSION['role_name'] ?? 'User';
$activeMenu = 'customer'; // active menu item

// Set asset path for layout (forms are 3 levels deep: modules/customer/forms/)
$assetPath = '../../../assets';

// Ensure page title is set if not already defined
if (!isset($pageTitle)) {
    $pageTitle = 'Form';
}

// Include the layout
include dirname(__DIR__, 4) . '/Layout/layout_new.php';
?>
