<?php
/**
 * Template Placeholder Converter
 * Converts shorthand codes to full placeholders in Word templates
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

require_once __DIR__ . '/PlaceholderLibrary.php';

class TemplatePlaceholderConverter {
    
    /**
     * Shorthand to Placeholder mapping
     */
    private static $mappings = [
        'br1' => [
            '${BR1_NM_NP}', '${BR1_NM_EN}', '${BR1_DOB}', '${BR1_AGE}', '${BR1_CIT_NO}',
            '${BR1_FATHER}', '${BR1_MOTHER}', '${BR1_GF}', '${BR1_SPOUSE}',
            '${BR1_P_DIST}', '${BR1_P_MUN}', '${BR1_P_WARD}'
        ],
        'ln' => ['${LN_AMT}', '${LN_AMT_W}', '${LN_SCHEME}', '${LN_PURPOSE}', '${LN_LSL_DT}'],
        'col1' => ['${COL1_PROV}', '${COL1_DIST}', '${COL1_MUN}', '${COL1_WARD}', '${COL1_KITTA_NO}', '${COL1_AREA}']
    ];
    
    /**
     * Individual field shortcuts
     */
    private static $fieldMappings = [
        'br1_name' => '${BR1_NM_NP}',
        'br1_name_en' => '${BR1_NM_EN}',
        'br1_dob' => '${BR1_DOB}',
        'br1_age' => '${BR1_AGE}',
        'br1_cit' => '${BR1_CIT_NO}',
        'br1_father' => '${BR1_FATHER}',
        'br1_address' => '${BR1_P_DIST}, ${BR1_P_MUN}-${BR1_P_WARD}',
        
        'co1_name' => '${CO1_NM_NP}',
        'co1_cit' => '${CO1_CIT_NO}',
        
        'col_kitta' => '${COL_KITTA_NO}',
        'col_area' => '${COL_AREA}',
        'col_address' => '${COL_PROV}, ${COL_DIST}, ${COL_MUN}-${COL_WARD}',
        
        'ln_amt' => '${LN_AMT}',
        'ln_amt_words' => '${LN_AMT_W}',
        'ln_scheme' => '${LN_SCHEME}',
    ];
    
    /**
     * Convert template file
     */
    public static function convert($inputPath, $outputPath = null) {
        try {
            if (!file_exists($inputPath)) {
                throw new Exception("Input file not found: $inputPath");
            }
            
            if (!$outputPath) {
                $pathInfo = pathinfo($inputPath);
                $outputPath = $pathInfo['dirname'] . '/' . 
                             $pathInfo['filename'] . '_converted.docx';
            }
            
            // Load document
            $phpWord = IOFactory::load($inputPath);
            $replacements = 0;
            
            // Process all sections
            foreach ($phpWord->getSections() as $section) {
                $replacements += self::processContainer($section);
            }
            
            // Save converted document
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($outputPath);
            
            return [
                'success' => true,
                'input' => $inputPath,
                'output' => $outputPath,
                'replacements' => $replacements,
                'message' => "Template converted successfully with $replacements replacements"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process a container (Section, TextRun, etc.)
     */
    private static function processContainer($container) {
        $replacements = 0;
        if (method_exists($container, 'getElements')) {
            foreach ($container->getElements() as $element) {
                $replacements += self::processElement($element);
            }
        }
        return $replacements;
    }

    /**
     * Process a single element
     */
    private static function processElement($element) {
        $replacements = 0;
        
        // Handle Text elements locally
        if (method_exists($element, 'getText') && method_exists($element, 'setText')) {
            $text = $element->getText();
            $newText = self::replaceShorthands($text);
            if ($text !== $newText) {
                $element->setText($newText);
                $replacements++;
            }
        }

        // Handle containers (TextRun, Table, Row, Cell)
        if (method_exists($element, 'getElements')) {
            $replacements += self::processContainer($element);
        } elseif (method_exists($element, 'getRows')) { // Table
            foreach ($element->getRows() as $row) {
                $replacements += self::processElement($row);
            }
        } elseif (method_exists($element, 'getCells')) { // Row
            foreach ($element->getCells() as $cell) {
                $replacements += self::processElement($cell);
            }
        }
        
        return $replacements;
    }
    
    /**
     * Convert shorthands to full placeholders dynamically
     * Supports: br1_name, br2_cit, gr1_name, co2_address, etc.
     */
    private static function replaceShorthands($text) {
        // 1. Handle Person Shorthands (br1_name -> ${BR1_NM_NP})
        // Pattern: (br|gr|co|wit)(\d+)_([a-z0-9_]+)
        $text = preg_replace_callback(
            '/\b(br|gr|co|wit)(\d+)_([a-z0-9_]+)\b/', 
            function($matches) {
                $type = $matches[1]; // br, gr, co, wit
                $num = $matches[2];  // 1, 2
                $field = $matches[3]; // name, cit, address
                
                return self::resolvePersonPlaceholder($type, $num, $field);
            },
            $text
        );

        // 2. Handle Loan Shorthands (ln_amt -> ${LN_AMT})
        $text = preg_replace_callback(
            '/\b(ln)_([a-z0-9_]+)\b/', 
            function($matches) {
                $field = $matches[2];
                return self::resolveLoanPlaceholder($field);
            },
            $text
        );

        // 3. Handle Collateral Shorthands (col_kitta -> ${COL_KITTA_NO})
        $text = preg_replace_callback(
            '/\b(col|col1)_([a-z0-9_]+)\b/', // accept col_ or col1_
            function($matches) {
                $field = $matches[2];
                return self::resolveCollateralPlaceholder(1, $field); // Default to COL1 for 'col_'
            },
            $text
        );

        // 4. Handle Bank Shorthands (bnk_name -> ${BNK_NM_NP})
        $text = preg_replace_callback(
            '/\b(bnk)_([a-z0-9_]+)\b/', 
            function($matches) {
                $field = $matches[2];
                return self::resolveBankPlaceholder($field);
            },
            $text
        );

        return $text;
    }

    // Helper Resolvers using PlaceholderLibrary definitions
    
    private static function resolvePersonPlaceholder($typeCode, $num, $shorthandField) {
        // Map shorthand field to Library field key
        $fieldMap = [
            // Basic Identity
            'name' => 'full_name',
            'name_en' => 'full_name_en',
            'dob' => 'date_of_birth',
            'age' => 'age',
            'gender' => 'gender',
            'relationship_status' => 'relationship_status',
            
            // Citizenship
            'cit' => 'citizenship_number',
            'cit_no' => 'citizenship_number',
            'issue_date' => 'id_issue_date',
            'id_issue_date' => 'id_issue_date',
            'issue_district' => 'id_issue_district',
            'reissue_date' => 'id_reissue_date',
            'reissue_times' => 'reissue_times',
            
            // Address shortcuts
            'address' => 'ADDRESS_COMPOSITE',
            'perm_country' => 'perm_country',
            'temp_country' => 'temp_country',
            'perm_street_number' => 'perm_street_number',
            'temp_street_number' => 'temp_street_number',
            'district' => 'perm_district',
            
            // Family relations
            'father' => 'father_name',
            'mother' => 'mother_name',
            'gf' => 'grandfather_name',
            'gm' => 'grandmother_name',
            'spouse' => 'spouse_name',
            'son' => 'son_name',
            'daughter' => 'daughter_name',
            'fil' => 'father_in_law',
            'mil' => 'mother_in_law',
            
            // Contact
            'contact' => 'contact_number',
            'email' => 'email',
        ];

        $libField = $fieldMap[$shorthandField] ?? $shorthandField;
        
        // Determine Person Type for Library
        $typeMap = ['br' => 'Borrower', 'gr' => 'Guarantor', 'co' => 'CollateralOwner', 'wit' => 'Witness'];
        $personType = $typeMap[$typeCode] ?? 'Borrower';

        // Handle Composite Address Shortcut
        if ($libField === 'ADDRESS_COMPOSITE') {
            $pTypeUpper = strtoupper($typeCode);
            return "\${{$pTypeUpper}{$num}_P_DIST}, \${{$pTypeUpper}{$num}_P_MUN}-\${{$pTypeUpper}{$num}_P_WARD}";
        }

        // Use Library to get code
        $code = self::getLibraryCode('person_fields', $libField);
        if ($code) {
            $prefix = strtoupper($typeCode);
            if ($typeCode == 'wit' && $code == 'NM_NP') $code = 'NM';
            return "\${{$prefix}{$num}_{$code}}";
        }

        // Fallback
        return "{$typeCode}{$num}_{$shorthandField}";
    }

    private static function resolveLoanPlaceholder($shorthandField) {
        $fieldMap = [
            'amt' => 'loan_amount',
            'amount' => 'loan_amount',
            'amt_w' => 'loan_amount_words',
            'scheme' => 'loan_scheme',
            'purpose' => 'loan_purpose',
            'lsl' => 'loan_approved_date',
        ];
        $libField = $fieldMap[$shorthandField] ?? $shorthandField;
        $code = self::getLibraryCode('loan_fields', $libField);
        return $code ? "\${" . $code . "}" : "ln_{$shorthandField}";
    }

    private static function resolveCollateralPlaceholder($num, $shorthandField) {
        $fieldMap = [
            'kitta' => 'land_kitta_no',
            'area' => 'land_area',
            'sheet' => 'land_sheet_no',
            'malpot' => 'land_malpot_office',
            'ward' => 'land_ward_no',
            'mun' => 'land_municipality_vdc',
            'dist' => 'land_district',
            'prov' => 'land_province',
        ];
        $libField = $fieldMap[$shorthandField] ?? $shorthandField;
        
        // Special case for COL1 (Library expects COL{N}_...)
        $code = self::getLibraryCode('collateral_fields', $libField);
        
        // Library codes for collateral are like 'COL_KITTA_NO' (without number? Wait, Library says COL_PROV)
        // Library structure: 'land_province' => 'COL_PROV'
        // getCollateralPlaceholder adds the number: COL1_PROV
        
        if ($code) {
            // Strip existing COL_ prefix if present to avoid double COL1_COL_
            $suffix = str_replace('COL_', '', $code); 
            return "\${COL{$num}_{$suffix}}";
        }
        return "col{$num}_{$shorthandField}";
    }

    private static function resolveBankPlaceholder($shorthandField) {
         $fieldMap = [
            'name' => 'bank_name_np',
            'addr' => 'branch_address',
            'branch' => 'branch_name',
        ];
        $libField = $fieldMap[$shorthandField] ?? $shorthandField;
        $code = self::getLibraryCode('bank_fields', $libField);
        return $code ? "\${" . $code . "}" : "bnk_{$shorthandField}";
    }

    private static function getLibraryCode($category, $field) {
        if (class_exists('PlaceholderLibrary')) {
            return PlaceholderLibrary::$FIELD_MAPPING[$category][$field] ?? null;
        }
        return null;
    }
    
    /**
     * Get available shortcuts
     */
    public static function getAvailableShortcuts() {
        return [
            'groups' => array_keys(self::$mappings),
            'fields' => array_keys(self::$fieldMappings)
        ];
    }
    
    /**
     * Show shortcut reference
     */
    public static function showReference() {
        echo "=== Placeholder Shortcut Reference ===\n\n";
        echo "Individual Field Shortcuts:\n";
        echo str_repeat("-", 60) . "\n";
        foreach (self::$fieldMappings as $shorthand => $placeholder) {
            printf("  %-20s → %s\n", $shorthand, $placeholder);
        }
    }
}
