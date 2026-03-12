<?php
/**
 * Placeholder Mapper
 * Maps database data to template placeholders
 */

require_once __DIR__ . '/ConditionalRules.php';
require_once __DIR__ . '/PreetiFontConverter.php';

class PlaceholderMapper {
    private $data;
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    /**
     * Map all data to placeholders for mortgage deed
     */
    public function mapForMortgageDeed($collateral_index = 0) {
        $placeholders = [];
        
        // Map borrowers and co-borrowers
        if (!empty($this->data['borrowers'])) {
            foreach ($this->data['borrowers'] as $index => $borrower) {
                $number = $index + 1;
                $prefix = ($index === 0) ? "BR{$number}" : "CO" . ($number - 1);
                $placeholders = array_merge($placeholders, $this->mapBorrower($borrower, $prefix));
            }
        }
        
        // Map guarantors
        if (!empty($this->data['guarantors'])) {
            foreach ($this->data['guarantors'] as $index => $guarantor) {
                $number = $index + 1;
                $placeholders = array_merge($placeholders, $this->mapGuarantor($guarantor, $number));
            }
        }
        
        // Map collateral
        if (!empty($this->data['collateral'])) {
            $collateral = $this->data['collateral'][$collateral_index] ?? $this->data['collateral'][0];
            $collateralMap = $this->mapCollateral($collateral);
            foreach ($collateralMap as $key => $val) {
                 // Convert COL_KEY to COL1_KEY
                 $newKey = str_replace('COL_', 'COL1_', $key);
                 $placeholders[$newKey] = $val;
            }
        }
        
        // Map loan details
        $placeholders = array_merge($placeholders, $this->mapLoanDetails());
        
        // Map branch details
        $placeholders = array_merge($placeholders, $this->mapBranchDetails());
        
        // Map serial numbers
        $placeholders['SN1'] = '१';
        
        return $placeholders;
    }
    
    /**
     * Map borrower to BR placeholders
     */
    private function mapBorrower($borrower, $prefix) {
        // Ensure prefix ends with _
        if (substr($prefix, -1) !== '_') {
            $prefix .= '_';
        }

        return [
            $prefix . 'NM_NP' => $this->convertPreeti($borrower['full_name_np'] ?? $borrower['full_name'] ?? ''),
            $prefix . 'NM_EN' => $borrower['full_name_en'] ?? '',
            $prefix . 'DOB' => $this->convertDateToBS($borrower['date_of_birth'] ?? ''),
            $prefix . 'AGE' => $this->calculateAge($borrower['date_of_birth'] ?? ''),
            $prefix . 'GENDER' => $this->translateGender($borrower['gender'] ?? ''),
            $prefix . 'REL_STATUS' => $this->translateMaritalStatus($borrower['relationship_status'] ?? ''),
            $prefix . 'FATHER' => $this->convertPreeti($borrower['father_name'] ?? ''),
            $prefix . 'MOTHER' => $this->convertPreeti($borrower['mother_name'] ?? ''),
            $prefix . 'GF' => $this->convertPreeti($borrower['grandfather_name'] ?? ''),
            $prefix . 'GM' => $this->convertPreeti($borrower['grandmother_name'] ?? ''),
            $prefix . 'SPOUSE' => $this->convertPreeti($borrower['spouse_name'] ?? ''),
            $prefix . 'SON' => $this->convertPreeti($borrower['son_name'] ?? ''),
            $prefix . 'DAUGHTER' => $this->convertPreeti($borrower['daughter_name'] ?? ''),
            $prefix . 'FIL' => $this->convertPreeti($borrower['father_in_law'] ?? ''),
            $prefix . 'MIL' => $this->convertPreeti($borrower['mother_in_law'] ?? ''),
            $prefix . 'CIT_NO' => $this->convertToNepaliNumbers($borrower['citizenship_number'] ?? ''),
            $prefix . 'ISS_DT' => $this->convertDateToBS($borrower['id_issue_date'] ?? ''),
            $prefix . 'ID_DIST' => $this->convertPreeti($borrower['id_issue_district'] ?? ''),
            $prefix . 'ID_AUTH' => $this->convertPreeti($borrower['id_issue_authority'] ?? ''),
            $prefix . 'REISS_DT' => $this->convertDateToBS($borrower['id_reissue_date'] ?? ''),
            $prefix . 'REISS_TIMES' => $this->convertToNepaliNumbers($borrower['reissue_count'] ?? '0'),
            $prefix . 'P_CNTRY' => $borrower['perm_country'] ?? 'Nepal',
            $prefix . 'P_PROV' => $this->convertPreeti($borrower['perm_province'] ?? ''),
            $prefix . 'P_DIST' => $this->convertPreeti($borrower['perm_district'] ?? ''),
            $prefix . 'P_MUN' => $this->convertPreeti($borrower['perm_municipality_vdc'] ?? ''),
            $prefix . 'P_WARD' => $this->convertToNepaliNumbers($borrower['perm_ward_no'] ?? ''),
            $prefix . 'P_TOLE' => $this->convertPreeti($borrower['perm_town_village'] ?? ''),
            $prefix . 'P_STREET' => $this->convertPreeti($borrower['perm_street_name'] ?? ''),
            $prefix . 'P_STREET_NO' => $borrower['perm_street_number'] ?? '',
            $prefix . 'T_CNTRY' => $borrower['temp_country'] ?? 'Nepal',
            $prefix . 'T_PROV' => $this->convertPreeti($borrower['temp_province'] ?? ''),
            $prefix . 'T_DIST' => $this->convertPreeti($borrower['temp_district'] ?? ''),
            $prefix . 'T_MUN' => $this->convertPreeti($borrower['temp_municipality_vdc'] ?? ''),
            $prefix . 'T_WARD' => $this->convertToNepaliNumbers($borrower['temp_ward_no'] ?? ''),
            $prefix . 'T_TOLE' => $this->convertPreeti($borrower['temp_town_village'] ?? ''),
            $prefix . 'T_STREET' => $this->convertPreeti($borrower['temp_street_name'] ?? ''),
            $prefix . 'T_STREET_NO' => $borrower['temp_street_number'] ?? '',
            $prefix . 'CNT_NO' => $this->convertToNepaliNumbers($borrower['contact_number'] ?? ''),
            $prefix . 'EMAIL' => $borrower['email'] ?? '',
            $prefix . 'COMP_NM' => $borrower['company_name'] ?? '',
            $prefix . 'REG_NO' => $this->convertToNepaliNumbers($borrower['registration_no'] ?? ''),
            $prefix . 'REG_DT' => $this->convertDateToBS($borrower['registration_date'] ?? ''),
            $prefix . 'PAN' => $this->convertToNepaliNumbers($borrower['pan_number'] ?? ''),
        ];
    }

