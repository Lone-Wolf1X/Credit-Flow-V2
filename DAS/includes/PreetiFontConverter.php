<?php
/**
 * Preeti to Unicode Font Converter
 * Converts Preeti font text to Unicode Devanagari
 */

class PreetiFontConverter {
    
    private static $preetToUnicodeMap = [
        // Vowels
        'a' => 'ब', 'b' => 'व', 'c' => 'च', 'd' => 'द', 'e' => 'े', 'f' => 'ा',
        'g' => 'ग', 'h' => 'ह', 'i' => 'ि', 'j' => 'ज', 'k' => 'क', 'l' => 'ल',
        'm' => 'म', 'n' => 'न', 'o' => 'ो', 'p' => 'प', 'q' => 'त्र', 'r' => 'र',
        's' => 'स', 't' => 'त', 'u' => 'ु', 'v' => 'ट', 'w' => 'ध', 'x' => 'ड',
        'y' => 'य', 'z' => 'श',
        
        // Capitals
        'A' => 'आ', 'B' => 'भ', 'C' => 'छ', 'D' => 'ध', 'E' => 'ै', 'F' => 'ँ',
        'G' => 'घ', 'H' => 'अ', 'I' => 'ी', 'J' => 'झ', 'K' => 'ख', 'L' => 'ळ',
        'M' => 'ः', 'N' => 'ण', 'O' => 'ौ', 'P' => 'फ', 'Q' => 'थ', 'R' => 'ऋ',
        'S' => 'ष', 'T' => 'ठ', 'U' => 'ू', 'V' => 'ठ', 'W' => 'ढ', 'X' => 'ढ',
        'Y' => 'य', 'Z' => 'ञ',
        
        // Numbers
        '0' => '०', '1' => '१', '2' => '२', '3' => '३', '4' => '४',
        '5' => '५', '6' => '६', '7' => '७', '8' => '८', '9' => '९',
        
        // Special characters
        '/' => 'र', '\\' => 'ृ', '|' => 'ं', '~' => 'ॅ', '`' => 'ञ',
        '!' => 'ज्ञ', '@' => 'द्द', '#' => 'रु', '$' => 'र्', '%' => 'ज्ञ',
        '^' => 'त्र', '&' => 'द्व', '*' => 'द्य', '(' => '(', ')' => ')',
        '-' => '-', '_' => 'ः', '=' => 'ृ', '+' => 'ं', '[' => 'ृ',
        ']' => '्', '{' => 'ै', '}' => 'ौ', ':' => 'स्', ';' => 'क्',
        '"' => 'ू', '\'' => 'ु', '<' => ',', '>' => '।', ',' => ',',
        '.' => '.', '?' => 'रू', ' ' => ' ',
        
        // Combined characters
        'ा' => 'ा', 'ि' => 'ि', 'ी' => 'ी', 'ु' => 'ु', 'ू' => 'ू',
        'े' => 'े', 'ै' => 'ै', 'ो' => 'ो', 'ौ' => 'ौ', 'ं' => 'ं',
        'ः' => 'ः', '्' => '्', 'ँ' => 'ँ',
    ];
    
    /**
     * Convert Preeti text to Unicode
     * 
     * @param string $preetText Text in Preeti font
     * @return string Text in Unicode Devanagari
     */
    public static function convert($preetText) {
        if (empty($preetText)) {
            return '';
        }
        
        // If text is already in Unicode (contains Devanagari), return as-is
        if (self::isUnicode($preetText)) {
            return $preetText;
        }
        
        $unicode = '';
        $length = mb_strlen($preetText, 'UTF-8');
        
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($preetText, $i, 1, 'UTF-8');
            
            // Check for two-character combinations first
            if ($i < $length - 1) {
                $twoChar = mb_substr($preetText, $i, 2, 'UTF-8');
                if (isset(self::$preetToUnicodeMap[$twoChar])) {
                    $unicode .= self::$preetToUnicodeMap[$twoChar];
                    $i++; // Skip next character
                    continue;
                }
            }
            
            // Single character conversion
            if (isset(self::$preetToUnicodeMap[$char])) {
                $unicode .= self::$preetToUnicodeMap[$char];
            } else {
                $unicode .= $char; // Keep as-is if not in map
            }
        }
        
        return $unicode;
    }
    
    /**
     * Check if text is already in Unicode Devanagari
     * 
     * @param string $text
     * @return bool
     */
    private static function isUnicode($text) {
        // Check if text contains Devanagari Unicode characters (U+0900 to U+097F)
        return preg_match('/[\x{0900}-\x{097F}]/u', $text) === 1;
    }
    
    /**
     * Convert English numbers to Nepali
     * 
     * @param string $text
     * @return string
     */
    public static function convertNumbers($text) {
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $nepali = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
        return str_replace($english, $nepali, $text);
    }
}
