<?php
/**
 * Placeholder Mapping Library
 * Short placeholder codes for document generation
 * Prevents formatting issues in Word templates
 */

class PlaceholderLibrary {
    
    /**
     * Complete placeholder mapping with short codes
     * Database Field → Short Placeholder Code
     */
    public static $FIELD_MAPPING = [
        
        // ==========================================
        // PERSON FIELDS (Borrower, Guarantor, Collateral Owner)
        // ==========================================
        // Pattern: {TYPE}{NUMBER}_{FIELD}
        // Example: BR1_NM_NP = Borrower 1 Name in Nepali
        
        'person_fields' => [
            'full_name_np' => 'NM_NP',              // Name in Nepali (Preeti → Unicode)
            'full_name_en' => 'NM_EN',           // Name in English
            'date_of_birth' => 'DOB',            // Date of Birth (BS format)
            'age' => 'AGE',                      // Computed age
            'gender' => 'GENDER',                // पुरुष/महिला/अन्य
            'relationship_status' => 'REL_STATUS', // Married/Unmarried
            
            // Citizenship/ID
            'citizenship_number' => 'CIT_NO',    // Citizenship number
            'id_issue_date' => 'ISS_DT',         // Issue date (BS)
            'id_issue_district' => 'ID_DIST',    // Issue district
            'id_issue_authority' => 'ID_AUTH',   // Issue authority
            'id_reissue_date' => 'REISS_DT',     // Reissue date (BS)
            'reissue_times' => 'REISS_TIMES',    // Re-issue times (Pratilipi)
            
            // Permanent Address
            'perm_country' => 'P_CNTRY',         // Country
            'perm_province' => 'P_PROV',         // Province
            'perm_district' => 'P_DIST',         // District
            'perm_municipality_vdc' => 'P_MUN',  // Municipality/VDC
            'perm_ward_no' => 'P_WARD',          // Ward number
            'perm_town_village' => 'P_TOLE',     // Tole/Village
            'perm_street_name' => 'P_STREET',    // Street name
            'perm_street_number' => 'P_STREET_NO', // Street number
            
            // Temporary Address
            'temp_country' => 'T_CNTRY',         // Country
            'temp_province' => 'T_PROV',         // Province
            'temp_district' => 'T_DIST',         // District
            'temp_municipality_vdc' => 'T_MUN',  // Municipality/VDC
            'temp_ward_no' => 'T_WARD',          // Ward number
            'temp_town_village' => 'T_TOLE',     // Tole/Village
            'temp_street_name' => 'T_STREET',    // Street name
            'temp_street_number' => 'T_STREET_NO', // Street number
            
            // Family Relations
            'grandfather_name' => 'GF',          // Grandfather
            'grandmother_name' => 'GM',          // Grandmother
            'father_name' => 'FATHER',           // Father
            'mother_name' => 'MOTHER',           // Mother
            'spouse_name' => 'SPOUSE',           // Spouse
            'son_name' => 'SON',                 // Son
            'daughter_name' => 'DAUGHTER',       // Daughter
            'father_in_law' => 'FIL',            // Father-in-law
            'mother_in_law' => 'MIL',            // Mother-in-law
            
            // Contact
            'contact_number' => 'CNT_NO',        // Contact number
            'email' => 'EMAIL',                  // Email address
            
            // Corporate Fields (if applicable)
            'company_name' => 'COMP_NM',         // Company name
            'registration_no' => 'REG_NO',       // Registration number
            'registration_date' => 'REG_DT',     // Registration date
            'pan_number' => 'PAN',               // PAN number
        ],
        
        // ==========================================
        // LOAN FIELDS
        // ==========================================
        // Pattern: LN_{FIELD}
        
        'loan_fields' => [
            'loan_amount' => 'LN_AMT',           // Loan amount (formatted)
            'loan_amount_words' => 'LN_AMT_W',   // Amount in words (Nepali) (Updated)
            'loan_scheme' => 'LN_SCHEME',        // Loan scheme name
            'loan_purpose' => 'LN_PURPOSE',      // Purpose
            'loan_type' => 'LN_TYPE',            // Loan type
            'loan_approved_date' => 'LN_LSL_DT', // LSL Date (handled specially in DocumentRuleEngine)
            // loan_approved_date_ad and loan_approved_date_bs removed - these don't exist in DB
            // They are generated dynamically in DocumentRuleEngine from loan_approved_date
            'approval_ref_no' => 'LN_REF_NO',    // Approval Ref No
            'loan_tenure_months' => 'LN_TENURE', // Tenure in months
            'loan_tenure_years' => 'LN_TENURE_Y',// Tenure in years
            'interest_rate' => 'LN_RATE',        // Interest rate
            'base_rate' => 'LN_BASE',            // Base rate
            'premium' => 'LN_PREMIUM',           // Premium
            'borrower_name' => 'LN_BR_NAME',     // Primary borrower name
            'remarks' => 'LN_REMARKS',           // Remarks
        ],
        
        // ==========================================
        // COLLATERAL FIELDS
        // ==========================================
        // Pattern: COL{NUMBER}_{FIELD}
        
        'collateral_fields' => [
            // Location
            'land_province' => 'COL_PROV',       // Province
            'land_district' => 'COL_DIST',       // District
            'land_municipality_vdc' => 'COL_MUN',// Municipality/VDC
            'land_ward_no' => 'COL_WARD',        // Ward number
            
            // Land Details
            'land_sheet_no' => 'COL_SHEET_NO',   // Sheet number
            'land_kitta_no' => 'COL_KITTA_NO',   // Kitta number
            'land_kitta_no_words' => 'COL_KITTA_WORDS', // Kitta in words (Updated)
            'land_area' => 'COL_AREA',           // Area (e.g., 0-0-2-0)
            'land_khande' => 'COL_KHANDE',       // Khande
            'land_biraha' => 'COL_BIRAHA',       // Biraha
            'land_kisim' => 'COL_KISIM',         // Kisim (type)
            'land_guthi_mohi_name' => 'COL_GUTHI_MOHI', // Guthi/Mohi name
            'land_malpot_office' => 'COL_MALPOT',// Malpot office
            
            // Valuation
            'dhito_parit_mulya' => 'COL_DHITO_MULYA',     // Dhito Parit Mulya (Updated)
            'dhito_mulya_words' => 'COL_DM_W',   // Dhito Mulya in words
            
            // Vehicle Details
            'vehicle_model_no' => 'COL_VEH_MODEL', // Vehicle model
            'vehicle_engine_no' => 'COL_VEH_ENG',  // Engine number
            'vehicle_chassis_no' => 'COL_VEH_CHAS',// Chassis number
            'vehicle_no' => 'COL_VEH_NO',          // Vehicle registration number
            
            // Remarks
            'land_remarks' => 'COL_REMARKS',          // Remarks
            
            // Owner Details (Embedded in Collateral Block)
            'owner_name_np' => 'COL_OWNER_NM_NP',
            'owner_name_en' => 'COL_OWNER_NM_EN',
            'owner_citizenship_number' => 'COL_OWNER_CIT',
            'owner_father_name' => 'COL_OWNER_FATHER',
            'owner_id_district' => 'COL_OWNER_ID_DIST',
        ],
        
        // ==========================================
        // BANK/SYSTEM FIELDS
        // ==========================================
        
        'bank_fields' => [
            'bank_name_np' => 'BNK_NM_NP',       // Bank name Nepali
            'bank_name_en' => 'BNK_NM_EN',       // Bank name English
            'branch_name' => 'BNK_BRANCH',       // Branch name
            'branch_sol' => 'BNK_SOL',           // SOL code
            'branch_province' => 'BNK_PROV',     // Province
            'branch_address' => 'BNK_ADDR',      // Full address
            'document_date_bs' => 'DOC_DT_BS',   // Document date BS
            'document_date_ad' => 'DOC_DT_AD',   // Document date AD
        ],
        
        // ==========================================
        // WITNESS/AUTHORITY FIELDS
        // ==========================================
        
        'witness_fields' => [
            'witness1_name' => 'WIT1_NM',        // Witness 1 name
            'witness1_designation' => 'WIT1_DES',// Witness 1 designation
            'witness2_name' => 'WIT2_NM',        // Witness 2 name
            'witness2_designation' => 'WIT2_DES',// Witness 2 designation
            'authorized_signatory' => 'AUTH_SIGN',// Authorized signatory
        ],
        
        // ==========================================
        // SERIAL NUMBERS
        // ==========================================
        
        'serial_numbers' => [
            'sn1' => 'SN1',
            'sn2' => 'SN2',
            'sn3' => 'SN3',
            'sn4' => 'SN4',
            'sn5' => 'SN5',
        ],
    ];
    