    /**
     * Map guarantor to GR placeholders
     */
    private function mapGuarantor($guarantor, $number) {
        $prefix = 'GR' . $number . '_';
        // Reuse mapBorrower logic as fields are identical
        return $this->mapBorrower($guarantor, $prefix);
    }

    /**
     * Map collateral owner to CO placeholders
     */
    private function mapCollateralOwner($owner, $number) {
        $prefix = 'CO' . $number . '_';
        
        return [
            $prefix . 'NM_NP' => $owner['full_name'] ?? '',
            $prefix . 'NM_EN' => $owner['full_name_en'] ?? '',
            $prefix . 'DOB' => $this->convertDateToBS($owner['date_of_birth'] ?? ''),
            $prefix . 'AGE' => $this->calculateAge($owner['date_of_birth'] ?? ''),
            $prefix . 'FATHER' => $owner['father_name'] ?? '',
            $prefix . 'MOTHER' => $owner['mother_name'] ?? '',
            $prefix . 'GF' => $owner['grandfather_name'] ?? '',
            $prefix . 'GM' => $owner['grandmother_name'] ?? '',
            $prefix . 'SPOUSE' => $owner['spouse_name'] ?? '',
            $prefix . 'CIT_NO' => $this->convertToNepaliNumbers($owner['citizenship_number'] ?? ''),
            $prefix . 'ISS_DT' => $this->convertDateToBS($owner['id_issue_date'] ?? ''),
            $prefix . 'ID_DIST' => $owner['id_issue_district'] ?? '',
            $prefix . 'ID_AUTH' => $owner['id_issue_authority'] ?? '',
            $prefix . 'REISS_DT' => $this->convertDateToBS($owner['id_reissue_date'] ?? ''),
            $prefix . 'REISS_TIMES' => $this->convertToNepaliNumbers($owner['reissue_count'] ?? '0'),
            $prefix . 'P_DIST' => $owner['perm_district'] ?? '',
            $prefix . 'P_MUN' => $owner['perm_municipality_vdc'] ?? '',
            $prefix . 'P_WARD' => $this->convertToNepaliNumbers($owner['perm_ward_no'] ?? ''),
            $prefix . 'T_DIST' => $owner['temp_district'] ?? '',
            $prefix . 'T_MUN' => $owner['temp_municipality_vdc'] ?? '',
            $prefix . 'T_WARD' => $this->convertToNepaliNumbers($owner['temp_ward_no'] ?? ''),
        ];
    }
    
