<?php
/**
 * Placeholder Extractor
 * Extracts all placeholders from DOCX templates
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;

class PlaceholderExtractor {
    
    /**
     * Extract all placeholders from a DOCX template
     * 
     * @param string $templatePath Path to DOCX file
     * @return array Array of unique placeholders found
     */
    public static function extract($templatePath) {
        if (!file_exists($templatePath)) {
            return ['error' => 'Template file not found: ' . $templatePath];
        }
        
        $placeholders = [];
        
        try {
            // Read the template as XML
            $zip = new \ZipArchive();
            if ($zip->open($templatePath) === true) {
                // Read document.xml which contains the main content
                $xml = $zip->getFromName('word/document.xml');
                
                if ($xml !== false) {
                    // Find all ${VARIABLE} patterns
                    preg_match_all('/\$\{([A-Z0-9_]+)\}/', $xml, $matches);
                    
                    if (!empty($matches[1])) {
                        $placeholders = array_unique($matches[1]);
                        sort($placeholders);
                    }
                }
                
                $zip->close();
            }
            
        } catch (Exception $e) {
            return ['error' => 'Error extracting placeholders: ' . $e->getMessage()];
        }
        
        return $placeholders;
    }
    
    /**
     * Extract placeholders from all templates in a directory
     * 
     * @param string $directory Path to templates directory
     * @return array Associative array [template_name => [placeholders]]
     */
    public static function extractFromDirectory($directory) {
        $results = [];
        
        if (!is_dir($directory)) {
            return ['error' => 'Directory not found: ' . $directory];
        }
        
        $files = glob($directory . '/*.docx');
        
        foreach ($files as $file) {
            $templateName = basename($file);
            $results[$templateName] = self::extract($file);
        }
        
        return $results;
    }
    
    /**
     * Categorize placeholders by type
     * 
     * @param array $placeholders Array of placeholder names
     * @return array Categorized placeholders
     */
    public static function categorize($placeholders) {
        $categories = [
            'borrowers' => [],
            'co_borrowers' => [],
            'guarantors' => [],
            'collateral' => [],
            'loan' => [],
            'system' => [],
            'other' => []
        ];
        
        foreach ($placeholders as $placeholder) {
            if (preg_match('/^BR\d+_/', $placeholder)) {
                $categories['borrowers'][] = $placeholder;
            } elseif (preg_match('/^CO\d+_/', $placeholder)) {
                $categories['co_borrowers'][] = $placeholder;
            } elseif (preg_match('/^GT\d+_/', $placeholder)) {
                $categories['guarantors'][] = $placeholder;
            } elseif (preg_match('/^COL\d+_/', $placeholder)) {
                $categories['collateral'][] = $placeholder;
            } elseif (preg_match('/^LN_/', $placeholder)) {
                $categories['loan'][] = $placeholder;
            } elseif (preg_match('/^(SYSTEM_|BANK_|CUSTOMER_|GENERATED_|APPROVED_)/', $placeholder)) {
                $categories['system'][] = $placeholder;
            } else {
                $categories['other'][] = $placeholder;
            }
        }
        
        return $categories;
    }
    
    /**
     * Get borrower count required by template
     * 
     * @param array $placeholders Array of placeholder names
     * @return int Number of borrowers needed
     */
    public static function getBorrowerCount($placeholders) {
        $maxBorrower = 0;
        
        foreach ($placeholders as $placeholder) {
            // Check BR1, BR2, etc.
            if (preg_match('/^BR(\d+)_/', $placeholder, $matches)) {
                $num = intval($matches[1]);
                if ($num > $maxBorrower) $maxBorrower = $num;
            }
            // Check CO1, CO2, etc.
            if (preg_match('/^CO(\d+)_/', $placeholder, $matches)) {
                $num = intval($matches[1]);
                // CO1 is the first co-borrower, so total borrowers = CO number + 1
                $total = $num + 1;
                if ($total > $maxBorrower) $maxBorrower = $total;
            }
        }
        
        return $maxBorrower;
    }
}
