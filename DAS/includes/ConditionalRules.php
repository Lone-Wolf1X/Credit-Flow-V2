<?php
/**
 * Conditional Rules Engine
 * Handles gender-specific terms, translations, and contextual replacements
 */

class ConditionalRules {
    
    /**
     * Gender-specific terms in Nepali
     * Used when document text changes based on borrower/guarantor gender
     */
    public static $GENDER_TERMS = [
        'Male' => [
            'pronoun_subject' => 'उनी',          // He
            'pronoun_possessive' => 'उनको',      // His
            'son_daughter' => 'छोरा',            // Son
            'himself_herself' => 'आफैं',         // Himself
            'mr_mrs' => 'श्री',                  // Mr.
            'father_mother' => 'बुबा',           // Father
        ],
        'Female' => [
            'pronoun_subject' => 'उनी',          // She
            'pronoun_possessive' => 'उनको',      // Her
            'son_daughter' => 'छोरी',            // Daughter
            'himself_herself' => 'आफैं',         // Herself
            'mr_mrs' => 'श्रीमती',               // Mrs.
            'father_mother' => 'आमा',            // Mother
        ],
        'Other' => [
            'pronoun_subject' => 'उनी',
            'pronoun_possessive' => 'उनको',
            'son_daughter' => 'सन्तान',          // Child
            'himself_herself' => 'आफैं',
            'mr_mrs' => 'श्री',
            'father_mother' => 'अभिभावक',        // Guardian
        ],
    ];
    
    /**
     * Marital status specific terms
     */
    public static $MARITAL_TERMS = [
        'Single' => [
            'spouse_clause' => '',               // Empty for single
            'marital_status_np' => 'अविवाहित',
        ],
        'Married' => [
            'spouse_clause' => 'पति/पत्नी ${SPOUSE} सहित', // With spouse
            'marital_status_np' => 'विवाहित',
        ],
        'Divorced' => [
            'spouse_clause' => '',
            'marital_status_np' => 'सम्बन्ध विच्छेद',
        ],
        'Widowed' => [
            'spouse_clause' => '',
            'marital_status_np' => 'विधवा/विधुर',
        ],
    ];
    
    /**
     * Translation mappings (English → Nepali)
     */
    public static $TRANSLATIONS = [
        // Gender
        'gender' => [
            'Male' => 'पुरुष',
            'Female' => 'महिला',
            'Other' => 'अन्य',
        ],
        
        // Marital Status
        'marital_status' => [
            'Single' => 'अविवाहित',
            'Married' => 'विवाहित',
            'Divorced' => 'सम्बन्ध विच्छेद',
            'Widowed' => 'विधवा/विधुर',
        ],
        
        // Loan Types
        'loan_type' => [
            'Business Loan' => 'व्यापार ऋण',
            'Agriculture Loan' => 'कृषि ऋण',
            'Home Loan' => 'गृह ऋण',
            'Personal Loan' => 'व्यक्तिगत ऋण',
            'Vehicle Loan' => 'सवारी साधन ऋण',
            'Education Loan' => 'शिक्षा ऋण',
        ],
        
        // Collateral Type
        'collateral_type' => [
            'Land' => 'जग्गा',
            'Vehicle' => 'सवारी साधन',
            'Building' => 'भवन',
            'Gold' => 'सुन',
        ],
        
        // Person Type
        'person_type' => [
            'Borrower' => 'ऋणी',
            'Guarantor' => 'जमानतवाला',
            'CollateralOwner' => 'धितो धनी',
        ],
        
        // Reissue Count
        'reissue_count' => [
            0 => 'पहिलो पटक',
            1 => 'पहिलो पुनः जारी',
            2 => 'दोस्रो पुनः जारी',
            3 => 'तेस्रो पुनः जारी',
        ],
    ];
    
    /**
     * Get gender-specific term
     * 
     * @param string $gender 'Male', 'Female', or 'Other'
     * @param string $termType 'pronoun_subject', 'pronoun_possessive', etc.
     * @return string Nepali term
     */
    public static function getGenderTerm($gender, $termType) {
        return self::$GENDER_TERMS[$gender][$termType] ?? self::$GENDER_TERMS['Male'][$termType];
    }
    
    /**
     * Get marital status specific term
     * 
     * @param string $maritalStatus 'Single', 'Married', etc.
     * @param string $termType 'spouse_clause', 'marital_status_np'
     * @return string
     */
    public static function getMaritalTerm($maritalStatus, $termType) {
        return self::$MARITAL_TERMS[$maritalStatus][$termType] ?? '';
    }
    
    /**
     * Translate English value to Nepali
     * 
     * @param string $category 'gender', 'marital_status', 'loan_type', etc.
     * @param string $value English value
     * @return string Nepali translation
     */
    public static function translate($category, $value) {
        return self::$TRANSLATIONS[$category][$value] ?? $value;
    }
    