    /**
     * Map collateral/property details
     */
    private function mapCollateral($collateral) {
        $data = [
            'COL_PROV' => $collateral['land_province'] ?? '',
            'COL_DIST' => $collateral['land_district'] ?? '',
            'COL_MUN' => $collateral['land_municipality_vdc'] ?? '',
            'COL_WARD' => $this->convertToNepaliNumbers($collateral['land_ward_no'] ?? ''),
            'COL_SHEET_NO' => $this->convertToNepaliNumbers($collateral['land_sheet_no'] ?? ''),
            'COL_KITTA_NO' => $this->convertToNepaliNumbers($collateral['land_kitta_no'] ?? ''),
            'COL_KITTA_WORDS' => $collateral['land_kitta_no_words'] ?? $this->numberToNepaliWords($collateral['land_kitta_no'] ?? 0),
            'COL_AREA' => $collateral['land_area'] ?? '',
            'COL_KHANDE' => $collateral['land_khande'] ?? '',
            'COL_BIRAHA' => $collateral['land_biraha'] ?? '',
            'COL_KISIM' => $collateral['land_kisim'] ?? '',
            'COL_GUTHI_MOHI' => $collateral['land_guthi_mohi_name'] ?? '',
            'COL_DHITO_MULYA' => $this->formatAmount($collateral['land_dhito_parit_mulya'] ?? 0),
            'COL_REMARKS' => $collateral['land_remarks'] ?? '',
        ];

        // Add Owner Details
        $owner = $this->getCollateralOwner($collateral);
        if ($owner) {
            // Map owner details with COL_ prefix
            // We reuse mapCollateralOwner logic but need to adapt keys from CO{n}_ to COL_
            // So we manually map here for clarity and correct prefixing
            $ownerMap = [
                'COL_NM_NP' => $owner['full_name'] ?? '', // full_name is usually Nepali in this DB
                'COL_NM_EN' => $owner['full_name_en'] ?? '',
                'COL_GEN' => $this->convertPreeti($owner['gender'] ?? ''),
                'COL_DOB' => $this->convertDateToBS($owner['date_of_birth'] ?? ''),
                'COL_AGE' => $this->calculateAge($owner['date_of_birth'] ?? ''),
                'COL_FATHER' => $owner['father_name'] ?? '',
                'COL_MOTHER' => $owner['mother_name'] ?? '',
                'COL_GF' => $owner['grandfather_name'] ?? '',
                'COL_GM' => $owner['grandmother_name'] ?? '',
                'COL_SPOUSE' => $owner['spouse_name'] ?? '',
                'COL_CIT_NO' => $this->convertToNepaliNumbers($owner['citizenship_number'] ?? ''),
                'COL_ISS_DT' => $this->convertDateToBS($owner['id_issue_date'] ?? ''),
                'COL_ID_DIST' => $owner['id_issue_district'] ?? '',
                'COL_ID_AUTH' => $owner['id_issue_authority'] ?? '',
                'COL_REISS_DT' => $this->convertDateToBS($owner['id_reissue_date'] ?? ''),
                'COL_P_DIST' => $owner['perm_district'] ?? '',
                'COL_P_MUN' => $owner['perm_municipality_vdc'] ?? '',
                'COL_P_WARD' => $this->convertToNepaliNumbers($owner['perm_ward_no'] ?? ''),
                'COL_T_DIST' => $owner['temp_district'] ?? '',
                'COL_T_MUN' => $owner['temp_municipality_vdc'] ?? '',
                'COL_T_WARD' => $this->convertToNepaliNumbers($owner['temp_ward_no'] ?? ''),
            ];
            $data = array_merge($data, $ownerMap);
        }

        return $data;
    }
    