    /**
     * Person type codes
     */
    public static $PERSON_TYPES = [
        'Borrower' => 'BR',
        'Guarantor' => 'GR',
        'CollateralOwner' => 'CO',
    ];
    
    /**
     * Generate placeholder for a person field
     * 
     * @param string $personType 'Borrower', 'Guarantor', or 'CollateralOwner'
     * @param int $number Person number (1, 2, 3, ...)
     * @param string $field Field name from person_fields
     * @return string Placeholder like ${BR1_NM_NP}
     */
    public static function getPersonPlaceholder($personType, $number, $field) {
        $typeCode = self::$PERSON_TYPES[$personType] ?? 'BR';
        $fieldCode = self::$FIELD_MAPPING['person_fields'][$field] ?? strtoupper($field);
        
        return "\${" . $typeCode . $number . "_" . $fieldCode . "}";
    }
    
    /**
     * Generate placeholder for loan field
     * 
     * @param string $field Field name from loan_fields
     * @return string Placeholder like ${LN_AMT}
     */
    public static function getLoanPlaceholder($field) {
        $fieldCode = self::$FIELD_MAPPING['loan_fields'][$field] ?? strtoupper($field);
        return "\${" . $fieldCode . "}";
    }
    
    /**
     * Generate placeholder for collateral field
     * 
     * @param int $number Collateral number (1, 2, 3, ...)
     * @param string $field Field name from collateral_fields
     * @return string Placeholder like ${COL1_KITTA_NO}
     */
    public static function getCollateralPlaceholder($number, $field) {
        $fieldCode = self::$FIELD_MAPPING['collateral_fields'][$field] ?? strtoupper($field);
        return "\${COL" . $number . "_" . $fieldCode . "}";
    }
    
    /**
     * Generate placeholder for bank field
     * 
     * @param string $field Field name from bank_fields
     * @return string Placeholder like ${BNK_NM_NP}
     */
    public static function getBankPlaceholder($field) {
        $fieldCode = self::$FIELD_MAPPING['bank_fields'][$field] ?? strtoupper($field);
        return "\${" . $fieldCode . "}";
    }
    
    /**
     * Get all placeholders for a person (for documentation)
     * 
     * @param string $personType
     * @param int $number
     * @return array
     */
    public static function getAllPersonPlaceholders($personType, $number) {
        $placeholders = [];
        foreach (self::$FIELD_MAPPING['person_fields'] as $field => $code) {
            $placeholders[$field] = self::getPersonPlaceholder($personType, $number, $field);
        }
        return $placeholders;
    }
}

// Example Usage:
/*
echo PlaceholderLibrary::getPersonPlaceholder('Borrower', 1, 'full_name');
// Output: ${BR1_NM_NP}

echo PlaceholderLibrary::getLoanPlaceholder('loan_amount');
// Output: ${LN_AMT}

echo PlaceholderLibrary::getCollateralPlaceholder(1, 'land_kitta_no');
// Output: ${COL1_KITTA_NO}
*/