    /**
     * Generate conditional placeholder based on gender
     * Used in templates where text changes based on gender
     * 
     * Example: "उनको छोरा/छोरी" becomes "उनको छोरा" for male
     * 
     * @param string $personType 'Borrower', 'Guarantor', 'CollateralOwner'
     * @param int $number Person number
     * @param string $termType Term type from GENDER_TERMS
     * @return string Placeholder like ${BR1_GENDER_SON_DAUGHTER}
     */
    public static function getGenderConditionalPlaceholder($personType, $number, $termType) {
        $typeCode = PlaceholderLibrary::$PERSON_TYPES[$personType] ?? 'BR';
        $termCode = strtoupper($termType);
        return "\${" . $typeCode . $number . "_GENDER_" . $termCode . "}";
    }
}

/**
 * Number to Nepali Words Converter
 * Converts numbers to Nepali words for amounts
 */
class NepaliNumberToWords {
    
    private static $ones = [
        0 => '', 1 => 'एक', 2 => 'दुई', 3 => 'तीन', 4 => 'चार',
        5 => 'पाँच', 6 => 'छ', 7 => 'सात', 8 => 'आठ', 9 => 'नौ'
    ];
    
    private static $tens = [
        10 => 'दश', 11 => 'एघार', 12 => 'बाह्र', 13 => 'तेह्र', 14 => 'चौध',
        15 => 'पन्ध्र', 16 => 'सोह्र', 17 => 'सत्र', 18 => 'अठार', 19 => 'उन्नाइस'
    ];
    
    private static $twenties = [
        20 => 'बीस', 30 => 'तीस', 40 => 'चालीस', 50 => 'पचास',
        60 => 'साठी', 70 => 'सत्तरी', 80 => 'अस्सी', 90 => 'नब्बे'
    ];
    
    /**
     * Convert number to Nepali words
     * 
     * @param float $number
     * @return string Nepali words + "रुपैयाँ मात्र"
     */
    public static function convert($number) {
        if ($number == 0) {
            return 'शून्य रुपैयाँ मात्र';
        }
        
        $crore = floor($number / 10000000);
        $number = $number % 10000000;
        
        $lakh = floor($number / 100000);
        $number = $number % 100000;
        
        $thousand = floor($number / 1000);
        $number = $number % 1000;
        
        $hundred = floor($number / 100);
        $number = $number % 100;
        
        $words = '';
        
        if ($crore > 0) {
            $words .= self::convertTwoDigit($crore) . ' करोड ';
        }
        
        if ($lakh > 0) {
            $words .= self::convertTwoDigit($lakh) . ' लाख ';
        }
        
        if ($thousand > 0) {
            $words .= self::convertTwoDigit($thousand) . ' हजार ';
        }
        
        if ($hundred > 0) {
            $words .= self::$ones[$hundred] . ' सय ';
        }
        
        if ($number > 0) {
            $words .= self::convertTwoDigit($number);
        }
        
        return trim($words) . ' रुपैयाँ मात्र';
    }
    
    private static function convertTwoDigit($number) {
        if ($number < 10) {
            return self::$ones[$number];
        } elseif ($number < 20) {
            return self::$tens[$number];
        } else {
            $ten = floor($number / 10) * 10;
            $one = $number % 10;
            return self::$twenties[$ten] . ($one > 0 ? ' ' . self::$ones[$one] : '');
        }
    }
}

/**
 * Font Size Preserver
 * Ensures placeholder replacements maintain document font size
 */
class FontSizePreserver {
    
    /**
     * Wrap text with font size preservation
     * PHPWord maintains formatting from template, but this ensures consistency
     * 
     * @param string $text Text to wrap
     * @param int $fontSize Font size (default 12)
     * @return string Text (PHPWord handles formatting from template)
     */
    public static function preserveSize($text, $fontSize = 12) {
        // PHPWord automatically preserves font from template
        // This is here for future HTML-based generation if needed
        return $text;
    }
    
    /**
     * Recommended font sizes for different document elements
     */
    public static $RECOMMENDED_SIZES = [
        'title' => 16,
        'heading' => 14,
        'body' => 12,
        'table_header' => 11,
        'table_data' => 10,
        'footer' => 9,
    ];
}

// Example Usage:
/*
// Gender-specific term
echo ConditionalRules::getGenderTerm('Male', 'son_daughter');
// Output: छोरा

// Translation
echo ConditionalRules::translate('gender', 'Male');
// Output: पुरुष

// Number to words
echo NepaliNumberToWords::convert(500000);
// Output: पाँच लाख रुपैयाँ मात्र

// Gender conditional placeholder
echo ConditionalRules::getGenderConditionalPlaceholder('Borrower', 1, 'son_daughter');
// Output: ${BR1_GENDER_SON_DAUGHTER}
*/
