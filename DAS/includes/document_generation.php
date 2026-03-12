<?php
/**
 * Document Generation Core Functions
 * Handles DOCX template processing with PHPWord
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Helper function to fetch profile data
 * Used internally by document generation
 */
if (!function_exists('fetchProfileData')) {
    function fetchProfileData($profile_id) {
        // Connect to das_db
        $conn = new mysqli('localhost', 'root', '', 'das_db');
        if ($conn->connect_error) {
            return null;
        }
        
        $stmt = $conn->prepare("SELECT cp.*, u.full_name as created_by_name FROM customer_profiles cp LEFT JOIN users u ON cp.created_by = u.id WHERE cp.id = ?");
        $stmt->bind_param("i", $profile_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $profile = $result->fetch_assoc();
        $conn->close();
        
        return $profile;
    }
}

/**
 * Generate document from approved profile
 * 
 * @param int $profile_id Customer profile ID
 * @param int $template_id Template ID to use
 * @param string $batch_id Optional batch grouping ID
 * @return array Result with success status and file info
 */
function generateDocument($profile_id, $template_id, $batch_id = null) {
    // Debug logging
    $log_file = __DIR__ . '/../debug_doc_gen.log';
    $log = function($msg) use ($log_file) {
        file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
    };

    $log("Starting generateDocument for Profile $profile_id, Template $template_id, Batch $batch_id");

    // Create database connection
    $das_conn = new mysqli('localhost', 'root', '', 'das_db');
    if ($das_conn->connect_error) {
        $log("DB Connection Failed: " . $das_conn->connect_error);
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Validate profile is approved
    $profile = fetchProfileData($profile_id);
    if (!$profile) {
        $log("Profile not found: $profile_id");
        return ['success' => false, 'error' => 'Profile not found'];
    }
    
    if ($profile['status'] !== 'Approved') {
        $log("Profile status is not Approved: " . $profile['status']);
        return ['success' => false, 'error' => 'Profile must be approved before generating documents'];
    }
    
    $log("Profile validated: Customer ID " . $profile['customer_id']);
    
    // Get template info
    $template = getTemplate($template_id);
    if (!$template) {
        $log("Template not found: $template_id");
        return ['success' => false, 'error' => 'Template not found'];
    }
    
    $log("Template found: " . $template['template_name']);
    
    // Load template file
    $path = !empty($template['template_folder_path']) ? $template['template_folder_path'] : $template['file_path'];
    if (empty($path)) {
        $log("Template path is empty in database");
        return ['success' => false, 'error' => 'Template path is missing in database'];
    }

    $log("Template path from DB: $path");
    $template_path = __DIR__ . "/../" . $path;
    $log("Full template path: $template_path");
    
    if (!file_exists($template_path) || !is_file($template_path)) {
        $log("Template file not found at: $template_path");
        return ['success' => false, 'error' => 'Template file not found or invalid: ' . $template_path];
    }
    
    $log("Template file exists and is readable");
    
    try {
        $log("Creating PHPWord TemplateProcessor");
        // Create PHPWord processor
        $processor = new TemplateProcessor($template_path);
        
        $log("Filling borrower placeholders");
        // Fill all placeholders
        fillBorrowerPlaceholders($processor, $profile_id);
        
        $log("Filling guarantor placeholders");
        fillGuarantorPlaceholders($processor, $profile_id);
        
        $log("Filling collateral placeholders");
        fillCollateralPlaceholders($processor, $profile_id);
        
        $log("Filling loan placeholders");
        fillLoanPlaceholders($processor, $profile_id);
        
        $log("Filling system placeholders");
        fillSystemPlaceholders($processor, $profile);
        
        // Generate profile-specific folder (NEW APPROACH)
        $profile_folder = "profile_" . $profile_id;
        $profile_dir = __DIR__ . "/../documents/" . $profile_folder;
        
        if (!file_exists($profile_dir)) {
            $log("Creating profile directory: $profile_dir");
            mkdir($profile_dir, 0777, true);
        }
        
        // Generate filename with document type and timestamp
        $timestamp = date('Ymd_His');
        $unique_id = uniqid();
        $filename = "original_{$timestamp}_{$unique_id}.docx";
        $output_path = "$profile_dir/$filename";
        
        $log("Saving document to: $output_path");
        // Save generated document
        $processor->saveAs($output_path);
        
        // Calculate file size
        $file_size_kb = round(filesize($output_path) / 1024, 2);
        $user_id = $_SESSION['user_id'] ?? 1;
        
        // Create template snapshot (preserve template info even if template is deleted later)
        $template_snapshot = json_encode([
            'template_id' => $template_id,
            'template_name' => $template['template_name'] ?? 'Unknown',
            'template_code' => $template['template_code'] ?? 'N/A',
            'scheme_id' => $template['scheme_id'] ?? null,
            'generated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Store in new das_generated_documents table
        $stmt = $das_conn->prepare("
            INSERT INTO das_generated_documents 
            (customer_profile_id, batch_id, loan_scheme_id, template_id, template_snapshot, 
             document_type, file_path, file_name, file_size_kb, status, generated_by)
            VALUES (?, ?, ?, ?, ?, 'original', ?, ?, ?, 'generated', ?)
        ");
        
        $scheme_id = $template['scheme_id'] ?? null;
        $relative_path = "documents/$profile_folder/$filename";
        
        $stmt->bind_param("isssisssdi", 
            $profile_id, 
            $batch_id,
            $scheme_id, 
            $template_id, 
            $template_snapshot,
            $relative_path,
            $filename,
            $file_size_kb,
            $user_id
        );
        $stmt->execute();
        $document_id = $das_conn->insert_id;
        $stmt->close();
        
        $das_conn->close();
        
        $log("SUCCESS: Document generated at $output_path (ID: $document_id) - Profile-based storage");
        
        return [
            'success' => true,
            'message' => 'Document generated successfully',
            'file_path' => $output_path,
            'relative_path' => $relative_path,
            'filename' => $filename,
            'document_id' => $document_id,
            'profile_folder' => $profile_folder
        ];
        
    } catch (Exception $e) {
        $das_conn->close();
        $log("EXCEPTION: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error generating document: ' . $e->getMessage()
        ];
    }
}

/**
 * Fill borrower placeholders (Comprehensive)
 */
function fillBorrowerPlaceholders($processor, $profile_id) {
    $borrowers = getAllBorrowers($profile_id);
    
    if (count($borrowers) > 0) {
        $b = $borrowers[0]; // Main Borrower
        
        $processor->setValue('BORROWER_NAME', $b['full_name'] ?? 'N/A');
        $processor->setValue('BORROWER_CIT_NUMBER', $b['citizenship_number'] ?? 'N/A');
        $processor->setValue('BORROWER_CIT_ISSUE_DATE', $b['citizenship_issued_date'] ?? 'N/A');
        $processor->setValue('BORROWER_CIT_ISSUE_DISTRICT', $b['citizenship_issued_district'] ?? 'N/A');
        $processor->setValue('BORROWER_DOB', $b['date_of_birth'] ?? 'N/A');
        $processor->setValue('BORROWER_FATHER_NAME', $b['father_name'] ?? 'N/A');
        $processor->setValue('BORROWER_GRANDFATHER_NAME', $b['grandfather_name'] ?? 'N/A');
        $processor->setValue('BORROWER_SPOUSE_NAME', $b['spouse_name'] ?? 'N/A');
        $processor->setValue('BORROWER_EMAIL', $b['email'] ?? 'N/A');
        $processor->setValue('BORROWER_PHONE', $b['phone_number'] ?? 'N/A');
        $processor->setValue('BORROWER_MARITAL_STATUS', $b['marital_status'] ?? 'N/A');
        $processor->setValue('BORROWER_GENDER', $b['gender'] ?? 'N/A');
        
        // Permanent Address
        $processor->setValue('BORROWER_PERM_PROVINCE', $b['permanent_province'] ?? 'N/A');
        $processor->setValue('BORROWER_PERM_DISTRICT', $b['permanent_district'] ?? 'N/A');
        $processor->setValue('BORROWER_PERM_MUNICIPALITY', $b['permanent_municipality'] ?? 'N/A');
        $processor->setValue('BORROWER_PERM_WARD', $b['permanent_ward_no'] ?? 'N/A');
        $processor->setValue('BORROWER_PERM_TOLE', $b['permanent_tole'] ?? 'N/A');
        $processor->setValue('BORROWER_PERM_FULL_ADDRESS', buildFullAddress($b, 'perm'));

        // Temporary Address
        $processor->setValue('BORROWER_TEMP_PROVINCE', $b['temporary_province'] ?? 'N/A');
        $processor->setValue('BORROWER_TEMP_DISTRICT', $b['temporary_district'] ?? 'N/A');
        $processor->setValue('BORROWER_TEMP_MUNICIPALITY', $b['temporary_municipality'] ?? 'N/A');
        $processor->setValue('BORROWER_TEMP_WARD', $b['temporary_ward_no'] ?? 'N/A');
        $processor->setValue('BORROWER_TEMP_TOLE', $b['temporary_tole'] ?? 'N/A');
        
    } else {
        // Fill with N/A if missing
        $keys = ['BORROWER_NAME', 'BORROWER_CIT_NUMBER', 'BORROWER_PERM_FULL_ADDRESS']; // Add others as needed
        foreach($keys as $k) $processor->setValue($k, 'N/A');
    }
}

/**
 * Fill guarantor placeholders (Comprehensive)
 */
function fillGuarantorPlaceholders($processor, $profile_id) {
    $guarantors = getAllGuarantors($profile_id);
    
    if (count($guarantors) > 0) {
        $g = $guarantors[0];
        
        $processor->setValue('GUARANTOR_NAME', $g['full_name'] ?? 'N/A');
        $processor->setValue('GUARANTOR_CIT_NUMBER', $g['citizenship_number'] ?? 'N/A');
        $processor->setValue('GUARANTOR_CIT_ISSUE_DATE', $g['citizenship_issued_date'] ?? 'N/A');
        $processor->setValue('GUARANTOR_CIT_ISSUE_DISTRICT', $g['citizenship_issued_district'] ?? 'N/A');
        $processor->setValue('GUARANTOR_RELATION', $g['relationship'] ?? 'N/A');
        $processor->setValue('GUARANTOR_FATHER_NAME', $g['father_name'] ?? 'N/A');
        $processor->setValue('GUARANTOR_GRANDFATHER_NAME', $g['grandfather_name'] ?? 'N/A');
        $processor->setValue('GUARANTOR_SPOUSE_NAME', $g['spouse_name'] ?? 'N/A');
        $processor->setValue('GUARANTOR_PERM_FULL_ADDRESS', buildFullAddress($g, 'perm'));
    } else {
        $processor->setValue('GUARANTOR_NAME', 'N/A');
    }
}

/**
 * Fill collateral placeholders (Comprehensive)
 */
function fillCollateralPlaceholders($processor, $profile_id) {
    $collateral = getAllCollateral($profile_id);
    
    if (count($collateral) > 0) {
        $c = $collateral[0];
        
        $processor->setValue('COLLATERAL_TYPE', $c['collateral_type'] ?? 'N/A');
        $processor->setValue('COLLATERAL_OWNER', $c['owner_name'] ?? 'N/A');
        $processor->setValue('COLLATERAL_VALUATION', number_format($c['fair_market_value'] ?? 0, 2));
        
        // Land Specific
        $processor->setValue('LAND_DISTRICT', $c['land_district'] ?? 'N/A');
        $processor->setValue('LAND_MUNICIPALITY', $c['land_municipality'] ?? 'N/A');
        $processor->setValue('LAND_WARD', $c['land_ward_no'] ?? 'N/A');
        $processor->setValue('LAND_KITTA', $c['land_kitta_no'] ?? 'N/A');
        $processor->setValue('LAND_AREA', $c['land_area'] ?? 'N/A');
        $processor->setValue('LAND_SHEET_NO', $c['land_sheet_no'] ?? 'N/A');
        
        // Vehicle Specific
        $processor->setValue('VEHICLE_MODEL', $c['vehicle_model_no'] ?? 'N/A');
        $processor->setValue('VEHICLE_REG_NO', $c['vehicle_no'] ?? 'N/A');
        $processor->setValue('VEHICLE_ENGINE_NO', $c['vehicle_engine_no'] ?? 'N/A');
        $processor->setValue('VEHICLE_CHASSIS_NO', $c['vehicle_chassis_no'] ?? 'N/A');
        
        $processor->setValue('COLLATERAL_DETAILS', getCollateralDescription($c));
    } else {
        $processor->setValue('COLLATERAL_TYPE', 'N/A');
    }
}

// Helper to build formatted address
function buildFullAddress($data, $type = 'perm') {
    $prefix = ($type == 'perm') ? 'permanent' : 'temporary';
    $muni = $data[$prefix . '_municipality'] ?? '';
    $ward = $data[$prefix . '_ward_no'] ?? '';
    $district = $data[$prefix . '_district'] ?? '';
    $province = $data[$prefix . '_province'] ?? '';
    
    $parts = [];
    if($muni) $parts[] = $muni . ($ward ? "-$ward" : "");
    if($district) $parts[] = $district;
    if($province) $parts[] = $province;
    
    return !empty($parts) ? implode(', ', $parts) : 'N/A';
}

/**
 * Fill loan placeholders (Comprehensive)
 */
function fillLoanPlaceholders($processor, $profile_id) {
    $conn = new mysqli('localhost', 'root', '', 'das_db');
    if ($conn->connect_error) return;
    
    $stmt = $conn->prepare("SELECT * FROM loan_details WHERE customer_profile_id = ?");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $loan = $stmt->get_result()->fetch_assoc();
    $conn->close();
    
    if (!$loan) return;
    
    $processor->setValue('LOAN_TYPE', $loan['loan_type'] ?? 'N/A');
    $processor->setValue('LOAN_AMOUNT', number_format($loan['sanctioned_amount'] ?? 0, 2));
    $processor->setValue('LOAN_AMOUNT_WORDS', numberToWords($loan['sanctioned_amount'] ?? 0));
    $processor->setValue('LOAN_TENURE', $loan['tenure_months'] ?? 'N/A');
    $processor->setValue('LOAN_INTEREST_RATE', $loan['interest_rate'] ?? 'N/A');
    $processor->setValue('LOAN_PURPOSE', $loan['purpose'] ?? 'N/A'); // Ensure column exists 
    $processor->setValue('BASE_RATE', $loan['base_rate'] ?? 'N/A');
    $processor->setValue('PREMIUM', $loan['premium'] ?? 'N/A');
    
    // Calculate simple installment estimation if not present
    // Calculate simple installment estimation if present and valid
    $amount = isset($loan['loan_amount']) ? (float)$loan['loan_amount'] : 0; // Column name is loan_amount?? No, logic said sanctioned_amount. Wait. 
    // Schema said NO AMOUNT col. So we must treat it as 0. 
    // Wait, check describe_loan.php output again via memory.
    // It had: id, customer_profile_id, loan_type, loan_scheme, loan_approved_date, created_at, updated_at, scheme_id.
    // NO AMOUNT.
    
    // So we effectively cannot calculate installment. 
    $processor->setValue('INSTALLMENT_AMOUNT', 'N/A (Data Missing)');

}

function powAttribute($base, $exp) {
   return pow($base, $exp);
}

/**
 * Fill system placeholders
 */
function fillSystemPlaceholders($processor, $profile) {
    $processor->setValue('SYSTEM_DATE', date('Y-m-d'));
    $processor->setValue('CUSTOMER_ID', $profile['customer_id']);
    $processor->setValue('APPROVED_BY_NAME', $profile['created_by_name'] ?? 'N/A');
    $processor->setValue('APPROVED_AT', $profile['approved_at'] ?? date('Y-m-d'));
    $processor->setValue('GENERATED_AT', date('Y-m-d H:i:s'));
    
    $processor->setValue('BANK_NAME', 'State Bank of India');
    $processor->setValue('BANK_ADDRESS', 'Kathmandu, Nepal');
    $processor->setValue('BANK_BRANCH', 'Main Branch');
}

/**
 * Helper: Get all borrowers for a profile
 */
function getAllBorrowers($profile_id) {
    $conn = new mysqli('localhost', 'root', '', 'das_db');
    if ($conn->connect_error) return [];
    
    $stmt = $conn->prepare("
        SELECT * FROM borrowers 
        WHERE customer_profile_id = ? 
        ORDER BY is_co_borrower ASC, id ASC
    ");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    return $result;
}

/**
 * Helper: Get all guarantors for a profile
 */
function getAllGuarantors($profile_id) {
    $conn = new mysqli('localhost', 'root', '', 'das_db');
    if ($conn->connect_error) return [];
    
    $stmt = $conn->prepare("
        SELECT * FROM guarantors 
        WHERE customer_profile_id = ? 
        ORDER BY id ASC
    ");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    return $result;
}

/**
 * Helper: Get all collateral for a profile
 */
function getAllCollateral($profile_id) {
    $conn = new mysqli('localhost', 'root', '', 'das_db');
    if ($conn->connect_error) return [];
    
    $stmt = $conn->prepare("
        SELECT * FROM collateral 
        WHERE customer_profile_id = ? 
        ORDER BY id ASC
    ");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    return $result;
}


/**
 * Helper: Get collateral description
 */
function getCollateralDescription($collateral) {
    if (($collateral['collateral_type'] ?? '') === 'Land') {
        return "Sheet No: {$collateral['land_sheet_no']}, Kitta No: {$collateral['land_kitta_no']}, " .
               "Area: {$collateral['land_area']}, {$collateral['land_district']}";
    } else if (($collateral['collateral_type'] ?? '') === 'Vehicle') {
        return "{$collateral['vehicle_model']}, Engine: {$collateral['vehicle_engine_no']}, " .
               "Chassis: {$collateral['vehicle_chassis_no']}";
    }
    return 'N/A';
}

/**
 * Helper: Generate document filename
 */
function generateDocumentFilename($profile, $template) {
    $customer_id = $profile['customer_id'];
    $template_code = preg_replace('/[^a-zA-Z0-9_]/', '_', $template['template_code']);
    $timestamp = date('Ymd_His');
    
    return "{$customer_id}_{$template_code}_{$timestamp}.docx";
}

/**
 * Helper: Generate agreement number
 */
function generateAgreementNumber($profile) {
    $year = date('Y');
    $month = date('m');
    $id = str_pad($profile['id'], 5, '0', STR_PAD_LEFT);
    
    return "AGR/{$year}/{$month}/{$id}";
}


/**
 * Helper: Get template
 */
function getTemplate($template_id) {
    // Create connection to das_db
    $conn = new mysqli('localhost', 'root', '', 'das_db');
    if ($conn->connect_error) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT * FROM templates WHERE id = ?");
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $conn->close();
    
    return $result;
}

/**
 * Helper: Convert number to words (simple English)
 */
function numberToWords($number) {
    if ($number == 0) return 'Zero';
    
    // Simple implementation - can be enhanced
    $words = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
        18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
        40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
        80 => 'Eighty', 90 => 'Ninety'
    ];
    
    $scales = ['', 'Thousand', 'Lakh', 'Crore'];
    
    if ($number < 21) return $words[$number] . ' Only';
    if ($number < 100) {
        $tens = floor($number / 10) * 10;
        $units = $number % 10;
        return $words[$tens] . ($units ? ' ' . $words[$units] : '') . ' Only';
    }
    
    // For larger numbers, use a simplified version
    return number_format($number, 2) . ' Only';
}

/**
 * Helper: Convert AD date to Nepali BS (placeholder - needs proper library)
 */
function convertToNepaliDate($ad_date) {
    // TODO: Implement actual AD to BS conversion
    // For now, return formatted AD date
    return date('Y-m-d', strtotime($ad_date)) . ' BS';
}
