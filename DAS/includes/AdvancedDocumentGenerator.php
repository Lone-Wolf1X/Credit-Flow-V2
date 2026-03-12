<?php
/**
 * Advanced Document Generator
 * Handles complex document generation rules including grouping, aggregation, and specific Nepali legal formatting.
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/DocumentDataFetcher.php';
require_once __DIR__ . '/PlaceholderMapper.php';
require_once __DIR__ . '/DocumentGenerator.php'; // Reuse some logic if needed

use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Element\TextRun;

class AdvancedDocumentGenerator {
    private $conn;
    private $generated_dir;
    private $fetcher; // Helper for data fetching
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->generated_dir = __DIR__ . '/../generated_documents/';
        
        if (!file_exists($this->generated_dir)) {
            mkdir($this->generated_dir, 0777, true);
        }
    }

    /**
     * Generate Nepali Legal Identification Paragraph
     * Based on Gender and Marital Status rules.
     * 
     * @param array $person Person data (Borrower/Guarantor/Owner)
     * @return string Formatted paragraph
     */
    public function generateNepaliIdentificationString($person) {
        // Extract required fields with fallbacks
        $gender = strtolower($person['gender'] ?? '');
        $marital_status = strtolower($person['marital_status'] ?? '');
        
        // Names
        $grand_father = $person['grandfather_name'] ?? '___';
        $father = $person['father_name'] ?? '___';
        $spouse = $person['spouse_name'] ?? '___';
        $father_in_law = $person['father_in_law_name'] ?? '___'; 
        
        // 1. Family Introduction Rules
        $rel_string = "";
        
        if ($gender == 'male') {
            if ($marital_status == 'married') {
                $rel_string = "{$grand_father} ko naati, {$father} ko chhora, {$spouse} ki pati";
            } else {
                $rel_string = "{$grand_father} ko naati, {$father} ko chhora";
            }
        } elseif ($gender == 'female') {
            if ($marital_status == 'married') {
                $rel_string = "{$father_in_law} ko buhari, {$father} ko chhori, {$spouse} ko patni";
            } else {
                $rel_string = "{$grand_father} ko naatini, {$father} ko chhori";
            }
        } else {
            // Fallback
            $rel_string = "{$grand_father} ko naati/naatini, {$father} ko chhora/chhori";
        }
        
        // Personal Details
        $name = $person['full_name_nepali'] ?? $person['full_name']; 
        $age = $person['age'] ?? '___';
        
        // 2. Address Format (Strict Rule)
        $perm_dist = $person['permanent_district'] ?? '___';
        $perm_muni = $person['permanent_municipality'] ?? '___';
        $perm_ward = $person['permanent_ward_no'] ?? '___';
        
        $curr_dist = $person['current_district'] ?? '___';
        $curr_muni = $person['current_municipality'] ?? '___';
        $curr_ward = $person['current_ward_no'] ?? '___';
        
        $address_str = "{$perm_dist} jilla {$perm_muni} wada no {$perm_ward} sthayi thegana bhai haal {$curr_dist} jilla {$curr_muni} wada no {$curr_ward} basne";
        
        // 3. Citizenship Statement Rules
        $cit_no = $person['citizenship_no'] ?? '___';
        $cit_date = $person['citizenship_issue_date'] ?? '___';
        $cit_issue_district = $person['citizenship_issue_district'] ?? '___';
        
        // Authority Mapping
        $auth_type = $person['citizenship_issue_authority'] ?? 'District Administration Office';
        $auth_abbr = ($auth_type == 'Area Administration Office') ? 'ई.प्र.का' : 'जि.प्र.का';
        
        $citizenship_str = "(ना.प्र.नं {$cit_no}, {$cit_date} मा {$auth_abbr} {$cit_issue_district} बाट जारी";
        
        // Re-issue Logic
        $reissue_status = strtolower($person['citizenship_reissue_status'] ?? 'no');
        if ($reissue_status == 'yes') {
            $reissue_date = $person['citizenship_reissue_date'] ?? '___';
            $copy_type = $person['citizenship_copy_type'] ?? '___'; // First/Second
            $citizenship_str .= " भई मिति {$reissue_date} मा {$copy_type} प्रतिलिपि जारी";
        }
        
        $citizenship_str .= ")";
        
        // 4. Final Output Construction
        // Pattern: [Family Intro], [Address] [Age] barsha ko/ki [Name] [Citizenship Statement]
        // Note: Joining with comma for flow
        $paragraph = "{$rel_string}, {$address_str} {$age} barsha ko/ki {$name} {$citizenship_str}";
        
        return $paragraph;
    }
    
    /**
     * Helper to format address string (Deprecated for ID string but useful for other parts)
     */
    private function formatAddress($person, $type) {
        $prefix = ($type == 'permanent') ? 'f' : 't'; 
        
        $district = $person[$prefix . '_district'] ?? '';
        $municipality = $person[$prefix . '_municipality'] ?? ''; 
        $ward = $person[$prefix . '_ward_no'] ?? '';
        
        return "{$district} Jilla, {$municipality} Palika, Wada Nam {$ward}";
    }

    /**
     * Generate Mortgage Deeds (Grouped by Owner + Malpot)
     */
    public function generateMortgageDeeds($customer_profile_id) {
        return $this->generateGroupedLandDocuments($customer_profile_id, 'Mortgage_Deed');
    }
    
    /**
     * Generate Rokka Letters (Grouped by Owner + Malpot)
     */
    public function generateRokkaLetters($customer_profile_id) {
        return $this->generateGroupedLandDocuments($customer_profile_id, 'Rokka_Letter');
    }

    /**
     * Core logic for grouped land documents
     */
    private function generateGroupedLandDocuments($customer_profile_id, $docType) {
        $this->fetcher = new DocumentDataFetcher($this->conn, $customer_profile_id);
        $data = $this->fetcher->fetchAllData();
        
        if (empty($data['collateral'])) {
            return ['success' => false, 'message' => 'No collateral found.'];
        }

        // Filter for Land only
        $lands = array_filter($data['collateral'], function($c) {
            return strtolower($c['collateral_type']) == 'land';
        });

        if (empty($lands)) {
            return ['success' => false, 'message' => 'No land collateral found.'];
        }

        // Group Logic
        $groups = [];
        foreach ($lands as $land) {
            $owner_id = $land['owner_id'];
            $owner_type = $land['owner_type'];
            $malpot = trim($land['land_malpot_office']);
            
            $key = "{$owner_id}_{$owner_type}_{$malpot}";
            
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'owner_id' => $owner_id,
                    'owner_type' => $owner_type,
                    'malpot' => $malpot,
                    'items' => []
                ];
            }
            $groups[$key]['items'][] = $land;
        }

        $results = [];
        
        // Generate for each group
        foreach ($groups as $group) {
            $res = $this->generateSingleGroupDocument($data, $group, $docType);
            $results[] = $res;
        }

        return ['success' => true, 'results' => $results];
    }

    private function generateSingleGroupDocument($allData, $group, $docType) {
        try {
            // 1. Prepare Aggregated Data for the Group
            // We need to merge individual land details into comma separated strings
            // e.g. Kitta: 101, 102 | Area: 400, 500
            
            $kitta_nos = [];
            $areas = [];
            $sheet_nos = [];
            $locations = [];
            
            foreach ($group['items'] as $item) {
                $kitta_nos[] = $item['land_kitta_no'];
                $areas[] = $item['land_area'];
                $sheet_nos[] = $item['land_sheet_no'];
                
                // Format single location
                // Assuming location is same for same malpot usually, but safer to list if different
                // But for Rokka, usually we say "Ward No X, Y..."
            }
    
            $aggregated_placeholders = [
                'LAND_KITTA_ALL' => implode(', ', $kitta_nos),
                'LAND_AREA_ALL' => implode(', ', $areas),
                'LAND_SHEET_ALL' => implode(', ', $sheet_nos),
                'MALPOT_OFFICE' => $group['malpot']
            ];
            
            // Map Owner Details
            // We need to find the owner in $allData['borrowers'] or $allData['guarantors']
            $owner_details = $this->findPerson($allData, $group['owner_id'], $group['owner_type']);
            if ($owner_details) {
                $aggregated_placeholders['OWNER_NAME'] = $owner_details['full_name_nepali'] ?? $owner_details['full_name'];
                $aggregated_placeholders['OWNER_ADDRESS'] = $this->formatAddress($owner_details, 'permanent');
                $aggregated_placeholders['OWNER_FATHER'] = $owner_details['father_name'];
                $aggregated_placeholders['OWNER_GRANDFATHER'] = $owner_details['grandfather_name'];
                
                // NEW: Nepali Legal ID String
                $aggregated_placeholders['OWNER_LEGAL_ID'] = $this->generateNepaliIdentificationString($owner_details);
            }
    
            // 2. Load Template (Need logic to pick template based on DocType)
            // For now, hardcode or simple mapping
            $templateName = ($docType == 'Mortgage_Deed') ? 'mortgage_deed.docx' : 'rokka_letter.docx';
            // TODO: Get actual path
            $templatePath = __DIR__ . '/../templates/ptl/' . $templateName; 
            
            if (!file_exists($templatePath)) {
                return ['success' => false, 'message' => "Template $templateName not found at $templatePath."];
            }
    
            $template = new TemplateProcessor($templatePath);
            
            // 3. Set Values
            foreach ($aggregated_placeholders as $k => $v) {
                $template->setValue($k, $v);
            }
            
            // Also map standard loan details (Limit, Borrower Name, etc.)
            // reusing PlaceholderMapper might be good?
            // For now, basic loan mapping:
            $template->setValue('LOAN_LIMIT_FIGURE', $allData['loan']['loan_amount'] ?? '');
            
            // 4. Save
            $filename = "{$docType}_" . ($owner_details['full_name'] ?? 'Unknown') . "_" . date('YmdHis') . ".docx";
            $outputPath = $this->generated_dir . $filename;
            $template->saveAs($outputPath);
            
            return ['success' => true, 'path' => $outputPath];
        } catch (Exception $e) {
            return ['success' => false, 'error' => "Error generating $docType: " . $e->getMessage()];
        }
    }

    /**
     * Generate Personal Guarantees (One per Guarantor)
     */
    public function generatePersonalGuarantees($customer_profile_id) {
        $data = $this->fetchDataIfNotFetched($customer_profile_id);
        $results = [];

        if (empty($data['guarantors'])) {
            return ['success' => false, 'message' => 'No guarantors found.'];
        }

        foreach ($data['guarantors'] as $guarantor) {
            $placeholders = $this->mapPersonPlaceholders($guarantor, 'GUARANTOR');
            
            // Add Loan Info
            $placeholders['LOAN_LIMIT_FIGURE'] = $data['loan']['loan_amount'] ?? '';
            
            $res = $this->generateSingleDocument('personal_guarantee.docx', $placeholders, "Personal_Guarantee_{$guarantor['full_name']}");
            $results[] = $res;
        }

        return ['success' => true, 'results' => $results];
    }

    /**
     * Generate Power of Attorney (One per Borrower + Guarantor)
     */
    public function generatePowerOfAttorney($customer_profile_id) {
        $data = $this->fetchDataIfNotFetched($customer_profile_id);
        $results = [];

        $allPeople = array_merge($data['borrowers'] ?? [], $data['guarantors'] ?? []); // Simplified merge

        foreach ($allPeople as $person) {
            $role = isset($person['is_guarantor']) ? 'GUARANTOR' : 'BORROWER'; // Simplified detection
            $placeholders = $this->mapPersonPlaceholders($person, 'PERSON');
            
            $res = $this->generateSingleDocument('power_of_attorney.docx', $placeholders, "POA_{$person['full_name']}");
            $results[] = $res;
        }

        return ['success' => true, 'results' => $results];
    }

    /**
     * Generate Promissory Note (Aggregated)
     */
    public function generatePromissoryNote($customer_profile_id) {
        $data = $this->fetchDataIfNotFetched($customer_profile_id);
        
        // Aggregate Names
        $borrowerNames = array_map(function($b) { return $b['full_name']; }, $data['borrowers']);
        $guarantorNames = array_map(function($g) { return $g['full_name']; }, $data['guarantors']);
        
        $placeholders = [
            'ALL_BORROWERS' => implode(', ', $borrowerNames),
            'ALL_GUARANTORS' => implode(', ', $guarantorNames),
            'LOAN_LIMIT_FIGURE' => $data['loan']['loan_amount'] ?? ''
        ];

        return $this->generateSingleDocument('promissory_note.docx', $placeholders, "Promissory_Note");
    }

    /**
     * Generate Loan Deed (Master)
     */
    public function generateLoanDeed($customer_profile_id) {
        // Similar to Promissory but with more details
        // reused existing logic? Or simple placeholder mapping
        $data = $this->fetchDataIfNotFetched($customer_profile_id);
         $placeholders = [
            'LOAN_LIMIT_FIGURE' => $data['loan']['loan_amount'] ?? ''
        ];
        // TODO: Full Loan Deed Mapping
        return $this->generateSingleDocument('loan_deed.docx', $placeholders, "Loan_Deed");
    }

    /**
     * Generate Legal Heir Consent
     */
    public function generateLegalHeirConsent($customer_profile_id) {
        $data = $this->fetchDataIfNotFetched($customer_profile_id);
        $results = [];
        
        // Check collaterals for is_legal_heir_applicable
        foreach ($data['collateral'] as $collateral) {
             // We need to check database directly for is_legal_heir_applicable if not in fetcher data
             // Assuming fetcher might not have new column yet unless we update fetcher. 
             // Let's query legal_heirs table directly for linked collateral
        }
        
        // Better approach: Query legal_heirs table for this profile
        $stmt = $this->conn->prepare("SELECT * FROM legal_heirs WHERE customer_profile_id = ? ORDER BY collateral_id");
        $stmt->bind_param("i", $customer_profile_id);
        $stmt->execute();
        $heirs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (empty($heirs)) {
             return ['success' => false, 'message' => 'No legal heirs found.'];
        }

        // Group heirs by collateral owner (via collateral_id -> owner lookup)
        // Group by collateral_id first
        $heirsByCollateral = [];
        foreach ($heirs as $heir) {
            $heirsByCollateral[$heir['collateral_id']][] = $heir;
        }

        foreach ($heirsByCollateral as $col_id => $groupHeirs) {
            // Get Owner Name from Collateral
            // We need to look up collateral list
            // Optimization: Find in $data['collateral']
            $colData = null;
            foreach ($data['collateral'] as $c) {
                if ($c['id'] == $col_id) { $colData = $c; break; }
            }
            
            if (!$colData) continue;
            
            // Map Heirs to String/Table
            $heirNames = array_map(function($h) { return $h['name']; }, $groupHeirs);
            
            $placeholders = [
                'HEIR_NAMES_ALL' => implode(', ', $heirNames),
                'OWNER_NAME' => $this->findPersonName($data, $colData['owner_id'], $colData['owner_type'])
            ];
            
            $res = $this->generateSingleDocument('legal_heir_consent.docx', $placeholders, "Legal_Heir_Consent_" . $col_id);
            $results[] = $res;
        }

        return ['success' => true, 'results' => $results];
    }

    // --- Helpers ---

    private function generateSingleDocument($templateName, $placeholders, $outputNameBase) {
        $templatePath = __DIR__ . '/../templates/ptl/' . $templateName; 
        
        // Fallback for missing templates during dev
        if (!file_exists($templatePath)) {
            // return ['success' => false, 'message' => "Template $templateName not found."];
             // Create dummy for testing? No, just return error
             return ['success' => false, 'error' => "Template $templateName not found at $templatePath"];
        }

        try {
            $template = new TemplateProcessor($templatePath);
            foreach ($placeholders as $k => $v) {
                $template->setValue($k, $v);
            }
            $filename = $outputNameBase . "_" . date('YmdHis') . ".docx";
            $outputPath = $this->generated_dir . $filename;
            $template->saveAs($outputPath);
            return ['success' => true, 'path' => $outputPath];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function fetchDataIfNotFetched($profile_id) {
        if (!$this->fetcher) {
            $this->fetcher = new DocumentDataFetcher($this->conn, $profile_id);
        }
        return $this->fetcher->fetchAllData();
    }

    private function mapPersonPlaceholders($person, $prefix) {
        return [
            "{$prefix}_NAME" => $person['full_name_nepali'] ?? $person['full_name'],
            "{$prefix}_ADDRESS" => $this->formatAddress($person, 'permanent'),
            "{$prefix}_FATHER" => $person['father_name'],
            "{$prefix}_GRANDFATHER" => $person['grandfather_name'],
            "{$prefix}_LEGAL_ID" => $this->generateNepaliIdentificationString($person)
        ];
    }
    
    private function findPersonName($data, $id, $type) {
        $p = $this->findPerson($data, $id, $type);
        return $p ? ($p['full_name_nepali'] ?? $p['full_name']) : 'Unknown';
    }

    private function findPerson($data, $id, $type) {
        // Handle case sensitivity for Owner Type
        $key = (stripos($type, 'borrower') !== false) ? 'borrowers' : 'guarantors';
        $list = $data[$key] ?? [];
        
        foreach ($list as $p) {
            if ($p['id'] == $id) return $p;
        }
        return null;
    }
}