    /**
     * Map loan details
     */
    private function mapLoanDetails() {
        $loan = $this->data['loan'] ?? [];
        $limit = $this->data['limit'] ?? [];
        
        // Use primary borrower if loan name is empty
        $primaryBorrowerName = $this->getPrimaryBorrowerName();
        $limitAmount = $limit['amount'] ?? 0;
        
        // Sanitize amount
        $cleanAmount = (float)str_replace([',', ' '], '', $limitAmount);
        $amountWords = NepaliNumberToWords::convert($cleanAmount); // Use clean amount

        return [
            'LN_BR_NAME' => $primaryBorrowerName,
            // Date mappings removed - now handled by DocumentRuleEngine with proper conversion
            'LN_APPR_REF' => $loan['approval_ref_no'] ?? '',
            'LN_SCHEME' => $loan['loan_scheme_name'] ?? '',
            'LN_AMT' => $this->formatAmount($limitAmount),
            'LN_AMT_WORDS' => $amountWords,
            'LN_PURPOSE' => $this->convertPreeti($loan['loan_purpose'] ?? ''), 
            'LN_REMARKS' => $loan['remarks'] ?? '',
        ];
    }
    
    /**
     * Get collateral owner from data
     */
    private function getCollateralOwner($collateral) {
        if ($collateral['owner_type'] == 'Borrower') {
            foreach ($this->data['borrowers'] as $borrower) {
                if ($borrower['id'] == $collateral['owner_id']) {
                    return $borrower;
                }
            }
        } else {
            foreach ($this->data['guarantors'] as $guarantor) {
                if ($guarantor['id'] == $collateral['owner_id']) {
                    return $guarantor;
                }
            }
        }
        return [];
    }
    
    /**
     * Convert Preeti to Unicode using PreetiFontConverter
     */
    private function convertPreeti($text) {
        if (empty($text)) return '';
        return PreetiFontConverter::convert($text);
    }
    
    /**
     * Convert English numbers to Nepali
     */
    private function convertToNepaliNumbers($text) {
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $nepali = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
        return str_replace($english, $nepali, $text);
    }
    
    /**
     * Convert AD date to BS (placeholder)
     */
    private function convertDateToBS($adDate) {
        if (empty($adDate)) return '';
        // TODO: Implement actual AD to BS conversion
        // For now, return formatted Nepali date
        return '२०८१-०९-२३';
    }
    
    /**
     * Calculate age from date of birth
     */
    private function calculateAge($dob) {
        if (empty($dob)) return '';
        $birthDate = new DateTime($dob);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        return $this->convertToNepaliNumbers((string)$age);
    }
    
    /**
     * Format amount with commas
     */
    private function formatAmount($amount) {
        if (empty($amount)) return '';
        
        // Remove commas and spaces if present to ensure it's numeric
        $cleanAmount = str_replace([',', ' '], '', $amount);
        
        if (is_numeric($cleanAmount)) {
            $formatted = number_format((float)$cleanAmount, 2);
            return 'रू ' . $this->convertToNepaliNumbers($formatted);
        }
        
        // If not numeric (maybe already formatted or text), return as is
        return $amount;
    }
    
    /**
     * Convert number to Nepali words (simple version)
     */
    private function numberToNepaliWords($number) {
        // TODO: Implement proper Nepali number to words conversion
        // For now, return the number as is
        return (string)$number;
    }
    
    // Map branch details for document generation
    public function mapBranchDetails() {
        $placeholders = [];
        
        // Get user's effective branch (considering deputation)
        if (!isset($this->data['branch']) || empty($this->data['branch'])) {
            return $placeholders;
        }
        
        $branch = $this->data['branch'];
        
        // Map branch placeholders using short format (matching BR1_, GR1_ convention)
        $placeholders['BR_NM_NP'] = $this->convertPreeti($branch['branch_name_np'] ?? '');
        $placeholders['BR_NM_EN'] = $branch['branch_name_en'] ?? '';
        $placeholders['BR_SOL'] = $branch['sol_id'] ?? '';
        
        // Address fields (Nepali only to match system)
        $placeholders['BR_PROV'] = $this->convertPreeti($branch['province_np'] ?? '');
        $placeholders['BR_DIST'] = $this->convertPreeti($branch['district_np'] ?? '');
        $placeholders['BR_MUN'] = $this->convertPreeti($branch['municipality_np'] ?? '');
        $placeholders['BR_WARD'] = $branch['ward_no'] ?? '';
        $placeholders['BR_TOLE'] = $this->convertPreeti($branch['tole_location_np'] ?? '');
        
        // Contact details
        $placeholders['BR_PHONE'] = $branch['phone'] ?? '';
        $placeholders['BR_EMAIL'] = $branch['email'] ?? '';
        
        // Full address (Nepali)
        $address_parts = array_filter([
            $branch['tole_location_np'] ?? '',
            $branch['ward_no'] ? 'वडा नं. ' . $this->convertToNepaliNumbers($branch['ward_no']) : '',
            $branch['municipality_np'] ?? '',
            $branch['district_np'] ?? '',
            $branch['province_np'] ?? ''
        ]);
        $placeholders['BR_ADDR'] = $this->convertPreeti(implode(', ', $address_parts));
        
        return $placeholders;
    }
    
