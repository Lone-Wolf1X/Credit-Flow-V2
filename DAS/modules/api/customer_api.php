<?php
/**
 * Customer Management API
 * Handles all customer profile related operations
 */

// Start output buffering FIRST to catch any stray output
ob_start();

// Disable error display (log them instead)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// CRITICAL DEBUGGING - Increase limits to rule out exhaustion
ini_set('memory_limit', '1024M');
set_time_limit(300); // 5 minutes

// Custom crash logger
function log_crash($message) {
    file_put_contents(__DIR__ . '/../../FATAL_ERROR_LOG.txt', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

// Catch shutdown fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR)) {
        log_crash("SHUTDOWN ERROR: " . $error['message'] . " in " . $error['file'] . ":" . $error['line']);
    }
});

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/config.php';
require_once '../../includes/comment_functions.php';

// CRITICAL: All tables are in das_db database
// customer_profiles, loan_details, borrowers, guarantors, collateral, etc. are all in das_db
$conn = new mysqli('localhost', 'root', '', 'das_db');
if ($conn->connect_error) {
    ob_end_clean(); // Clear any buffered output
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// das_conn is same as conn since everything is in das_db
$das_conn = $conn;

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ob_end_clean(); // Clear any buffered output
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Route to appropriate function
switch ($action) {
    case 'get_provinces':
        getProvinces();
        break;
    case 'get_branches':
        getBranches();
        break;
    case 'get_districts':
        getDistricts();
        break;
    case 'get_municipalities':
        getMunicipalities();
        break;
    case 'get_wards':
        getWards();
        break;
    case 'create_customer':
        createCustomer();
        break;
    case 'get_profile':
        getCustomerProfile();
        break;
    case 'save_borrower':
        saveBorrower();
        break;
    case 'save_guarantor':
        saveGuarantor();
        break;
    case 'save_family_details':
        saveFamilyDetails();
        break;
    case 'save_authorized_persons':
        saveAuthorizedPersons();
        break;
    case 'save_collateral':
        saveCollateral();
        break;
    case 'save_limit':
        saveLimitDetails();
        break;
    case 'save_loan':
        saveLoanDetails();
        break;
    case 'get_borrowers':
        getBorrowers();
        break;
    case 'get_guarantors':
        getGuarantors();
        break;
    case 'get_collaterals':
        getCollaterals();
        break;
    case 'get_limits':
        getLimits();
        break;
    case 'get_loans':
        getLoans();
        break;
    case 'clone_profile':
        handleCloneProfile();
        break;
    case 'search_profiles':
        searchProfiles();
        break;
    case 'search_collateral_source':
        searchCollateralsForImport();
        break;
    case 'import_collateral':
        importCollateral();
        break;
    case 'delete_item':
        deleteItem();
        break;
    case 'search_master_entity':
        searchMasterEntity();
        break;
    case 'submit_profile':
        submitProfile();
        break;
    case 'reinitiate_profile':
        reinitiateProfile();
        break;
    case 'approve_profile':
        approveProfile();
        break;
    case 'reject_profile':
        rejectProfile();
        break;
    case 'return_profile':
        returnProfile();
        break;
    case 'get_family_details':
        getFamilyDetails();
        break;
    case 'get_owners':
        getOwners();
        break;
    case 'get_generated_documents':
        getGeneratedDocuments();
        break;
    case 'get_legal_heirs':
        getLegalHeirs();
        break;
    case 'get_comments':
        $profile_id = $_GET['profile_id'] ?? '';
        if (empty($profile_id)) {
            echo json_encode(['success' => false, 'message' => 'Profile ID required']);
        } else {
            echo json_encode(['success' => true, 'data' => getProfileComments($profile_id)]);
        }
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// =====================================================
// LOCATION FUNCTIONS
// =====================================================

/**
 * Get list of provinces from admin_db.provinces
 */
function getProvinces() {
    global $conn;
    
    // Fetch from admin_db which contains the full list of 7 provinces
    $stmt = $conn->prepare("SELECT province_name, province_name_nepali FROM admin_db.provinces WHERE is_active = 1 ORDER BY province_code");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $provinces = [];
    while ($row = $result->fetch_assoc()) {
        $provinces[] = [
            'id' => $row['province_name_nepali'], // Use Nepali name as ID because demopnepal table uses Nepali names for filtering
            'name' => $row['province_name_nepali'], // Display Nepali name (User request: "nepali province details fetch hona chaiyea")
            'name_en' => $row['province_name']      // Keep English name just in case
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $provinces]);
}

/**
 * Get list of branches from das_db.branchsol (Full list of 138 branches)
 */
function getBranches() {
    global $conn;
    
    // We fetch all branches from branchsol table which has 138 rows
    // Note: Cross-province filtering is disabled because branchsol doesn't have province mapping
    $query = "SELECT `SOL ID` as sol_id, `SOL Detail` as sol_name FROM das_db.branchsol ORDER BY `SOL ID` ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $branches = [];
    while ($row = $result->fetch_assoc()) {
        $branches[] = [
            'id' => $row['sol_id'], // Using sol_id as ID for simplicity
            'sol_id' => $row['sol_id'],
            'sol_name' => $row['sol_name'],
            'sol_name_np' => $row['sol_name'], // branchsol only has names in English
            'province' => '' // No province mapping available in branchsol
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $branches]);
}

function getDistricts() {
    global $conn;
    
    $province = $_GET['province'] ?? '';
    
    if (empty($province)) {
        echo json_encode(['success' => false, 'message' => 'Province is required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT DISTINCT district FROM demopnepal WHERE province = ? ORDER BY district");
    $stmt->bind_param("s", $province);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $districts = [];
    while ($row = $result->fetch_assoc()) {
        $districts[] = $row['district'];
    }
    
    echo json_encode(['success' => true, 'data' => $districts]);
}

function getMunicipalities() {
    global $conn;
    
    $province = $_GET['province'] ?? '';
    $district = $_GET['district'] ?? '';
    
    if (empty($province) || empty($district)) {
        echo json_encode(['success' => false, 'message' => 'Province and district are required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT municipality_vdc, wada_count FROM demopnepal WHERE province = ? AND district = ? ORDER BY municipality_vdc");
    $stmt->bind_param("ss", $province, $district);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $municipalities = [];
    while ($row = $result->fetch_assoc()) {
        $municipalities[] = [
            'name' => $row['municipality_vdc'],
            'wada_count' => $row['wada_count']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $municipalities]);
}

function getWards() {
    global $conn;
    
    $municipality = $_GET['municipality'] ?? '';
    
    if (empty($municipality)) {
        echo json_encode(['success' => false, 'message' => 'Municipality is required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT wada_count FROM demopnepal WHERE municipality_vdc = ? LIMIT 1");
    $stmt->bind_param("s", $municipality);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $wards = [];
        for ($i = 1; $i <= $row['wada_count']; $i++) {
            $wards[] = $i;
        }
        echo json_encode(['success' => true, 'data' => $wards]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Municipality not found']);
    }
}

// =====================================================
// CUSTOMER PROFILE FUNCTIONS
// =====================================================

function createCustomer() {
    global $conn;
    
    $customer_type = $_POST['customer_type'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact = $_POST['contact'] ?? '';
    
    if (empty($customer_type) || empty($full_name) || empty($contact)) {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        return;
    }
    
    // Auto-set template_category based on customer_type
    // Individual -> Individual templates, Corporate -> Institutional templates
    $template_category = ($customer_type === 'Individual') ? 'Individual' : 'Institutional';
    
    try {
        // Get data from POST
        $selected_province = $_POST['province'] ?? '';
        $selected_sol = $_POST['sol'] ?? '';

        // Get fallback data from logged-in user
        $user_id = $_SESSION['user_id'];
        $user_query = $conn->prepare("SELECT province, sol FROM users WHERE id = ?");
        $user_query->bind_param("i", $user_id);
        $user_query->execute();
        $user_result = $user_query->get_result();
        $user_data = $user_result->fetch_assoc();
        
        $final_province = !empty($selected_province) ? $selected_province : ($user_data['province'] ?? 1);
        $final_sol = !empty($selected_sol) ? $selected_sol : ($user_data['sol'] ?? '001');

        // Generate customer ID
        // Consume any pending results
        while($conn->more_results()) { $conn->next_result(); }

        if (!$conn->query("CALL sp_generate_customer_profile_id(@new_id)")) {
            throw new Exception("Failed to call ID generation: " . $conn->error);
        }
        
        $result = $conn->query("SELECT @new_id as customer_id");
        if (!$result) {
            throw new Exception("Failed to select generated ID");
        }
        $customer_id = $result->fetch_assoc()['customer_id'];
        
        // Clear results again
        while($conn->more_results()) { $conn->next_result(); }
        
        // Insert customer profile with template_category
        $stmt = $conn->prepare("INSERT INTO customer_profiles (customer_id, customer_type, template_category, full_name, email, contact, created_by, province, sol, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Draft')");
        if (!$stmt) {
             throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssssssis", $customer_id, $customer_type, $template_category, $full_name, $email, $contact, $user_id, $final_province, $final_sol);
    } catch (Throwable $e) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
        return;
    }
    
    if ($stmt->execute()) {
        $profile_id = $conn->insert_id;
        
        // Log audit
        $action = "Customer Created";
        $description = "Created customer profile: $customer_id - $full_name (Template Category: $template_category)";
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $conn->query("CALL sp_log_audit($user_id, '$action', 'customer_profiles', $profile_id, '$description', '$ip_address')");
        
        ob_clean(); // Clean buffer before output
        echo json_encode([
            'success' => true, 
            'message' => 'Customer profile created successfully',
            'customer_id' => $customer_id,
            'profile_id' => $profile_id,
            'template_category' => $template_category
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create customer profile']);
    }
}

/**
 * Helper function to fetch profile data (NOT an API endpoint)
 * Used internally by document generation
 */
function fetchProfileData($profile_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT cp.*, u.full_name as created_by_name FROM customer_profiles cp LEFT JOIN users u ON cp.created_by = u.id WHERE cp.id = ?");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getCustomerProfile() {
    global $conn;
    
    $profile_id = $_POST['profile_id'] ?? $_GET['profile_id'] ?? $_GET['id'] ?? '';
    
    if (empty($profile_id)) {
        echo json_encode(['success' => false, 'message' => 'Profile ID is required']);
        return;
    }
    
    // Get customer profile
    $stmt = $conn->prepare("SELECT cp.*, u.full_name as created_by_name FROM customer_profiles cp LEFT JOIN users u ON cp.created_by = u.id WHERE cp.id = ?");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($profile = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $profile]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Profile not found']);
    }
}

function checkProfileAccess($profile_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT created_by, status FROM customer_profiles WHERE id = ?");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if ($row['created_by'] != $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Only the creator can edit this profile.']);
            exit;
        }
        if ($row['status'] !== 'Draft' && $row['status'] !== 'Returned') {
             echo json_encode(['success' => false, 'message' => 'Profile is not in editable status (Draft/Returned).']);
             exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Profile not found']);
        exit;
    }
}

// =====================================================
// HELPER FUNCTIONS
// =====================================================
function convertNepaliDigitsToEnglish($str) {
    if (empty($str)) return $str;
    $nepali = ['०','१','२','३','४','५','६','७','८','९'];
    $english = ['0','1','2','3','4','5','6','7','8','9'];
    return str_replace($nepali, $english, $str);
}

function convertEnglishDigitsToNepali($str) {
    if (empty($str)) return $str;
    $english = ['0','1','2','3','4','5','6','7','8','9'];
    $nepali = ['०','१','२','३','४','५','६','७','८','९'];
    return str_replace($english, $nepali, $str);
}

// =====================================================
// BORROWER FUNCTIONS
// =====================================================

function saveBorrower() {
    global $conn;
    
    $data = $_POST;
    $profile_id = $data['customer_profile_id'] ?? '';
    $borrower_id = $data['borrower_id'] ?? null;
    
    // DEBUG: Log received data
    error_log("=== SAVE BORROWER DEBUG ===");
    error_log("Profile ID: " . $profile_id);
    error_log("Borrower ID: " . ($borrower_id ?? 'NEW'));
    error_log("Address fields received:");
    error_log("  perm_province: " . ($data['perm_province'] ?? 'NOT SET'));
    error_log("  perm_district: " . ($data['perm_district'] ?? 'NOT SET'));
    error_log("  perm_municipality_vdc: " . ($data['perm_municipality_vdc'] ?? 'NOT SET'));
    error_log("  perm_ward_no: " . ($data['perm_ward_no'] ?? 'NOT SET'));
    error_log("  temp_province: " . ($data['temp_province'] ?? 'NOT SET'));
    error_log("All POST keys: " . implode(', ', array_keys($data)));
    
    if (empty($profile_id)) {
        echo json_encode(['success' => false, 'message' => 'Customer profile ID is required']);
        return;
    }

    // Check Access
    checkProfileAccess($profile_id);

    // SANITIZE DATES: Convert Nepali digits to English for storage (MySQL DATE format requires 0-9)
    $date_fields = ['date_of_birth', 'id_issue_date', 'id_reissue_date'];
    foreach ($date_fields as $field) {
        if (isset($data[$field])) {
            $data[$field] = convertNepaliDigitsToEnglish($data[$field]);
        }
    }

    // Fields to exclude from borrowers table (auto-handled or array fields)
    $excluded_fields = [
        'action', 'customer_profile_id', 'borrower_id', 
        'family_name', 'family_relation',
        // Authorized person array fields
        'auth_person_name_en', 'auth_person_name_np', 'auth_person_dob',
        'auth_person_gender', 'auth_person_citizenship', 'auth_person_id_issue_date',
        'auth_person_id_issue_district', 'auth_person_contact', 'auth_person_email',
        'auth_person_designation', 'auth_person_marital_status', 'auth_person_father_name',
        // Read-only calculation fields
        'date_of_birth_ad', 'age', 'id_issue_date_ad', 'id_reissue_date_ad'
    ];
    
    // Checkbox handling: Explicitly set to 0 if not present in POST
    $checkbox_fields = ['is_co_borrower', 'ctz_reissued'];
    foreach ($checkbox_fields as $field) {
        if (!isset($data[$field])) {
            $data[$field] = '0';
        }
    }

    // MASTER ENTITY LINKING
    try {
        $master_type = $data['borrower_type'] ?? 'Individual';
        $master_id = saveMasterEntity($data, $master_type);
        if ($master_id) {
            $data['master_entity_id'] = $master_id;
        }
    } catch (Exception $e) {
        error_log("Master Entity Save Failed: " . $e->getMessage());
    }
    
    // Build query based on whether updating or inserting
    if ($borrower_id) {
        // Update existing borrower
        $fields = [];
        $types = "";
        $values = [];
        
        foreach ($data as $key => $value) {
            if (!in_array($key, $excluded_fields) && !is_array($value)) {
                $fields[] = "$key = ?";
                $types .= "s";
                $values[] = $value;
            }
        }
        
        $types .= "i";
        $values[] = $borrower_id;
        
        $sql = "UPDATE borrowers SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            // Handle Family Details if present
            if (isset($data['family_name']) && is_array($data['family_name'])) {
                error_log("Saving family details for borrower $borrower_id");
                savePersonFamilyDetails($borrower_id, 'Borrower', $data['family_name'], $data['family_relation'] ?? []);
            }
            
            // Handle Authorized Persons if present (for Corporate borrowers)
            if (isset($data['auth_person_name_en']) && is_array($data['auth_person_name_en'])) {
                error_log("Saving authorized persons for borrower $borrower_id");
                saveBorrowerAuthorizedPersons($borrower_id, $data);
            }
            
            echo json_encode(['success' => true, 'message' => 'Borrower updated successfully', 'borrower_id' => $borrower_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update borrower: ' . $stmt->error]);
        }
    } else {
        // Insert new borrower
        $fields = ['customer_profile_id'];
        $placeholders = ['?'];
        $types = "i";
        $values = [$profile_id];
        
        foreach ($data as $key => $value) {
            if (!in_array($key, $excluded_fields) && !is_array($value)) {
                $fields[] = $key;
                $placeholders[] = '?';
                $types .= "s";
                $values[] = $value;
            }
        }
        
        $sql = "INSERT INTO borrowers (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            $borrower_id = $conn->insert_id;
            
            // Handle Family Details if present
            if (isset($data['family_name']) && is_array($data['family_name'])) {
                error_log("Saving family details for NEW borrower $borrower_id");
                savePersonFamilyDetails($borrower_id, 'Borrower', $data['family_name'], $data['family_relation'] ?? []);
            }
            
            // Handle Authorized Persons if present (for Corporate borrowers)
            if (isset($data['auth_person_name_en']) && is_array($data['auth_person_name_en'])) {
                error_log("Saving authorized persons for NEW borrower $borrower_id");
                saveBorrowerAuthorizedPersons($borrower_id, $data);
            }
            
            echo json_encode(['success' => true, 'message' => 'Borrower saved successfully', 'borrower_id' => $borrower_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save borrower: ' . $stmt->error]);
        }
    }
}

// Helper function to save authorized persons for corporate borrowers
function saveBorrowerAuthorizedPersons($borrower_id, $data) {
    global $conn;
    
    // Delete existing authorized persons for this borrower
    $stmt = $conn->prepare("DELETE FROM authorized_persons WHERE corporate_id = ? AND person_type = 'Borrower'");
    $stmt->bind_param("i", $borrower_id);
    $stmt->execute();
    
    // Get array lengths
    $names_en = $data['auth_person_name_en'] ?? [];
    $count = count($names_en);
    
    if ($count == 0) {
        return; // No authorized persons to save
    }
    
    // Prepare insert statement
    $stmt = $conn->prepare("INSERT INTO authorized_persons (
        corporate_id, person_type, name_en, name_np, date_of_birth, gender, 
        citizenship_number, id_issue_date, id_issue_district, contact_number, 
        email, designation, marital_status, father_name
    ) VALUES (?, 'Borrower', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Insert each authorized person
    for ($i = 0; $i < $count; $i++) {
        $name_en = $names_en[$i] ?? '';
        $name_np = ($data['auth_person_name_np'] ?? [])[$i] ?? '';
        $dob = ($data['auth_person_dob'] ?? [])[$i] ?? null;
        $gender = ($data['auth_person_gender'] ?? [])[$i] ?? '';
        $citizenship = ($data['auth_person_citizenship'] ?? [])[$i] ?? '';
        $id_issue_date = ($data['auth_person_id_issue_date'] ?? [])[$i] ?? null;
        $id_issue_district = ($data['auth_person_id_issue_district'] ?? [])[$i] ?? '';
        $contact = ($data['auth_person_contact'] ?? [])[$i] ?? '';
        $email = ($data['auth_person_email'] ?? [])[$i] ?? '';
        $designation = ($data['auth_person_designation'] ?? [])[$i] ?? '';
        $marital_status = ($data['auth_person_marital_status'] ?? [])[$i] ?? '';
        $father_name = ($data['auth_person_father_name'] ?? [])[$i] ?? '';
        
        // Only insert if name is provided
        if (!empty($name_en)) {
            $stmt->bind_param("issssssssssss", 
                $borrower_id, $name_en, $name_np, $dob, $gender, 
                $citizenship, $id_issue_date, $id_issue_district, $contact, 
                $email, $designation, $marital_status, $father_name
            );
            $stmt->execute();
        }
    }
}

// Helper function to save family details from array inputs
function savePersonFamilyDetails($person_id, $person_type, $names, $relations) {
    global $conn;
    
    $log_entry = date('Y-m-d H:i:s') . " - savePersonFamilyDetails Call\n";
    $log_entry .= "Person: $person_id ($person_type)\n";
    $log_entry .= "Names Count: " . count($names) . "\n";
    file_put_contents(__DIR__ . '/../../debug_guarantor_save.txt', $log_entry, FILE_APPEND);
    
    // Delete existing family details for this person
    $stmt = $conn->prepare("DELETE FROM family_details WHERE person_id = ? AND person_type = ?");
    $stmt->bind_param("is", $person_id, $person_type);
    if (!$stmt->execute()) {
         file_put_contents(__DIR__ . '/../../debug_guarantor_save.txt', "DELETE FAILED: " . $stmt->error . "\n", FILE_APPEND);
    }
    
    // Insert new family details
    $stmt = $conn->prepare("INSERT INTO family_details (person_id, person_type, name, relation) VALUES (?, ?, ?, ?)");
    
    for ($i = 0; $i < count($names); $i++) {
        if (!empty($names[$i])) {
            $name = $names[$i];
            $relation = $relations[$i] ?? '';
            $stmt->bind_param("isss", $person_id, $person_type, $name, $relation);
            if (!$stmt->execute()) {
                 file_put_contents(__DIR__ . '/../../debug_guarantor_save.txt', "INSERT FAILED: " . $stmt->error . "\n", FILE_APPEND);
            } else {
                 file_put_contents(__DIR__ . '/../../debug_guarantor_save.txt', "INSERT SUCCESS: $name ($relation)\n", FILE_APPEND);
            }
        } else {
             file_put_contents(__DIR__ . '/../../debug_guarantor_save.txt', "SKIPPING EMPTY NAME at index $i\n", FILE_APPEND);
        }
    }
}

function getBorrowers() {
    global $conn;
    
    $profile_id = $_GET['profile_id'] ?? '';
    
    if (empty($profile_id)) {
        echo json_encode(['success' => false, 'message' => 'Profile ID is required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT * FROM borrowers WHERE customer_profile_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $borrowers = [];
    while ($row = $result->fetch_assoc()) {
        $borrowers[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $borrowers]);
}

// =====================================================
// GUARANTOR FUNCTIONS
// =====================================================

function saveGuarantor() {
    global $conn;
    
    $data = $_POST;
    $profile_id = $data['customer_profile_id'] ?? '';
    $guarantor_id = $data['guarantor_id'] ?? null;
    
    if (empty($profile_id)) {
        echo json_encode(['success' => false, 'message' => 'Customer profile ID is required']);
        return;
    }

    // Check Access
    checkProfileAccess($profile_id);

    // SANITIZE DATES: Convert Nepali digits to English for storage
    $date_fields = ['date_of_birth', 'id_issue_date', 'id_reissue_date'];
    foreach ($date_fields as $field) {
        if (isset($data[$field])) {
            $data[$field] = convertNepaliDigitsToEnglish($data[$field]);
        }
    }

    // DEBUG: Custom Log to file
    $log_entry = date('Y-m-d H:i:s') . " - Save Guarantor Call\n";
    $log_entry .= "ID: " . ($guarantor_id ?? 'NEW') . "\n";
    $log_entry .= "Family Names: " . (isset($data['family_name']) ? print_r($data['family_name'], true) : 'NOT SET') . "\n";
    $log_entry .= "Family Relations: " . (isset($data['family_relation']) ? print_r($data['family_relation'], true) : 'NOT SET') . "\n";
    file_put_contents(__DIR__ . '/../../debug_guarantor_save.txt', $log_entry, FILE_APPEND);

    // Fields to exclude from guarantors table (auto-handled)
    $excluded_fields = [
        'action', 'customer_profile_id', 'guarantor_id', 'family_name', 'family_relation',
        // Read-only calculation fields
        'date_of_birth_ad', 'age', 'id_issue_date_ad', 'id_reissue_date_ad'
    ];
    
    // Checkbox handling: Explicitly set to 0 if not present in POST
    $checkbox_fields = ['is_co_borrower', 'ctz_reissued'];
    foreach ($checkbox_fields as $field) {
        if (!isset($data[$field])) {
            $data[$field] = '0';
        }
    }

    // MASTER ENTITY LINKING
    try {
        $master_type = $data['guarantor_type'] ?? 'Individual';
        $master_id = saveMasterEntity($data, $master_type);
        if ($master_id) {
            $data['master_entity_id'] = $master_id;
        }
    } catch (Exception $e) {
        error_log("Master Entity Save Failed: " . $e->getMessage());
    }

    if ($guarantor_id) {
        // Update existing guarantor
        $fields = [];
        $types = "";
        $values = [];
        
        foreach ($data as $key => $value) {
            if (!in_array($key, $excluded_fields)) {
                $fields[] = "$key = ?";
                $types .= "s";
                $values[] = $value;
            }
        }
        
        $types .= "i";
        $values[] = $guarantor_id;
        
        $sql = "UPDATE guarantors SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            // Handle Family Details if present
            if (isset($data['family_name']) && is_array($data['family_name'])) {
                 error_log("Saving family details for guarantor $guarantor_id: " . print_r($data['family_name'], true));
                savePersonFamilyDetails($guarantor_id, 'Guarantor', $data['family_name'], $data['family_relation'] ?? []);
            }
            echo json_encode(['success' => true, 'message' => 'Guarantor updated successfully', 'guarantor_id' => $guarantor_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update guarantor']);
        }
    } else {
        // Insert new guarantor
        $fields = ['customer_profile_id'];
        $placeholders = ['?'];
        $types = "i";
        $values = [$profile_id];
        
        // Checkbox handling for Insert as well
        $checkbox_fields = ['is_co_borrower', 'ctz_reissued'];
        foreach ($checkbox_fields as $field) {
            if (!isset($data[$field])) {
                $data[$field] = '0';
            }
        }
        
        foreach ($data as $key => $value) {
            if (!in_array($key, $excluded_fields)) {
                $fields[] = $key;
                $placeholders[] = '?';
                $types .= "s";
                $values[] = $value;
            }
        }
        
        $sql = "INSERT INTO guarantors (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            $guarantor_id = $conn->insert_id;
            // Handle Family Details if present
            if (isset($data['family_name']) && is_array($data['family_name'])) {
                 error_log("Saving family details for NEW guarantor $guarantor_id");
                savePersonFamilyDetails($guarantor_id, 'Guarantor', $data['family_name'], $data['family_relation'] ?? []);
            }
            echo json_encode(['success' => true, 'message' => 'Guarantor saved successfully', 'guarantor_id' => $guarantor_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save guarantor']);
        }
    }
}

function getGuarantors() {
    global $conn;
    
    $profile_id = $_GET['profile_id'] ?? '';
    
    if (empty($profile_id)) {
        echo json_encode(['success' => false, 'message' => 'Profile ID is required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT * FROM guarantors WHERE customer_profile_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $guarantors = [];
    while ($row = $result->fetch_assoc()) {
        $guarantors[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $guarantors]);
}


// =====================================================
// FAMILY DETAILS FUNCTIONS
// =====================================================


function saveFamilyDetails() {
    global $conn;
    
    $person_id = $_POST['person_id'] ?? '';
    $person_type = $_POST['person_type'] ?? '';
    $family_members = json_decode($_POST['family_members'] ?? '[]', true);
    
    if (empty($person_id) || empty($person_type)) {
        echo json_encode(['success' => false, 'message' => 'Person ID and type are required']);
        return;
    }
    
    // Delete existing family details
    $stmt = $conn->prepare("DELETE FROM family_details WHERE person_id = ? AND person_type = ?");
    $stmt->bind_param("is", $person_id, $person_type);
    $stmt->execute();
    
    // Insert new family details
    $stmt = $conn->prepare("INSERT INTO family_details (person_id, person_type, name, relation) VALUES (?, ?, ?, ?)");
    
    if (!$stmt) {
         error_log("Prepare Failed in saveFamilyDetails: " . $conn->error);
         return;
    }

    foreach ($family_members as $member) {
        $stmt->bind_param("isss", $person_id, $person_type, $member['name'], $member['relation']);
        if (!$stmt->execute()) {
             error_log("Execute failed in saveFamilyDetails: " . $stmt->error);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Family details saved successfully']);
}

function getFamilyDetails() {
    global $conn;
    
    $person_id = $_GET['person_id'] ?? '';
    $person_type = $_GET['person_type'] ?? '';
    
    if (empty($person_id) || empty($person_type)) {
        echo json_encode(['success' => false, 'message' => 'Person ID and type are required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT * FROM family_details WHERE person_id = ? AND person_type = ? ORDER BY id");
    $stmt->bind_param("is", $person_id, $person_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $family_members = [];
    while ($row = $result->fetch_assoc()) {
        $family_members[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $family_members]);
}


// =====================================================
// AUTHORIZED PERSONS FUNCTIONS


// =====================================================
// AUTHORIZED PERSONS FUNCTIONS
// =====================================================

function saveAuthorizedPersons() {
    global $conn;
    
    $corporate_id = $_POST['corporate_id'] ?? '';
    $person_type = $_POST['person_type'] ?? '';
    $authorized_persons = json_decode($_POST['authorized_persons'] ?? '[]', true);
    
    if (empty($corporate_id) || empty($person_type)) {
        echo json_encode(['success' => false, 'message' => 'Corporate ID and type are required']);
        return;
    }
    
    // Delete existing authorized persons
    $stmt = $conn->prepare("DELETE FROM authorized_persons WHERE corporate_id = ? AND person_type = ?");
    $stmt->bind_param("is", $corporate_id, $person_type);
    $stmt->execute();
    
    // Insert new authorized persons
    $stmt = $conn->prepare("INSERT INTO authorized_persons (corporate_id, person_type, name, designation, contact) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($authorized_persons as $person) {
        $stmt->bind_param("issss", $corporate_id, $person_type, $person['name'], $person['designation'], $person['contact']);
        $stmt->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'Authorized persons saved successfully']);
}

// =====================================================
// COLLATERAL FUNCTIONS
// =====================================================

function saveCollateral() {
    global $conn;
    
    $data = $_POST;
    $profile_id = $data['customer_profile_id'] ?? '';
    $collateral_id = $data['collateral_id'] ?? null;
    
    // Extract Legal Heir arrays and remove from data for collateral table
    $legal_heir_names = $data['legal_heir_name'] ?? [];
    unset($data['legal_heir_name']);
    
    // Store reference for other legal heir fields to pass to helper
    $legal_heir_data = [
        'names' => $legal_heir_names,
        'relations' => $data['legal_heir_relation'] ?? [],
        'father_names' => $data['legal_heir_father_name'] ?? [],
        'grandfather_names' => $data['legal_heir_grandfather_name'] ?? [],
        'dobs' => $data['legal_heir_dob'] ?? [],
        'citizenship_nos' => $data['legal_heir_citizenship_no'] ?? [],
        'issue_dates' => $data['legal_heir_citizenship_issue_date'] ?? [],
        'issue_districts' => $data['legal_heir_citizenship_issue_district'] ?? []
    ];

    // Remove these arrays from data so they don't break SQL generation for collateral table
    unset($data['legal_heir_relation']);
    unset($data['legal_heir_father_name']);
    unset($data['legal_heir_grandfather_name']);
    unset($data['legal_heir_dob']);
    unset($data['legal_heir_citizenship_no']);
    unset($data['legal_heir_citizenship_issue_date']);
    unset($data['legal_heir_citizenship_issue_district']);

    
    if (empty($profile_id)) {
        echo json_encode(['success' => false, 'message' => 'Customer profile ID is required']);
        return;
    }

    // Check Access
    checkProfileAccess($profile_id);
    
    if ($collateral_id) {
        // Update
        $fields = [];
        $types = "";
        $values = [];
        
        foreach ($data as $key => $value) {
            if ($key != 'collateral_id' && $key != 'customer_profile_id' && $key != 'action') {
                $fields[] = "$key = ?";
                $types .= "s";
                $values[] = $value;
            }
        }
        
        $types .= "i";
        $values[] = $collateral_id;
        
        $sql = "UPDATE collateral SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            saveLegalHeirs($profile_id, $collateral_id, $legal_heir_data);
            echo json_encode(['success' => true, 'message' => 'Collateral updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update collateral']);
        }
    } else {
        // Insert
        $fields = ['customer_profile_id'];
        $placeholders = ['?'];
        $types = "i";
        $values = [$profile_id];
        
        foreach ($data as $key => $value) {
            if ($key != 'action' && $key != 'customer_profile_id') {
                $fields[] = $key;
                $placeholders[] = '?';
                $types .= "s";
                $values[] = $value;
            }
        }
        
        $sql = "INSERT INTO collateral (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            $new_collateral_id = $conn->insert_id;
            saveLegalHeirs($profile_id, $new_collateral_id, $legal_heir_data);
            echo json_encode(['success' => true, 'message' => 'Collateral saved successfully', 'collateral_id' => $new_collateral_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save collateral']);
        }
    }
}

function saveLegalHeirs($profile_id, $collateral_id, $data) {
    global $conn;
    
    // Delete existing heirs
    $stmt = $conn->prepare("DELETE FROM legal_heirs WHERE collateral_id = ?");
    $stmt->bind_param("i", $collateral_id);
    $stmt->execute();
    
    $names = $data['names'];
    $count = count($names);
    
    if ($count == 0) return;
    
    $stmt = $conn->prepare("INSERT INTO legal_heirs (
        customer_profile_id, collateral_id, name, relation, 
        father_name, grandfather_name, date_of_birth, 
        citizenship_no, citizenship_issue_date, citizenship_issue_district
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    for ($i = 0; $i < $count; $i++) {
        $name = $names[$i];
        if (empty($name)) continue;
        
        $relation = $data['relations'][$i] ?? '';
        $father = $data['father_names'][$i] ?? '';
        $grandfather = $data['grandfather_names'][$i] ?? '';
        $dob = $data['dobs'][$i] ?? '';
        $cit_no = $data['citizenship_nos'][$i] ?? '';
        $cit_date = $data['issue_dates'][$i] ?? '';
        $cit_dist = $data['issue_districts'][$i] ?? '';
        
        // Convert dates to English digits just in case
        $dob = convertNepaliDigitsToEnglish($dob);
        $cit_date = convertNepaliDigitsToEnglish($cit_date);
        
        $stmt->bind_param("iissssssss", 
            $profile_id, $collateral_id, $name, $relation,
            $father, $grandfather, $dob,
            $cit_no, $cit_date, $cit_dist
        );
        $stmt->execute();
    }
}

function getLegalHeirs() {
    global $conn;
    $collateral_id = $_GET['collateral_id'] ?? 0;
    
    $stmt = $conn->prepare("SELECT * FROM legal_heirs WHERE collateral_id = ?");
    $stmt->bind_param("i", $collateral_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $heirs = [];
    while ($row = $result->fetch_assoc()) {
        $heirs[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $heirs]);
}

function getCollaterals() {
    global $conn;
    
    $profile_id = $_GET['profile_id'] ?? '';
    
    if (empty($profile_id)) {
        echo json_encode(['success' => false, 'message' => 'Profile ID is required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT c.*, 
        CASE 
            WHEN c.owner_type = 'Borrower' THEN b.full_name
            WHEN c.owner_type = 'Guarantor' THEN g.full_name
        END as owner_name
        FROM collateral c
        LEFT JOIN borrowers b ON c.owner_id = b.id AND c.owner_type = 'Borrower'
        LEFT JOIN guarantors g ON c.owner_id = g.id AND c.owner_type = 'Guarantor'
        WHERE c.customer_profile_id = ? ORDER BY c.created_at DESC");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $collaterals = [];
    while ($row = $result->fetch_assoc()) {
        $collaterals[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $collaterals]);
}

// =====================================================
// LIMIT DETAILS FUNCTIONS
// =====================================================

function saveLimitDetails() {
    global $conn;
    
    $data = $_POST;
    $profile_id = $data['customer_profile_id'] ?? '';
    $limit_id = $data['limit_id'] ?? null;
    
    if (empty($profile_id)) {
        echo json_encode(['success' => false, 'message' => 'Customer profile ID is required']);
        return;
    }

    // Check Access
    checkProfileAccess($profile_id);
    
    // Convert Nepali digits to English for numeric fields
    $amount = convertNepaliDigitsToEnglish($data['amount'] ?? '');
    $tenure = convertNepaliDigitsToEnglish($data['tenure'] ?? '');
    $interest_rate = convertNepaliDigitsToEnglish($data['interest_rate'] ?? '');
    $base_rate = convertNepaliDigitsToEnglish($data['base_rate'] ?? '');
    $premium = convertNepaliDigitsToEnglish($data['premium'] ?? '');
    
    if ($limit_id) {
        // Update
        $stmt = $conn->prepare("UPDATE limit_details SET 
            loan_type = ?, loan_purpose = ?, amount = ?, amount_in_words = ?, tenure = ?, 
            interest_rate = ?, base_rate = ?, premium = ? 
            WHERE id = ?");
        $stmt->bind_param("ssssssssi", 
            $data['loan_type'], $data['loan_purpose'], $amount, $data['amount_in_words'], 
            $tenure, $interest_rate, $base_rate, $premium, $limit_id);
        
        if ($stmt->execute()) {
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Limit details updated successfully']);
        } else {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to update limit details: ' . $stmt->error]);
        }
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO limit_details 
            (customer_profile_id, loan_type, loan_purpose, amount, amount_in_words, tenure, 
            interest_rate, base_rate, premium) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssss", 
            $profile_id, $data['loan_type'], $data['loan_purpose'], $amount, $data['amount_in_words'], 
            $tenure, $interest_rate, $base_rate, $premium);
        
        if ($stmt->execute()) {
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Limit details saved successfully', 'limit_id' => $conn->insert_id]);
        } else {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to save limit details: ' . $stmt->error]);
        }
    }
}

function getLimits() {
    global $conn;
    
    $profile_id = $_GET['profile_id'] ?? '';
    
    if (empty($profile_id)) {
        echo json_encode(['success' => false, 'message' => 'Profile ID is required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT * FROM limit_details WHERE customer_profile_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $limits = [];
    while ($row = $result->fetch_assoc()) {
        $limits[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $limits]);
}

// =====================================================
// LOAN DETAILS FUNCTIONS
// =====================================================

function saveLoanDetails() {
    global $conn;
    
    $data = $_POST;
    $profile_id = $data['customer_profile_id'] ?? '';
    $loan_id = $data['loan_id'] ?? null;
    
    if (empty($profile_id)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Customer profile ID is required']);
        return;
    }

    // Check Access
    checkProfileAccess($profile_id);
    
    if ($loan_id) {
        // Update
        $loan_type = $data['loan_type'];
        $loan_approved_date = $data['loan_approved_date'];
        $approval_ref_no = $data['approval_ref_no'] ?? '';
        $loan_scheme_name = $data['loan_scheme_name'] ?? '';
        $loan_purpose = $data['loan_purpose'] ?? '';
        $remarks = $data['remarks'] ?? '';
        
        $stmt = $conn->prepare("UPDATE loan_details SET loan_type = ?, loan_approved_date = ?, approval_ref_no = ?, loan_scheme_name = ?, loan_purpose = ?, remarks = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $loan_type, $loan_approved_date, $approval_ref_no, $loan_scheme_name, $loan_purpose, $remarks, $loan_id);
        
        if ($stmt->execute()) {
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Loan details updated successfully']);
        } else {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to update loan details: ' . $stmt->error]);
        }
    } else {
        // Insert
        $loan_type = $data['loan_type'];
        $loan_approved_date = $data['loan_approved_date'];
        $approval_ref_no = $data['approval_ref_no'] ?? '';
        $loan_scheme_name = $data['loan_scheme_name'] ?? '';
        $loan_purpose = $data['loan_purpose'] ?? '';
        $remarks = $data['remarks'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO loan_details (customer_profile_id, loan_type, loan_approved_date, approval_ref_no, loan_scheme_name, loan_purpose, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $profile_id, $loan_type, $loan_approved_date, $approval_ref_no, $loan_scheme_name, $loan_purpose, $remarks);
        
        if ($stmt->execute()) {
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Loan details saved successfully', 'loan_id' => $conn->insert_id]);
        } else {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to save loan details: ' . $stmt->error]);
        }
    }
}

function getLoans() {
    global $conn;
    
    $profile_id = $_GET['profile_id'] ?? '';
    
    if (empty($profile_id)) {
        echo json_encode(['success' => false, 'message' => 'Profile ID is required']);
        return;
    }
    
    // Fetch loan details - now using loan_scheme_name field directly
    $stmt = $conn->prepare("SELECT ld.* FROM loan_details ld WHERE ld.customer_profile_id = ? ORDER BY ld.created_at DESC");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $loans = [];
    while ($row = $result->fetch_assoc()) {
        // Use loan_scheme_name field directly (plain text input)
        // Fallback to scheme_id lookup only if loan_scheme_name is empty (for old records)
        if (empty($row['loan_scheme_name']) && !empty($row['scheme_id'])) {
            $das_conn = new mysqli('localhost', 'root', '', 'das_db');
            $scheme_stmt = $das_conn->prepare("SELECT scheme_name FROM loan_schemes WHERE id = ?");
            $scheme_stmt->bind_param("i", $row['scheme_id']);
            $scheme_stmt->execute();
            $scheme_result = $scheme_stmt->get_result();
            if ($scheme_data = $scheme_result->fetch_assoc()) {
                $row['scheme_name'] = $scheme_data['scheme_name'];
            }
            $das_conn->close();
        } else {
            // Use the plain text loan_scheme_name
            $row['scheme_name'] = $row['loan_scheme_name'] ?? 'Not specified';
        }
        $loans[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $loans]);
}

// =====================================================
// MASTER ENTITY FUNCTIONS
// =====================================================

function searchMasterEntity() {
    global $conn;
    
    $query = $_GET['q'] ?? '';
    $type = $_GET['type'] ?? 'Individual';
    
    if (strlen($query) < 3) {
        echo json_encode(['success' => true, 'data' => []]);
        return;
    }
    
    $search = "%$query%";
    $sql = "SELECT * FROM master_entities WHERE entity_type = ? AND (full_name LIKE ? OR citizenship_number LIKE ? OR contact_number LIKE ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $type, $search, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $entities = [];
    while ($row = $result->fetch_assoc()) {
        $entities[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $entities]);
}

function saveMasterEntity($data, $type = 'Individual') {
    global $conn;
    
    // Check if entity exists by unique identifiers
    // For Individual: Citizenship No + District
    // For Corporate: Registration No/PAN
    
    $master_id = null;
    
    if ($type === 'Individual') {
        $citizenship_no = $data['citizenship_number'] ?? '';
        $issue_district = $data['id_issue_district'] ?? '';
        
        if (!empty($citizenship_no) && !empty($issue_district)) {
            $stmt = $conn->prepare("SELECT id FROM master_entities WHERE citizenship_number = ? AND id_issue_district = ? LIMIT 1");
            $stmt->bind_param("ss", $citizenship_no, $issue_district);
            $stmt->execute();
            $stmt->bind_result($existing_id);
            if ($stmt->fetch()) {
                $master_id = $existing_id;
            }
            $stmt->close();
        }
    }
    
    // Prepare common fields
    $full_name = $data['full_name'] ?? $data['company_name'] ?? '';
    $email = $data['email'] ?? '';
    $contact = $data['contact_number'] ?? $data['contact'] ?? '';
    // ... map other fields ...
    
    // Update or Insert Master Entity
    if ($master_id) {
        // Option: Update master entity with latest details? 
        // For now, let's just return the existing ID to link it.
        // Or maybe update contact info if changed.
        return $master_id;
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO master_entities (
            full_name, full_name_en, full_name_np, 
            date_of_birth, gender, citizenship_number, 
            id_issue_date, id_issue_district, 
            father_name, grandfather_name, 
            contact_number, email, entity_type
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $full_name_en = $data['full_name_en'] ?? $full_name;
        $full_name_np = $data['full_name_np'] ?? '';
        $dob = $data['date_of_birth'] ?? null;
        $gender = $data['gender'] ?? '';
        $citizenship = $data['citizenship_number'] ?? null;
        $issue_date = $data['id_issue_date'] ?? null;
        $district = $data['id_issue_district'] ?? null;
        $father = $data['father_name'] ?? '';
        $grandfather = $data['grandfather_name'] ?? '';
        
        $stmt->bind_param("sssssssssssss", 
            $full_name, $full_name_en, $full_name_np, 
            $dob, $gender, $citizenship, 
            $issue_date, $district, 
            $father, $grandfather, 
            $contact, $email, $type
        );
        
        if ($stmt->execute()) {
            return $conn->insert_id;
        }
    }
    return null;
}

// Helper to update Master Entity Link in saveBorrower/saveGuarantor
// Call this inside those functions


function deleteItem() {
    global $conn;
    
    $type = $_POST['type'] ?? '';
    $id = $_POST['id'] ?? '';
    
    if (empty($type) || empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Type and ID are required']);
        return;
    }
    
    $table_map = [
        'borrower' => 'borrowers',
        'guarantor' => 'guarantors',
        'collateral' => 'collateral',
        'limit' => 'limit_details',
        'loan' => 'loan_details'
    ];
    
    if (!isset($table_map[$type])) {
        echo json_encode(['success' => false, 'message' => 'Invalid type']);
        return;
    }
    
    $table = $table_map[$type];
    
    // Check Access
    $check_stmt = $conn->prepare("SELECT customer_profile_id FROM $table WHERE id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $res = $check_stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        checkProfileAccess($row['customer_profile_id']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => ucfirst($type) . ' deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete ' . $type]);
    }
}

function getOwners() {
    global $conn;
    
    $profile_id = $_GET['profile_id'] ?? '';
    
    if (empty($profile_id)) {
        echo json_encode(['success' => false, 'message' => 'Profile ID is required']);
        return;
    }
    
    $owners = [];
    
    // Get borrowers
    $stmt = $conn->prepare("SELECT id, full_name, 'Borrower' as type FROM borrowers WHERE customer_profile_id = ?");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $owners[] = $row;
    }
    
    // Get guarantors
    $stmt = $conn->prepare("SELECT id, full_name, 'Guarantor' as type FROM guarantors WHERE customer_profile_id = ?");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $owners[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $owners]);
}

// =====================================================
// WORKFLOW FUNCTIONS
// =====================================================

function submitProfile() {
    global $conn;
    
    $profile_id = $_POST['profile_id'] ?? '';
    $remarks = $_POST['remarks'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 1;
    
    if (empty($profile_id)) {
        echo json_encode(['success' => false, 'message' => 'Profile ID is required']);
        return;
    }

    // Check Access
    checkProfileAccess($profile_id);
    
    $stmt = $conn->prepare("UPDATE customer_profiles SET status = 'Submitted', submitted_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $profile_id);
    
    
    if ($stmt->execute()) {
        if (!empty($remarks)) {
            addProfileComment($profile_id, $user_id, $remarks, 'Submission');
        } else {
            addProfileComment($profile_id, $user_id, 'Profile submitted for approval', 'Submission');
        }
        ob_end_clean(); // End buffering and clear
        echo json_encode(['success' => true, 'message' => 'Profile submitted for approval']);
    } else {
        ob_end_clean(); // End buffering and clear
        echo json_encode(['success' => false, 'message' => 'Failed to submit profile']);
    }
}


function approveProfile() {
    global $conn, $das_conn;
    
    // Include new document generation classes
    require_once '../../includes/PlaceholderMapper.php';
    require_once '../../includes/DocumentGenerator.php';
    
    $profile_id = $_POST['profile_id'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    // Include Mail Service
    require_once '../../includes/MailService.php';
    
    if (empty($profile_id)) {
        echo json_encode(['success' => false, 'message' => 'Profile ID is required']);
        return;
    }
    
    // Debug logging
    $log_file = __DIR__ . '/../../debug_approval.log';
    $log = function($msg) use ($log_file) {
        file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
    };
    
    $log("=== APPROVAL START for Profile $profile_id ===");

    // 1. Get Customer Profile with Template Category
    if (!isset($das_conn) || $das_conn->connect_error) {
         $das_conn = new mysqli('localhost', 'root', '', 'das_db');
    }
    
    // Fetch customer profile with template_category AND creator email
    $stmt = $das_conn->prepare("
        SELECT cp.customer_id, cp.customer_type, cp.template_category, cp.full_name, cp.created_by, cp.id,
               u.email as creator_email, u.full_name as creator_name 
        FROM customer_profiles cp 
        LEFT JOIN users u ON cp.created_by = u.id 
        WHERE cp.id = ?
    ");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $profile_result = $stmt->get_result();
    
    if ($profile_result->num_rows === 0) {
        $log("ERROR: Customer profile not found");
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Customer profile not found']);
        return;
    }
    
    $profile_data = $profile_result->fetch_assoc();
    $customer_id = $profile_data['customer_id'];
    $template_category = $profile_data['template_category'];
    
    // Fallback: If template_category is not set, derive it from customer_type
    if (empty($template_category)) {
        $template_category = ($profile_data['customer_type'] === 'Individual') ? 'Individual' : 'Institutional';
        $log("WARNING: template_category was null, derived as $template_category from customer_type");
        
        // Update the profile with the derived category
        $update_stmt = $das_conn->prepare("UPDATE customer_profiles SET template_category = ? WHERE id = ?");
        $update_stmt->bind_param("si", $template_category, $profile_id);
        $update_stmt->execute();
    }
    
    $log("Customer Profile Found: $customer_id, Template Category: $template_category");
    
// =====================================================
// RENEWAL / RE-INITIATE FUNCTIONS
// =====================================================

function reinitiateProfile() {
    global $conn, $das_conn;
    
    $profile_id = $_POST['profile_id'] ?? '';
    $application_type = $_POST['application_type'] ?? 'Renewal'; // Renewal, Enhancement, etc.
    $user_id = $_SESSION['user_id'];
    
    if (empty($profile_id)) {
        echo json_encode(['success' => false, 'message' => 'Profile ID required']);
        return;
    }
    
    // 1. Get Original Profile
    $stmt = $conn->prepare("SELECT * FROM customer_profiles WHERE id = ?");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $original = $result->fetch_assoc();
    
    if (!$original) {
        echo json_encode(['success' => false, 'message' => 'Profile not found']);
        return;
    }
    
    // 2. Create New Profile (Clone)
    // Generate new Customer ID? No, keep same customer_id usually? OR new Request ID?
    // User requirement: "generate ho customer profile... but ek issue ye hai..." 
    // Actually for Renewal, we usually keep the CUSTOMER ID but this table uses customer_id as the Request/Application ID.
    // The user said: "checker profile k aander ek customer ka ek history wala profile banayange"
    
    // Let's generate a NEW profile ID (Request ID) but keep the same data.
    
    // Generate new ID
    if (!$conn->query("CALL sp_generate_customer_profile_id(@new_id)")) {
        echo json_encode(['success' => false, 'message' => 'Failed to generate ID']);
        return;
    }
    $res = $conn->query("SELECT @new_id as customer_id");
    $new_customer_id = $res->fetch_assoc()['customer_id'];
    while($conn->more_results()) { $conn->next_result(); } // Clear buffer
    
    // Insert Copied Profile
    $stmt = $conn->prepare("INSERT INTO customer_profiles (
        customer_id, customer_type, template_category, full_name, email, contact, 
        created_by, province, sol, status, parent_profile_id, application_type
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Draft', ?, ?)");
    
    $stmt->bind_param("ssssssisssis", 
        $new_customer_id, $original['customer_type'], $original['template_category'], 
        $original['full_name'], $original['email'], $original['contact'],
        $user_id, $original['province'], $original['sol'], 
        $profile_id, $application_type
    );
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to create new profile: ' . $stmt->error]);
        return;
    }
    
    $new_profile_id = $conn->insert_id;
    
    // 3. Clone Related Data
    
    // Borrowers
    $conn->query("INSERT INTO borrowers (customer_profile_id, borrower_type, is_co_borrower, full_name, full_name_en, full_name_np, date_of_birth, gender, relationship_status, citizenship_number, id_issue_date, id_issue_district, id_issue_authority, father_name, grandfather_name, address_perm, address_temp, contact_number, email, master_entity_id)
        SELECT $new_profile_id, borrower_type, is_co_borrower, full_name, full_name_en, full_name_np, date_of_birth, gender, relationship_status, citizenship_number, id_issue_date, id_issue_district, id_issue_authority, father_name, grandfather_name, address_perm, address_temp, contact_number, email, master_entity_id
        FROM borrowers WHERE customer_profile_id = $profile_id");

    // Guarantors
    $conn->query("INSERT INTO guarantors (customer_profile_id, guarantor_type, is_co_borrower, full_name, full_name_en, full_name_np, date_of_birth, gender, relationship_status, citizenship_number, id_issue_date, id_issue_district, id_issue_authority, father_name, grandfather_name, address_perm, address_temp, contact_number, email, master_entity_id)
        SELECT $new_profile_id, guarantor_type, is_co_borrower, full_name, full_name_en, full_name_np, date_of_birth, gender, relationship_status, citizenship_number, id_issue_date, id_issue_district, id_issue_authority, father_name, grandfather_name, address_perm, address_temp, contact_number, email, master_entity_id
        FROM guarantors WHERE customer_profile_id = $profile_id");
        
    // Collateral (Basic copy - Owner mapping might be tricky if owners are linked by ID. 
    // Collateral table links to Owner ID? Table check needed.
    // If collateral links to borrower.id, and we just created NEW borrowers with NEW IDs, the links will break!
    // COMPLEXITY: We need to map Old Borrower IDs to New Borrower IDs.
    
    // For now, let's copy Limits and Loan details which are usually independent.
    // Collateral linking requires more complex logic (map old_id -> new_id).
    
    // Limits
    $conn->query("INSERT INTO limit_details (customer_profile_id, loan_type, loan_purpose, amount, amount_in_words, tenure, interest_rate, base_rate, premium)
        SELECT $new_profile_id, loan_type, loan_purpose, amount, amount_in_words, tenure, interest_rate, base_rate, premium
        FROM limit_details WHERE customer_profile_id = $profile_id");

    // Loans
    $conn->query("INSERT INTO loan_details (customer_profile_id, loan_type, loan_scheme_name, loan_approved_date, loan_purpose, remarks)
        SELECT $new_profile_id, loan_type, loan_scheme_name, loan_approved_date, loan_purpose, remarks
        FROM loan_details WHERE customer_profile_id = $profile_id");
        
    // Log comment
    addProfileComment($profile_id, $user_id, "Profile re-initiated as $new_customer_id ($application_type)", 'Re-initiate');
    addProfileComment($new_profile_id, $user_id, "Profile created from $original[customer_id] ($application_type)", 'Creation');
    
    echo json_encode([
        'success' => true, 
        'message' => 'Profile re-initiated successfully',
        'new_profile_id' => $new_profile_id
    ]);
}

// 2. Get Templates for this Template Category (replaces scheme-based lookup)
    $stmt = $das_conn->prepare("
        SELECT t.id, t.template_name, t.file_path 
        FROM templates t
        WHERE t.template_type = ? AND t.is_active = 1
        ORDER BY t.template_name
    ");
    $stmt->bind_param("s", $template_category);
    $stmt->execute();
    $templates = $stmt->get_result();
    
    $log("Found " . $templates->num_rows . " templates for category: $template_category");
    
    if ($templates->num_rows === 0) {
         $log("WARNING: No templates found for template category: $template_category");
         // Approve anyway but warn
         $conn->query("UPDATE customer_profiles SET status = 'Approved', approved_by = $user_id, approved_at = NOW() WHERE id = $profile_id");
         ob_end_clean();
         echo json_encode(['success' => true, 'message' => "Profile approved, but no templates found for $template_category category. Please upload templates in Template Manager."]);
         return;
    }
    
    // 3. Update Profile Status FIRST
    $stmt = $conn->prepare("UPDATE customer_profiles SET status = 'Approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $user_id, $profile_id);
    
    if (!$stmt->execute()) {
        $log("ERROR: Failed to update profile status: " . $conn->error);
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Failed to approve profile']);
        return;
    }
    
    $remarks = $_POST['remarks'] ?? '';
    if (!empty($remarks)) {
        addProfileComment($profile_id, $user_id, $remarks, 'Approval');
    } else {
        addProfileComment($profile_id, $user_id, 'Profile approved', 'Approval');
    }
    
    $log("Profile status updated to Approved. Proceeding to document generation.");

    // 4. Generate Documents using DocumentRuleEngine
    $generated_count = 0;
    $errors = [];
    
    // Fetch customer_id for linking
    $cust_stmt = $das_conn->prepare("SELECT customer_id FROM customer_profiles WHERE id = ?");
    $cust_stmt->bind_param("i", $profile_id);
    $cust_stmt->execute();
    $cust_res = $cust_stmt->get_result();
    $cust_row = $cust_res->fetch_assoc();
    $customer_id = $cust_row['customer_id'];

    try {
        require_once __DIR__ . '/../../includes/DocumentGenerator.php';
        
        $generator = new DocumentGenerator($das_conn);
        
        // Clear previous generation log
        $detailed_log = __DIR__ . '/../../generated_documents/last_generation_log.txt';
        file_put_contents($detailed_log, "Generation Batch Log - " . date('Y-m-d H:i:s') . "\n============================================\n\n");
        
        while ($template = $templates->fetch_assoc()) {
            $log("Generating document for template: " . $template['template_name']);
            
            try {
                $template_path = __DIR__ . '/../../' . $template['file_path'];
                
                // Apply dynamic template selection (e.g., Mortgage Deed 1-5 borrowers)
                if (method_exists($generator, 'resolveDynamicPath')) {
                    $template_path = $generator->resolveDynamicPath($template_path, $profile_id);
                }
                
                if (!file_exists($template_path)) {
                    $errors[] = $template['template_name'] . ": Template file not found";
                    $log("ERROR: Template file not found: " . $template_path);
                    continue;
                }
                
                // Determine document type from template name (e.g. mortgage_deed, loan_deed)
                $doc_type_slug = 'default';
                $tmpl_lower = strtolower($template['template_name']);
                
                $is_splittable = false;
                $is_guarantor_splittable = false;
                
                if (strpos($tmpl_lower, 'mortgage') !== false) {
                    $doc_type_slug = 'mortgage_deed';
                    $is_splittable = true;
                } elseif (strpos($tmpl_lower, 'rokka') !== false) { // Rokka Letter Support
                    $doc_type_slug = 'mortgage_deed'; // Uses similar data to Mortgage Deed
                    $is_splittable = true;
                } elseif (strpos($tmpl_lower, 'loan') !== false) {
                    $doc_type_slug = 'loan_deed';
                } elseif (strpos($tmpl_lower, 'guarantee') !== false || strpos($tmpl_lower, 'personal') !== false) {
                     $doc_type_slug = 'guarantee_deed';
                     $is_guarantor_splittable = true;
                } elseif (strpos($tmpl_lower, 'attorney') !== false || strpos($tmpl_lower, 'poa') !== false) { // Power of Attorney
                     $doc_type_slug = 'guarantee_deed'; // POA is usually by Guarantor
                     $is_guarantor_splittable = true;
                }
                
                $generation_results = [];
                
                // Use Split Logic
                if ($is_splittable) {
                    $log("DEBUG: Split Malpot Logic for " . $template['template_name']);
                    $generation_results = $generator->generateDocumentsGroupedByMalpot($template_path, $profile_id, $doc_type_slug);
                } elseif ($is_guarantor_splittable) {
                     // NEW: Split POA/Guarantor by Guarantor
                     $log("DEBUG: Split Guarantor Logic for " . $template['template_name']);
                     $generation_results = $generator->generateDocumentsGroupedByGuarantor($template_path, $profile_id, $doc_type_slug);
                } else {
                    // Standard single document generation
                    $generation_results[] = $generator->generateDocument($template_path, $profile_id, $doc_type_slug);
                }
                
                foreach ($generation_results as $result) {
                    if ($result['success']) {
                        $generated_count++;
                        $log("Document generated successfully with " . ($result['placeholders_count'] ?? 0) . " placeholders");
                        if (isset($result['malpot_office']) && $result['malpot_office'] !== 'General') {
                            $log("Group: " . $result['malpot_office']);
                        }
                        $log("Saved to folder: " . $result['folder_name']);
                        
                        // Ensure customer exists in `customers` table (to satisfy FK for generated_documents)
                        // This handles cases where customer_profiles has the data but customers table (legacy/linked) does not
                        $check_cust_stmt = $conn->prepare("SELECT id FROM customers WHERE id = ?");
                        // Assuming customer_id in profiles is the ID we want in customers
                        $cust_pk = (int)$customer_id; 
                        $check_cust_stmt->bind_param("i", $cust_pk);
                        $check_cust_stmt->execute();
                        if ($check_cust_stmt->get_result()->num_rows == 0) {
                            $log("Legacy 'customers' record missing for ID $cust_pk. Creating it...");
                            
                             // Fetch full name from profile if available (was fetched in step 2 earlier? query at 1251 only fetched customer_id)
                            $p_stmt = $conn->prepare("SELECT full_name FROM customer_profiles WHERE id = ?");
                            $p_stmt->bind_param("i", $profile_id);
                            $p_stmt->execute();
                            $p_row = $p_stmt->get_result()->fetch_assoc();
                            $c_name = $p_row['full_name'] ?? ('Customer ' . $customer_id);
                            
                            // Disable FK checks temporarily for this fix
                            $conn->query("SET FOREIGN_KEY_CHECKS=0");
                            $ins_cust = $conn->prepare("INSERT INTO customers (id, customer_id, customer_name, created_by, status) VALUES (?, ?, ?, ?, 'Active')");
                            $ins_cust->bind_param("issi", $cust_pk, $customer_id, $c_name, $user_id);
                            if(!$ins_cust->execute()) {
                                $log("WARNING: Failed to auto-create customers record: " . $conn->error);
                            }
                            $conn->query("SET FOREIGN_KEY_CHECKS=1");
                        }
    
                        // Save generation record using the relative path
                        $doc_number = 'DOC-' . date('YmdHis') . '-' . rand(100, 999);
                        $saved_name = $template['template_name'];
                        if (isset($result['malpot_office']) && $result['malpot_office'] !== 'General') {
                             // Clean up name: remove parens if previously added or just append
                             $saved_name .= " - " . $result['malpot_office'];
                        }
                        if (isset($result['guarantor_name']) && $result['guarantor_name'] !== 'General') {
                             $saved_name .= " - " . $result['guarantor_name'];
                        }
                        
                        // Clean up double spaces if any
                        $saved_name = str_replace("  ", " ", $saved_name);
                        
                        $stmt = $das_conn->prepare("
                            INSERT INTO generated_documents 
                            (customer_profile_id, customer_id, scheme_id, template_name, file_path, generated_by, generated_at, document_number, document_name)
                            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
                        ");
                        $stmt->bind_param("iisssiss", $profile_id, $customer_id, $scheme_id, $template['template_name'], $result['relative_path'], $user_id, $doc_number, $saved_name);
                        $stmt->execute();
                    } else {
                        $err = $result['error'] ?? 'Unknown Error';
                        $errors[] = $template['template_name'] . ": " . $err;
                        $log("ERROR: " . $err);
                    }
                }
            } catch (Exception $e) {
                $error_msg = $e->getMessage();
                $errors[] = $template['template_name'] . ": " . $error_msg;
                $log("Exception: " . $error_msg);
            }
        }
    } catch (Exception $e) {
        $log("FATAL ERROR: " . $e->getMessage());
        $errors[] = "System error: " . $e->getMessage();
    }
    
    $message = "Profile approved!";
    if ($generated_count > 0) {
        $message .= " Generated $generated_count document(s).";
    }


    
    if (!empty($errors)) {
        $message .= " WARNING: Some documents failed: " . implode(", ", $errors);
    }
    
    // Clear any buffered output before sending JSON
    ob_end_clean();
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'generated_count' => $generated_count
    ]);
}

/**
 * Get generated documents for a profile
 */
function getGeneratedDocuments() {
    global $conn, $das_conn;
    
    $profile_id = $_GET['profile_id'] ?? '';
    
    if (empty($profile_id)) {
        echo json_encode(['success' => false, 'message' => 'Profile ID required']);
        return;
    }
    
    // Fetch generated documents with user info
    $stmt = $das_conn->prepare("
        SELECT gd.*, u.full_name as generated_by_name 
        FROM generated_documents gd
        LEFT JOIN users u ON gd.generated_by = u.id
        WHERE gd.customer_profile_id = ?
        ORDER BY gd.generated_at DESC
    ");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $documents = [];
    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $documents]);
}

function rejectProfile() {
    global $conn;
    
    $profile_id = $_POST['profile_id'] ?? '';
    $reason = $_POST['rejection_reason'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 1;
    
    if (empty($profile_id) || empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Profile ID and reason are required']);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE customer_profiles SET status = 'Rejected' WHERE id = ?");
    $stmt->bind_param("i", $profile_id);
    
    if ($stmt->execute()) {
        addProfileComment($profile_id, $user_id, $reason, 'Rejection');
        echo json_encode(['success' => true, 'message' => 'Profile rejected']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reject profile']);
    }
}

function returnProfile() {
    global $conn;
    
    $profile_id = $_POST['profile_id'] ?? '';
    $comment = $_POST['comments'] ?? $_POST['checker_comment'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 1;
    
    if (empty($profile_id) || empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Profile ID and comment are required']);
        return;
    }
    
    // Still update checker_comment column for legacy compatibility if needed
    $stmt = $conn->prepare("UPDATE customer_profiles SET status = 'Returned', checker_comment = ? WHERE id = ?");
    $stmt->bind_param("si", $comment, $profile_id);
    
    if ($stmt->execute()) {
        addProfileComment($profile_id, $user_id, $comment, 'Return');
        echo json_encode(['success' => true, 'message' => 'Profile returned to maker']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to return profile']);
    }
}

function searchProfiles() {
    global $conn;
    
    $query = $_GET['query'] ?? '';
    
    if (strlen($query) < 2) {
        // Return latest 20 customers if no query
        $sql = "
            SELECT p.*, 
            (SELECT status FROM customer_profiles WHERE customer_id = p.customer_id ORDER BY id DESC LIMIT 1) as latest_status
            FROM customer_profiles p
            WHERE p.id IN (SELECT MAX(id) FROM customer_profiles GROUP BY customer_id)
            ORDER BY p.updated_at DESC LIMIT 20
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $customers = [];
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $customers]);
        return;
    }
    
    $search = "%$query%";
    // Search for profiles, prioritizing Approved ones for Renewal
    // We group by customer_id to show unique customers
    $sql = "
        SELECT p.*, 
        (SELECT status FROM customer_profiles WHERE customer_id = p.customer_id ORDER BY id DESC LIMIT 1) as latest_status
        FROM customer_profiles p
        WHERE (p.full_name LIKE ? OR p.contact LIKE ? OR p.email LIKE ? OR p.customer_id LIKE ?)
        AND p.id IN (SELECT MAX(id) FROM customer_profiles GROUP BY customer_id)
        ORDER BY p.full_name ASC LIMIT 20
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $search, $search, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $customers]);
}

function searchCollateralsForImport() {
    global $conn;
    $query = $_GET['query'] ?? '';
    
    if (empty($query)) {
        // Return latest 50 collaterals if no query
        $sql = "
        SELECT c.*, 
               p.full_name as source_profile_name, 
               p.customer_id,
               CASE 
                   WHEN c.owner_type = 'Borrower' THEN COALESCE(b.full_name, b.company_name)
                   WHEN c.owner_type = 'Guarantor' THEN COALESCE(g.full_name, g.company_name)
                   ELSE 'Unknown'
               END as owner_name
        FROM collateral c 
        JOIN customer_profiles p ON c.customer_profile_id = p.id 
        LEFT JOIN borrowers b ON c.owner_id = b.id AND c.owner_type = 'Borrower'
        LEFT JOIN guarantors g ON c.owner_id = g.id AND c.owner_type = 'Guarantor'
        ORDER BY c.id DESC LIMIT 50
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $items]);
        return;
    }
    
    $search = "%$query%";
    $sql = "
        SELECT c.*, 
               p.full_name as source_profile_name, 
               p.customer_id,
               CASE 
                   WHEN c.owner_type = 'Borrower' THEN COALESCE(b.full_name, b.company_name)
                   WHEN c.owner_type = 'Guarantor' THEN COALESCE(g.full_name, g.company_name)
                   ELSE 'Unknown'
               END as owner_name
        FROM collateral c 
        JOIN customer_profiles p ON c.customer_profile_id = p.id 
        LEFT JOIN borrowers b ON c.owner_id = b.id AND c.owner_type = 'Borrower'
        LEFT JOIN guarantors g ON c.owner_id = g.id AND c.owner_type = 'Guarantor'
        WHERE (p.full_name LIKE ? OR p.customer_id LIKE ?)
        ORDER BY p.full_name ASC, c.id DESC LIMIT 20
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $items]);
}

function importCollateral() {
    global $conn;
    
    $target_pid = $_POST['target_profile_id'] ?? '';
    $source_ids_json = $_POST['source_collateral_ids'] ?? '[]';
    $source_ids = json_decode($source_ids_json, true);
    
    if (empty($target_pid) || empty($source_ids)) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        return;
    }
    
    // Fetch source items
    $ids_placeholder = implode(',', array_fill(0, count($source_ids), '?'));
    $sql_src = "SELECT * FROM collateral WHERE id IN ($ids_placeholder)";
    $stmt = $conn->prepare($sql_src);
    $stmt->bind_param(str_repeat('i', count($source_ids)), ...$source_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $success_count = 0;
    while ($row = $result->fetch_assoc()) {
        // Prepare insert
        $cols = []; $vals = []; $params = []; $types = "";
        
        foreach ($row as $k => $v) {
            if ($k !== 'id' && $k !== 'customer_profile_id' && $k !== 'created_at' && $k !== 'updated_at') {
                $cols[] = $k;
                $vals[] = "?";
                $params[] = $v;
                $types .= "s"; // Simplified type assumption
            }
        }
        
        // Link to target
        $cols[] = "customer_profile_id"; 
        $vals[] = "?"; 
        $params[] = $target_pid; 
        $types .= "i";
        
        $ins_sql = "INSERT INTO collateral (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
        $ins_stmt = $conn->prepare($ins_sql);
        $ins_stmt->bind_param($types, ...$params);
        if ($ins_stmt->execute()) {
            $success_count++;
        }
    }
    
    echo json_encode(['success' => true, 'message' => "Imported $success_count items"]);
}

// RENEWAL & ENHANCEMENT WORKFLOW
// =====================================================

function handleCloneProfile() {
    global $conn;
    
    $old_profile_id = $_POST['profile_id'] ?? '';
    $type = $_POST['application_type'] ?? 'Renewal';
    
    if (empty($old_profile_id)) {
        echo json_encode(['success' => false, 'message' => 'Profile ID is required']);
        return;
    }
    
    try {
        $new_profile_id = cloneProfile($old_profile_id, $type);
        echo json_encode([
            'success' => true, 
            'message' => 'Profile cloned successfully',
            'new_profile_id' => $new_profile_id
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function cloneProfile($old_profile_id, $type) {
    global $conn, $das_conn;
    
    $user_id = $_SESSION['user_id'] ?? 1;
    
    // 1. Clone Customer Profile
    $stmt = $conn->prepare("SELECT * FROM customer_profiles WHERE id = ?");
    $stmt->bind_param("i", $old_profile_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Source profile not found");
    }
    
    $old_profile = $result->fetch_assoc();
    
    // Prepare data for new profile
    // Exclude keys that should be typically reset or handled by DB defaults
    $exclude_keys = ['id', 'created_at', 'updated_at', 'approved_at', 'approved_by', 'submitted_at', 'checker_comment', 'status', 'document_generation_status'];
    
    $cols = [];
    $vals = [];
    $types = "";
    $params = [];
    
    foreach ($old_profile as $key => $value) {
        if (!in_array($key, $exclude_keys)) {
            $cols[] = $key;
            $vals[] = "?";
            $params[] = $value;
            // Determine type (rough approximation)
            if (is_int($value)) $types .= "i";
            elseif (is_double($value)) $types .= "d";
            else $types .= "s";
        }
    }
    
    // Add new specific fields
    $cols[] = "parent_profile_id"; $vals[] = "?"; $params[] = $old_profile_id; $types .= "i";
    $cols[] = "application_type";  $vals[] = "?"; $params[] = $type;           $types .= "s";
    $cols[] = "status";            $vals[] = "?"; $params[] = "Draft";         $types .= "s";
    $cols[] = "created_by";        $vals[] = "?"; $params[] = $user_id;        $types .= "i";
    
    $sql = "INSERT INTO customer_profiles (" . implode(", ", $cols) . ") VALUES (" . implode(", ", $vals) . ")";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to clone profile: " . $conn->error);
    }
    
    $new_profile_id = $conn->insert_id;
    
    // Helper function to clone related tables
    $cloneTable = function($table, $fk_col) use ($conn, $old_profile_id, $new_profile_id) {
        // Get columns
        $res = $conn->query("SHOW COLUMNS FROM $table");
        $columns = [];
        while ($row = $res->fetch_assoc()) {
            if ($row['Field'] !== 'id' && $row['Field'] !== 'created_at' && $row['Field'] !== 'updated_at') {
                $columns[] = $row['Field'];
            }
        }
        
        $col_str = implode(", ", $columns);
        // We replace the FK value in the SELECT
        $select_cols = array_map(function($c) use ($fk_col, $new_profile_id) {
            return ($c === $fk_col) ? "$new_profile_id" : $c;
        }, $columns);
        $select_str = implode(", ", $select_cols);
        
        $sql = "INSERT INTO $table ($col_str) SELECT $select_str FROM $table WHERE $fk_col = $old_profile_id";
        if (!$conn->query($sql)) {
            // Log but don't crash? Or crash to ensure integrity?
            // Throwing for now
            throw new Exception("Failed to clone $table: " . $conn->error);
        }
    };
    
    // 2. Clone Related Data
    $cloneTable("borrowers", "customer_profile_id");
    $cloneTable("guarantors", "customer_profile_id");
    $cloneTable("collateral", "customer_profile_id");
    $cloneTable("loan_details", "customer_profile_id");
    $cloneTable("limit_details", "customer_profile_id");
    
    return $new_profile_id;
}
?>