    /**
     * Get a specific person field value with conversion
     * Used by DocumentRuleEngine
     */
    public function getPersonFieldValue($personData, $fieldName) {
        $value = $personData[$fieldName] ?? '';
        
        // Apply conversions based on field type
        switch ($fieldName) {
            case 'full_name_np':
            case 'father_name':
            case 'mother_name':
            case 'grandfather_name':
            case 'grandmother_name':
            case 'spouse_name':
            case 'son_name':
            case 'daughter_name':
            case 'father_in_law':
            case 'mother_in_law':
            case 'perm_district':
            case 'perm_municipality_vdc':
            case 'perm_town_village':
            case 'temp_district':
            case 'temp_municipality_vdc':
            case 'temp_town_village':
            case 'id_issue_district':
            case 'id_issue_authority':
                // Return value directly as we are using Unicode
                return $value;
                
            case 'citizenship_number':
            case 'perm_ward_no':
            case 'temp_ward_no':
            case 'reissue_times':
                return $this->convertToNepaliNumbers($value);
                
            case 'date_of_birth':
            case 'id_issue_date':
            case 'id_reissue_date':
                return $this->convertDateToBS($value);
                
            case 'age':
                return $this->calculateAge($personData['date_of_birth'] ?? '');
                
            default:
                return $value;
        }
    }
    
    /**
     * Get a specific collateral field value with conversion
     */
    public function getCollateralFieldValue($collateralData, $fieldName) {
        $value = $collateralData[$fieldName] ?? '';
        
        switch ($fieldName) {
            case 'land_province':
            case 'land_district':
            case 'land_municipality_vdc':
            case 'land_khande':
            case 'land_biraha':
            case 'land_kisim':
            case 'land_kisim':
            case 'land_guthi_mohi_name':
            case 'land_malpot_office':
                return $this->convertPreeti($value);
                
            case 'land_ward_no':
            case 'land_sheet_no':
            case 'land_kitta_no':
                return $this->convertToNepaliNumbers($value);
                
            case 'dhito_parit_mulya':
                // Map from DB column land_dhito_parit_mulya
                return $this->formatAmount($collateralData['land_dhito_parit_mulya'] ?? 0);

                
            case 'dhito_mulya_words':
                return NepaliNumberToWords::convert((float)str_replace([',', ' '], '', $collateralData['land_dhito_parit_mulya'] ?? 0));
                
            // Owner Details Conversions
            case 'owner_citizenship_number':
                return $this->convertToNepaliNumbers($value);
            
            case 'owner_name_np':
            case 'owner_father_name':
            case 'owner_id_district':
                return $value; // Unicode, return as is
                
            default:
                return $value;
        }
    }
    
    /**
     * Get a specific loan field value with conversion
     */
    public function getLoanFieldValue($loanData, $fieldName) {
        // Handle nested limit data
        $limitData = $loanData['limit'] ?? [];
        
        switch ($fieldName) {
            case 'loan_amount':
                $amount = $limitData['amount'] ?? 0;
                return $this->formatAmount($amount);
                
            case 'loan_amount_words':
                $amount = $limitData['amount'] ?? 0;
                $cleanAmount = (float)str_replace([',', ' '], '', $amount);
                return NepaliNumberToWords::convert($cleanAmount);
                
            case 'loan_scheme':
                return $this->convertPreeti($loanData['loan_scheme_name'] ?? '');
                
            case 'loan_purpose':
                return $this->convertPreeti($loanData['loan_purpose'] ?? $limitData['loan_purpose'] ?? '');
                
            case 'loan_approved_date':
                return $this->convertDateToBS($loanData['loan_approved_date'] ?? '');
                
            case 'borrower_name':
                // This would come from borrowers data
                return '';
                
            default:
                return $loanData[$fieldName] ?? '';
        }
    }
}
