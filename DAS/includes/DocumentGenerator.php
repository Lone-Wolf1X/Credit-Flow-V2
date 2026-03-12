<?php
/**
 * Document Generator
 * Generates Word documents using PHPWord and templates
 */

if (!class_exists('Composer\Autoload\ClassLoader')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
require_once __DIR__ . '/DocumentDataFetcher.php';
require_once __DIR__ . '/PlaceholderMapper.php';
require_once __DIR__ . '/DocumentRuleEngine.php';

use PhpOffice\PhpWord\TemplateProcessor;

class DocumentGenerator {
    private $conn;
    private $generated_dir;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->generated_dir = __DIR__ . '/../generated/';
        
        // Create generated directory if not exists
        if (!file_exists($this->generated_dir)) {
            mkdir($this->generated_dir, 0777, true);
        }
    }
    
    /**
     * Generate mortgage deed for a customer profile
     * 
     * @param int $customer_profile_id Customer profile ID
     * @param int $collateral_index Which collateral to use (default 0 = first)
     * @return array Result with success status and file path
     */
    public function generateMortgageDeed($customer_profile_id, $collateral_index = 0) {
        try {
            // 1. Fetch all data
            $fetcher = new DocumentDataFetcher($this->conn, $customer_profile_id);
            $data = $fetcher->fetchAllData();
            
            // Validate data
            if (empty($data['collateral'])) {
                throw new Exception('No collateral found for this profile');
            }
            
            if (!isset($data['collateral'][$collateral_index])) {
                throw new Exception('Collateral index not found');
            }
            
            // 2. Get template path from loan scheme
            $template_path = $this->getTemplatePath($data['loan']);
            
            if (!file_exists($template_path)) {
                throw new Exception('Template file not found: ' . $template_path);
            }
            
            // 3. Map data to placeholders
            $mapper = new PlaceholderMapper($data);
            $placeholders = $mapper->mapForMortgageDeed($collateral_index);
            
            // 4. Load template
            $template = new TemplateProcessor($template_path);
            
            // 5. Get all template variables
            $templateVars = $template->getVariables();
            
            // 6. Replace all mapped placeholders (Iterate data first to ensure all instances are replaced)
            foreach ($placeholders as $key => $value) {
                // Ensure value is never null or empty (use space instead)
                $safeValue = ($value === null || $value === '') ? ' ' : $value;
                // setValue replaces ALL occurrences of the variable in the document
                $template->setValue($key, $safeValue);
            }

            // 7. Cleanup remaining variables (set unmapped to space)
            // Re-fetch variables in case some were not in our mapping or new ones appeared (unlikely but safe)
            $remainingVars = $template->getVariables();
            foreach ($remainingVars as $var) {
                // If it's still here, it wasn't replaced by the mapping above
                $template->setValue($var, ' ');
            }
            
            // 7. Generate filename
            $filename = $this->generateFilename($customer_profile_id, 'Mortgage_Deed');
            $output_path = $this->generated_dir . $filename;
            
            // 7. Save document
            $template->saveAs($output_path);
            
            // 8. Save to database (commented out for testing)
            // $this->saveToDatabase($customer_profile_id, $filename, $output_path);
            
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $output_path,
                'message' => 'Mortgage deed generated successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate document using Rule Engine (new method)
     */
    public function generateDocument($templatePath, $customerProfileId, $documentType = 'mortgage_deed', $injectedData = null) {
        try {
            // Use Rule Engine to fetch and map data
            $ruleEngine = new DocumentRuleEngine($documentType, $this->conn);
            
            if ($injectedData) {
                $data = $injectedData;
            } else {
                $data = $ruleEngine->fetchData($customerProfileId);
            }
            
            $placeholders = $ruleEngine->mapToPlaceholders($data);
            
            // Load template (Restored)
            $templateProcessor = new TemplateProcessor($templatePath);
            
            // PRE-PROCESS BLOCKS (Cloning Tables/Sections)
            // Prepare block data
            $blockData = [];
            if (isset($data['persons']['borrowers'])) {
                $blockData['borrower_block'] = $data['persons']['borrowers'];
            }
            if (isset($data['persons']['guarantors'])) {
                $blockData['guarantor_block'] = $data['persons']['guarantors'];
            }
            if (isset($data['persons']['collateral_owners'])) {
                $blockData['collateral_owner_block'] = $data['persons']['collateral_owners'];
            }
            // Add Collateral Items (Properties) Block
            if (isset($data['collateral'])) {
                $blockData['collateral_block'] = $data['collateral'];
            }
            // Execute Block Processing
            $this->processBlocks($templateProcessor, $blockData);
            
            // LOGGING START
            $logContent = "Generation Log - " . date('Y-m-d H:i:s') . "\n";
            $logContent .= "Template: " . basename($templatePath) . "\n";
            $logContent .= "----------------------------------------\n";
            
            // Get all variable counts in template
            $variableCounts = $templateProcessor->getVariableCount();
            $logContent .= "Found " . count($variableCounts) . " unique variables in template.\n\n";
            
            foreach ($variableCounts as $var => $count) {
                // The variable name in getVariables() doesn't have ${} wrapper usually
                // We typically map keys like BR1_NM_NP
                
                $value = $placeholders[$var] ?? null;
                
                if ($value !== null && $value !== '') {
                    $logContent .= "[MATCH] \${$var} = '$value' (Count: $count)\n";
                    
                    // Loop to replace ALL occurrences (since setComplexValue only does one at a time)
                    for ($i = 0; $i < $count; $i++) {
                        try {
                            $this->setValuePreservingFormat($templateProcessor, $var, $value);
                        } catch (Exception $e) {
                             $logContent .= "  ERROR setting value: " . $e->getMessage() . "\n";
                             break; // Stop loop if error
                        }
                    }
                    unset($placeholders[$var]); // Mark as used
                } else {
                    $logContent .= "[MISSING] \${$var} - No data found (replaced with space)\n";
                    // use standard setValue for space (replaces all 1 by default, but to be safe for consistency?)
                    // setValue replaces ALL if limit is -1. So one call is enough.
                    $templateProcessor->setValue($var, ' ');
                }
            }
            
            $logContent .= "\n----------------------------------------\n";
            file_put_contents(__DIR__ . '/../generated_documents/last_generation_log.txt', $logContent, FILE_APPEND);
            // LOGGING END
            
            
            // Get profile name for folder organization
            $profile_name = $this->getProfileName($customerProfileId);
            $customer_id = $this->getCustomerID($customerProfileId);
            
            // Create organized folder structure: CustomerID_ProfileName
            $folder_name = $customer_id . '_' . $this->sanitizeFilename($profile_name);
            $outputDir = __DIR__ . '/../generated_documents/' . $folder_name . '/';
            
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0777, true);
            }
            
            // Generate descriptive filename based on template name
            $template_basename = pathinfo($templatePath, PATHINFO_FILENAME);
            $timestamp = date('YmdHis');
            
            // Create descriptive filename: TemplateName_Timestamp.docx
            $outputFilename = $this->sanitizeFilename($template_basename) . '_' . $timestamp . '.docx';
            $outputPath = $outputDir . $outputFilename;
            
            $templateProcessor->saveAs($outputPath);
            
            // Return relative path for database storage
            $relativePath = 'generated_documents/' . $folder_name . '/' . $outputFilename;
            
            return [
                'success' => true,
                'output_path' => $outputPath,
                'relative_path' => $relativePath,
                'customer_profile_id' => $customerProfileId,
                'placeholders_count' => count($placeholders),
                'folder_name' => $folder_name
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }
    
    /**
     * Get profile name for folder naming
     */
    private function getProfileName($profileId) {
        $stmt = $this->conn->prepare("
            SELECT full_name FROM customer_profiles 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $profileId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['full_name'];
        }
        
        return 'Unknown_Profile';
    }
    
    /**
     * Get customer ID from profile
     */
    private function getCustomerID($profileId) {
        $stmt = $this->conn->prepare("
            SELECT customer_id FROM customer_profiles 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $profileId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['customer_id'];
        }
        
        return 'CUST' . $profileId;
    }

    /**
     * Resolve dynamic template path based on borrower count
     * Public helper for API usage
     */
    public function resolveDynamicPath($baseTemplatePath, $profileId) {
        // Only apply to mortgage_deed type templates
        if (strpos($baseTemplatePath, 'mortgage_deed') === false) {
            return $baseTemplatePath;
        }

        $count = $this->getBorrowerCount($profileId);
        
        // Ensure count is within expected range (1-5)
        if ($count < 1) $count = 1;
        if ($count > 5) $count = 5;
        
        $dir = dirname($baseTemplatePath);
        $filename = basename($baseTemplatePath);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        
        // If filename is already numbered (e.g. mortgage_deed_1), strip it?
        // Assuming base is mortgage_deed.docx
        
        // Construct new filename: mortgage_deed_{count}.docx
        // But what if base is "Previous_Mortgage.docx"?
        // User said "mortgage_deed_1", "mortgage_deed_2".
        // Let's assume we replace "mortgage_deed" with "mortgage_deed_{count}".
        
        // Simpler approach: Check if "mortgage_deed_{count}.docx" exists in the same folder
        $newFilename = "mortgage_deed_{$count}.{$extension}";
        $newPath = $dir . '/' . $newFilename;
        
        if (file_exists($newPath)) {
            return $newPath;
        }
        
        return $baseTemplatePath;
    }

    /**
     * Get borrower count for template selection
     */
    private function getBorrowerCount($profileId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM borrowers WHERE customer_profile_id = ?");
        $stmt->bind_param("i", $profileId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)($result['count'] ?? 1);
    }
    
    /**
     * Sanitize filename to remove invalid characters
     */
    private function sanitizeFilename($filename) {
        // Remove or replace invalid filename characters
        $filename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $filename);
        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);
        // Remove multiple consecutive underscores
        $filename = preg_replace('/_+/', '_', $filename);
        return trim($filename, '_');
    }
    
    /**
     * Set value while preserving template formatting
     * Uses regular setValue - template formatting is preserved by Word
     */
    /**
     * Set value while preserving template formatting
     * Uses setComplexValue with TextRun to maintain font size and style
     */
    private function setValuePreservingFormat($templateProcessor, $search, $replace) {
        // Use standard setValue to preserve template's own formatting (Font, Size, Bold, etc.)
        $templateProcessor->setValue($search, $replace);
    }
    
    /**
     * Get template path from loan scheme
     */
    private function getTemplatePath($loan) {
        // Get loan scheme
        $scheme_name = $loan['loan_scheme'] ?? '';
        
        // Query loan_schemes table to get template folder
        $stmt = $this->conn->prepare("
            SELECT template_folder_path FROM loan_schemes 
            WHERE scheme_name = ? OR scheme_code = ?
            LIMIT 1
        ");
        $stmt->bind_param("ss", $scheme_name, $scheme_name);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result) {
            $folder = $result['template_folder_path'];
            // Look for mortgage_deed.docx in that folder
            $template_path = __DIR__ . '/../' . $folder . '/mortgage_deed.docx';
            
            if (file_exists($template_path)) {
                return $template_path;
            }
        }
        
        // Fallback to default template logic with dynamic borrower count
        $count = $this->getBorrowerCount($loan['customer_profile_id']);
        
        // Ensure count is within expected range (1-5)
        if ($count < 1) $count = 1;
        if ($count > 5) $count = 5;
        
        $templateName = "mortgage_deed_{$count}.docx";
        $templatePath = __DIR__ . '/../templates/ptl/' . $templateName;
        
        if (file_exists($templatePath)) {
            return $templatePath;
        }
        
        // Fallback to base template if specific count template doesn't exist
        return __DIR__ . '/../templates/ptl/mortgage_deed.docx';
    }
    
    /**
     * Generate unique filename
     */
    private function generateFilename($customer_profile_id, $doc_type) {
        $date = date('Ymd_His');
        return "{$doc_type}_{$customer_profile_id}_{$date}.docx";
    }
    
    /**
     * Save generated document info to database
     */
    private function saveToDatabase($customer_profile_id, $filename, $file_path) {
        // Check if profile_documents table exists
        $result = $this->conn->query("SHOW TABLES LIKE 'profile_documents'");
        
        if ($result && $result->num_rows > 0) {
            $stmt = $this->conn->prepare("
                INSERT INTO profile_documents 
                (customer_profile_id, document_type, file_name, file_path, generated_at)
                VALUES (?, 'Mortgage Deed', ?, ?, NOW())
            ");
            $stmt->bind_param("iss", $customer_profile_id, $filename, $file_path);
            $stmt->execute();
        }
    }
    
    /**
     * Get list of generated documents for a profile
     */
    public function getGeneratedDocuments($customer_profile_id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM profile_documents 
            WHERE customer_profile_id = ?
            ORDER BY generated_at DESC
        ");
        $stmt->bind_param("i", $customer_profile_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // =================================================================
    // HTML INJECTION METHODS
    // =================================================================
    
    /**
     * Render and Inject HTML Components
     * Replaces ${COMPONENT_CODE} placeholders with rendered HTML
     */
    /**
     * Render and Inject HTML Components
     * Replaces ${COMPONENT_CODE} placeholders with rendered HTML
     */
    // =================================================================
    // NEW: MALPOT SPLITTING LOGIC
    // =================================================================

    /**
     * Generate Documents Grouped by Malpot Office
     * 
     * Splits generation into multiple files if collateral exists under different Malpot offices.
     * Returns an array of results (one for each generated file).
     */
    public function generateDocumentsGroupedByMalpot($templatePath, $profileId, $docType) {
        $results = [];
        
        try {
            // 1. Fetch ALL Data first
            $ruleEngine = new DocumentRuleEngine($docType, $this->conn);
            $fullData = $ruleEngine->fetchData($profileId);
            
            // 2. Identify Unique Malpot Offices
            $malpotGroups = [];
            
            if (isset($fullData['collateral']) && is_array($fullData['collateral'])) {
                foreach ($fullData['collateral'] as $item) {
                    // Use 'land_malpot_office' or default to 'Others'
                    $malpot = trim($item['land_malpot_office'] ?? 'Others');
                    if (empty($malpot)) $malpot = 'Others';
                    
                    if (!isset($malpotGroups[$malpot])) {
                        $malpotGroups[$malpot] = [];
                    }
                    $malpotGroups[$malpot][] = $item;
                }
            }
            
            // 3. If no collateral or generic, generate once using STANDARD method
            if (empty($malpotGroups)) {
                $res = $this->generateDocument($templatePath, $profileId, $docType);
                $res['malpot_office'] = 'General';
                return [$res];
            }
            
            // 4. Generate Document for EACH Malpot Group
            foreach ($malpotGroups as $officeName => $items) {
                // Create a Copy of Data
                $groupData = $fullData;
                
                // OVERWRITE collateral with FILTERED items
                $groupData['collateral'] = $items;
                
                // Filter Collateral Owners
                $relevantOwnerIds = [];
                foreach ($items as $ci) {
                    if (isset($ci['owner_id'])) $relevantOwnerIds[] = $ci['owner_id'];
                }
                
                if (isset($groupData['persons']['collateral_owners'])) {
                    $filteredOwners = [];
                    foreach ($groupData['persons']['collateral_owners'] as $co) {
                        if (in_array($co['id'], $relevantOwnerIds)) {
                             $filteredOwners[] = $co;
                        }
                    }
                    $groupData['persons']['collateral_owners'] = $filteredOwners;
                }
                
                // Generate with specific Data
                $res = $this->generateFromData($templatePath, $profileId, $docType, $groupData, $officeName);
                $res['malpot_office'] = $officeName;
                $results[] = $res;
            }
            
        } catch (Exception $e) {
             return [['success' => false, 'error' => $e->getMessage()]];
        }
        
        return $results;
    }



    /**
     * Generate Document from Pre-fetched Data (Internal Helper)
     */
    private function generateFromData($templatePath, $customerProfileId, $documentType, $data, $suffixRaw = '') {
         try {
             // Rule Engine: Map placeholders
             $ruleEngine = new DocumentRuleEngine($documentType, $this->conn);
             $placeholders = $ruleEngine->mapToPlaceholders($data);
             
             // Load template
             $templateProcessor = new TemplateProcessor($templatePath);
             
             // PRE-PROCESS BLOCKS (with filtered data)
             $blockData = [];
             if (isset($data['persons']['borrowers'])) $blockData['borrower_block'] = $data['persons']['borrowers'];
             if (isset($data['persons']['guarantors'])) $blockData['guarantor_block'] = $data['persons']['guarantors'];
             if (isset($data['persons']['collateral_owners'])) $blockData['collateral_owner_block'] = $data['persons']['collateral_owners'];
             if (isset($data['collateral'])) $blockData['collateral_block'] = $data['collateral'];
             
             $this->processBlocks($templateProcessor, $blockData);
             
             // Variable Replacement
             $variableCounts = $templateProcessor->getVariableCount();
             foreach ($variableCounts as $var => $count) {
                 $value = $placeholders[$var] ?? null;
                 if ($value !== null && $value !== '') {
                     for ($i = 0; $i < $count; $i++) {
                         try { $this->setValuePreservingFormat($templateProcessor, $var, $value); } catch(Exception $e) {}
                     }
                 } else {
                     $templateProcessor->setValue($var, ' ');
                 }
             }

             // Folder & Filename
             $profile_name = $this->getProfileName($customerProfileId);
             $customer_id = $this->getCustomerID($customerProfileId);
             $folder_name = $customer_id . '_' . $this->sanitizeFilename($profile_name);
             $outputDir = __DIR__ . '/../generated_documents/' . $folder_name . '/';
             if (!file_exists($outputDir)) mkdir($outputDir, 0777, true);
             
             $template_basename = pathinfo($templatePath, PATHINFO_FILENAME);
             $timestamp = date('YmdHis');
             
             // SUFFIX HANDLING
             $suffixClean = $this->sanitizeFilename($suffixRaw);
             if (!empty($suffixClean)) $suffixClean = '_' . $suffixClean;
             
             $outputFilename = $this->sanitizeFilename($template_basename) . $suffixClean . '_' . $timestamp . '.docx';
             $outputPath = $outputDir . $outputFilename;
             
             $templateProcessor->saveAs($outputPath);
             $relativePath = 'generated_documents/' . $folder_name . '/' . $outputFilename;
             
             return [
                 'success' => true,
                 'output_path' => $outputPath,
                 'relative_path' => $relativePath,
                 'customer_profile_id' => $customerProfileId,
                 'placeholders_count' => count($placeholders),
                 'folder_name' => $folder_name
             ];
             
         } catch (Exception $e) {
             return ['success' => false, 'error' => $e->getMessage()];
         }
    }

    public function renderHtmlComponents($templateProcessor, $data) {
        // 1. Fetch all components from DB
        $result = $this->conn->query("SELECT * FROM document_components");
        $components = $result->fetch_all(MYSQLI_ASSOC);
        
        foreach ($components as $comp) {
            $code = $comp['code'];
            $html = $comp['html_content'];
            
            // 2. Resolve internal variables in HTML
            // E.g. replace {{owner_name}} with actual data
            // We use mustache-like syntax {{var}}
            
            // Simple string replacement
            foreach ($data as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $html = str_replace('{{' . $key . '}}', $value, $html);
                }
            }
            
            // Handle Loops (Simple implementation for Tables)
            // Pattern: <!-- {{#array_name}} --> content <!-- {{/array_name}} -->
            // Only supports 1 level of nesting
            if (preg_match_all('/<!--\s*\{\{#(\w+)\}\}\s*-->(.*?)<!--\s*\{\{\/\1\}\}\s*-->/s', $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $fullBlock = $match[0];
                    $arrayName = $match[1]; // e.g., 'collateral'
                    $innerHtml = $match[2]; // The row template
                    
                    $renderedLoop = '';
                    
                    if (isset($data[$arrayName]) && is_array($data[$arrayName])) {
                        $index = 1;
                        foreach ($data[$arrayName] as $item) {
                            $rowHtml = $innerHtml;
                            // Add handy index variable
                            $item['sn'] = $index++;
                            
                            // Replace variables in row
                            foreach ($item as $k => $v) {
                                if (is_string($v) || is_numeric($v)) {
                                    $rowHtml = str_replace('{{' . $k . '}}', $v, $rowHtml);
                                }
                            }
                            $renderedLoop .= $rowHtml;
                        }
                    }
                    
                    $html = str_replace($fullBlock, $renderedLoop, $html);
                }
            }
            
            // 3. Inject into Word Template SAFE MODE
            $this->injectHtmlSafely($templateProcessor, $code, $html);
        }
    }

    /**
     * Safe HTML Injection that reconstructs Tables manually
     * Advanced version: Supports colspan, rowspan, and custom widths
     */
    private function injectHtmlSafely($templateProcessor, $code, $html) {
        try {
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
            // Remove any whitespace to avoid empty text nodes affecting traversal
            $dom->preserveWhiteSpace = false;
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();

            $tables = $dom->getElementsByTagName('table');

            if ($tables->length > 0) {
                $domTable = $tables->item(0); 
                
                // Get table attributes for global style if possible
                // For now, standard full width
                $table = new \PhpOffice\PhpWord\Element\Table([
                    'borderSize' => 6, 
                    'borderColor' => '000000', 
                    'cellMargin' => 50,
                    'width' => 100 * 50, 
                    'unit' => 'pct',
                    'layout' => 'autofit' 
                ]);

                // Track rowspans: $rowSpanTracker[colIndex] = remainingRows
                $rowSpanTracker = [];

                $rows = $domTable->getElementsByTagName('tr');
                foreach ($rows as $domRow) {
                    $table->addRow();
                    $cells = $domRow->childNodes; // process child nodes to respect order
                    
                    $colIndex = 0;
                    
                    foreach ($cells as $domCell) {
                        if ($domCell->nodeName !== 'td' && $domCell->nodeName !== 'th') continue;
                        
                        // Check if this column is currently affected by a rowspan from above
                        while (isset($rowSpanTracker[$colIndex]) && $rowSpanTracker[$colIndex] > 0) {
                            // Add a "continue" cell for the vertical merge
                            $table->addCell(null, ['vMerge' => 'continue']);
                            $rowSpanTracker[$colIndex]--; // Decrement counter
                            $colIndex++;
                        }
                        
                        // Parse Attributes
                        $colspan = $domCell->getAttribute('colspan');
                        $rowspan = $domCell->getAttribute('rowspan');
                        $widthAttr = $domCell->getAttribute('width');
                        $style = $domCell->getAttribute('style');
                        
                        $gridSpan = ($colspan && $colspan > 1) ? (int)$colspan : null;
                        $vMerge = ($rowspan && $rowspan > 1) ? 'restart' : null;

                        // Width Calculation
                        // Default approx width (2000 twips)
                        $cellWidth = 2000; 
                        
                        // Try to parse style width first (e.g. width: 65.05pt)
                        if (preg_match('/width\s*:\s*([\d\.]+)(pt|px|%|in)/', $style, $matches)) {
                             $val = (float)$matches[1];
                             $unit = $matches[2];
                             if ($unit == 'pt') $cellWidth = $val * 20; // 1pt = 20 twips
                             elseif ($unit == 'in') $cellWidth = $val * 1440;
                             elseif ($unit == 'px') $cellWidth = $val * 15; // approx
                             // % is harder without total width, safely ignore or treat as proportional
                        } elseif ($widthAttr) {
                            // usually px or pt
                            $cellWidth = (float)$widthAttr * 15; 
                        }
                        
                        // If standard cell, add it
                        $cellStyle = [];
                        if ($gridSpan) $cellStyle['gridSpan'] = $gridSpan;
                        if ($vMerge) $cellStyle['vMerge'] = $vMerge;
                        
                        // Add Cell
                        $cell = $table->addCell($cellWidth, $cellStyle);
                        
                        // Content
                        $text = trim($domCell->textContent);
                        $isBold = false;
                        // Basic bold check
                        if ($domCell->getElementsByTagName('b')->length > 0 || 
                            $domCell->getElementsByTagName('strong')->length > 0 ||
                            $domCell->nodeName === 'th') {
                            $isBold = true;
                        }
                        
                        $cell->addText($text, ['name' => 'Mangal', 'size' => 10, 'bold' => $isBold]);

                        // Handle Rowspan logic
                        if ($vMerge === 'restart') {
                            $rowSpanTracker[$colIndex] = (int)$rowspan - 1; // -1 because current row counts
                            // Also need to handle colspan inside rowspan? Complex, but let's assume simple cases first.
                            // If this cell spans 2 cols AND 2 rows, we need to mark next col as occupied too.
                            if ($gridSpan) {
                                for ($g = 1; $g < $gridSpan; $g++) {
                                     $rowSpanTracker[$colIndex + $g] = (int)$rowspan - 1;
                                }
                            }
                        }

                        // Advance column index
                        $colIndex += ($gridSpan ? $gridSpan : 1);
                    }
                    
                    // Fill remaining rowspans for this row if they extend beyond last cell
                     while (isset($rowSpanTracker[$colIndex]) && $rowSpanTracker[$colIndex] > 0) {
                        $table->addCell(null, ['vMerge' => 'continue']);
                        $rowSpanTracker[$colIndex]--;
                        $colIndex++;
                    }
                }
                
                $templateProcessor->setComplexBlock($code, $table);
                echo "[DEBUG] Injected Advanced Reconstructed TABLE for $code\n";
                
            } else {
                $cleanText = strip_tags($html);
                $templateProcessor->setValue($code, $cleanText);
            }

        } catch (Exception $e) {
            $templateProcessor->setValue($code, "Error");
        }
    }

    /**
     * Process Dynamic Blocks (cloneBlock)
     * Useful for repeating entire sections (e.g. multiple tables for multiple owners)
     */
    public function processBlocks($templateProcessor, $data) {
        // We use a "Raw XML" approach to match the success of test_expansion.php
        // 1. Flush current state to the temp file
        // 2. Open Zip, manipulate XML directly using Regex
        // 3. Reload XML back into TemplateProcessor
        
        try {
            // A. Get Temp Filename via Reflection
            $ref = new \ReflectionClass($templateProcessor);
            $propFile = $ref->getProperty('tempDocumentFilename');
            $propFile->setAccessible(true);
            $tempFile = $propFile->getValue($templateProcessor);
            
            // B. Ensure latest changes are saved to this file
            $templateProcessor->save();
            
            // C. Open Zip
            $zip = new \ZipArchive;
            if ($zip->open($tempFile) === TRUE) {
                $xml = $zip->getFromName('word/document.xml');
                
                $modificationsMade = false;
                
                foreach ($data as $key => $items) {
                    // Start with generic "BLOCK_" or just use key as is if user defined
                    // Our standard: Key = BLOCK_NAME
                    $blockName = $key;
                    
                    // Match pattern: ${BLOCK}...${/BLOCK}
                    $blockStart = '${' . $blockName . '}';
                    $blockEnd = '${/' . $blockName . '}';
                    
                    // Regex to capture content. 
                    // We make the regex robust against XML tags inside the macros (e.g. ${<w:r>BLOCK...</w:r>})
                    // Pattern: ${ (optional tags) BLOCK_NAME (optional tags) }
                    // We use [^}]+ instead of block name strict match if we want to be super loose, but let's stick to name.
                    $searchStart = '\$\{(?:<[^>]+>)*' . preg_quote($blockName, '/') . '(?:<[^>]+>)*\}';
                    $searchEnd   = '\$\{(?:<[^>]+>)*\/' . preg_quote($blockName, '/') . '(?:<[^>]+>)*\}';
                    
                    $pattern = '/' . $searchStart . '(.*?)' . $searchEnd . '/s';
                    
                    echo "[DEBUG] Raw Clone Matching Pattern: $pattern\n";
                    
                    if (preg_match($pattern, $xml, $matches)) {
                        $fullMatch = $matches[0];
                        $innerContent = $matches[1];
                        
                        echo "[DEBUG] Raw Clone: Found Block $blockName (Length: " . strlen($innerContent) . ")\n";
                        
                        $clonedContent = '';
                        $idx = 1;
                        
                        foreach ($items as $itemData) {
                            $snippet = $innerContent;
                            
                            // RENAME LOGIC: TYPE1 -> TYPE$idx
                            if ($idx > 1) {
                                // Collateral Owners (Persons)
                                $snippet = str_replace('CO1', 'CO' . $idx, $snippet);
                                // Collateral Items (Properties)
                                $snippet = str_replace('COL1', 'COL' . $idx, $snippet);
                                // Borrowers
                                $snippet = str_replace('BR1', 'BR' . $idx, $snippet);
                                // Guarantors
                                $snippet = str_replace('GR1', 'GR' . $idx, $snippet);
                            }
                            
                            $clonedContent .= $snippet;
                            $idx++;
                        }
                        
                        // Replace in XML (Removing tags for clean output)
                        $xml = str_replace($fullMatch, $clonedContent, $xml);
                        $modificationsMade = true;
                        
                        echo "[DEBUG] Raw Clone: Expanded $blockName into " . count($items) . " copies.\n";
                    } else {
                        echo "[DEBUG] Raw Clone: Block $blockName tag not found in Raw XML.\n";
                    }
                }
                
                if ($modificationsMade) {
                    $zip->addFromString('word/document.xml', $xml);
                    $zip->close();
                    
                    // D. Reload back into TemplateProcessor memory
                    // This is CRITICAL so subsequent setValue calls see the new variables
                    $propMain = $ref->getProperty('tempDocumentMainPart');
                    $propMain->setAccessible(true);
                    $propMain->setValue($templateProcessor, $xml);

                    // E. RESURRECT ZipArchive
                    // TemplateProcessor->save() closed the zip. We must re-open it 
                    // and assign it back to the protected $zipClass property so future calls work.
                    $newZip = new \PhpOffice\PhpWord\Shared\ZipArchive();
                    $newZip->open($tempFile);
                    
                    $propZip = $ref->getProperty('zipClass');
                    $propZip->setAccessible(true);
                    $propZip->setValue($templateProcessor, $newZip);
                    
                } else {
                    $zip->close();
                    // Even if no mods, save() closed it. We must re-open.
                    $newZip = new \PhpOffice\PhpWord\Shared\ZipArchive();
                    $newZip->open($tempFile);
                    $propZip = $ref->getProperty('zipClass');
                    $propZip->setAccessible(true);
                    $propZip->setValue($templateProcessor, $newZip);
                }
                
            } else {
                echo "[DEBUG] Raw Clone: Failed to open temp zip: $tempFile\n";
            }
            
            // E. Fill Data 
            foreach ($data as $key => $items) {
                 $idx = 1;
                 foreach ($items as $itemData) {
                     foreach ($itemData as $var => $val) {
                         // Map CO1... to CO$idx...
                         if ($idx > 1 && strpos($var, 'CO1') === 0) {
                             $target = str_replace('CO1', 'CO' . $idx, $var);
                         } else {
                             $target = $var;
                         }
                         $templateProcessor->setValue($target, $val);
                     }
                     $idx++;
                 }
            }
            
        } catch (Exception $e) {
            echo "[DEBUG] Raw Clone Error: " . $e->getMessage() . "\n";
        }
    }


    /**
     * Render and Inject Paragraph Components (New System)
     * Replaces ${PARA_CODE} placeholders with rendered HTML
     */
    public function renderParagraphs($templateProcessor, $data) {
        // 1. Fetch all paragraphs from DB
        $result = $this->conn->query("SELECT * FROM doc_paragraphs");
        $paragraphs = $result->fetch_all(MYSQLI_ASSOC);
        
        foreach ($paragraphs as $para) {
            $code = $para['code'];
            $html = $para['content'];
            
            // 2. Resolve internal variables in HTML
            foreach ($data as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $html = str_replace('{{' . $key . '}}', $value, $html);
                    $html = str_replace('${' . $key . '}', $value, $html); // Also support ${VAR} style in paragraphs
                }
            }
            
            // 3. Inject into Word Template
            try {
                // Parse HTML to Elements
                $tempWord = new \PhpOffice\PhpWord\PhpWord();
                $section = $tempWord->addSection();
                
                // Add HTML to temporary section
                \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html);
                
                // Get Elements
                $elements = $section->getElements();
                
                if (count($elements) > 0) {
                     // Check if any element is a table or contains a table
                     $tableElement = null;
                     
                     foreach($elements as $el) {
                         if ($el instanceof \PhpOffice\PhpWord\Element\Table) {
                             $tableElement = $el;
                             break;
                         }
                     }
                     
                     // Optimization: If it's a Table, inject it.
                     if ($tableElement) {
                         $templateProcessor->setComplexBlock($code, $tableElement);
                         echo "[DEBUG] Injected TABLE for $code\n";
                     } else {
                        $templateProcessor->setComplexBlock($code, $elements[0]);
                        echo "[DEBUG] Injected BLOCK for $code\n";
                     }
                }
            } catch (Exception $e) {
                echo "[DEBUG] Failed to inject $code: " . $e->getMessage() . "\n";
            }
        }
    }


    /**
     * Generate Documents Grouped by Guarantor
     * 
     * Splits generation into multiple files, one for each guarantor.
     * Used for Personal Guarantee and Power of Attorney documents.
     */
    public function generateDocumentsGroupedByGuarantor($templatePath, $profileId, $docType) {
        $results = [];
        
        try {
            // 1. Fetch ALL Data first
            $ruleEngine = new DocumentRuleEngine($docType, $this->conn);
            $fullData = $ruleEngine->fetchData($profileId);
            
            // 2. Extract Guarantors
            $guarantors = $fullData['persons']['guarantors'] ?? [];
            
            if (empty($guarantors)) {
                // Return empty result or generate standard if no guarantors?
                // Probably standard generation (e.g. might be a generic template)
                // But normally this doc type requires guarantors.
                // Let's fallback to standard generation just in case.
                $res = $this->generateDocument($templatePath, $profileId, $docType);
                $res['guarantor_name'] = 'General';
                return [$res];
            }
            
            // 3. Generate Document for EACH Guarantor
            foreach ($guarantors as $guarantor) {
                // Create a Copy of Data
                $singleData = $fullData;
                
                // OVERWRITE guarantors with SINGLE item
                $singleData['persons']['guarantors'] = [$guarantor];
                
                // NOTE: We also need to filter 'guarantor_block' in logic
                // But since 'generateDocument' calls 'mapToPlaceholders' which likely uses $data['persons']['guarantors']
                // to build the block, just overwriting this array should be enough for the loop/block generation.
                
                // ALSO: Ensure specific mapping for "single guarantor" context if needed
                // (e.g. if template uses explicit indices like GR1_NM) -> The loop handles GR1 correctly as first item.
                
                // CUSTOMIZE FILENAME
                // We inject a special data property or handle it in return
                
                // Generate
                $res = $this->generateDocument($templatePath, $profileId, $docType, $singleData);
                
                if ($res['success']) {
                    // Rename the file to include guarantor name
                    $name = $guarantor['id'] . '_' . ($guarantor['name'] ?? $guarantor['full_name'] ?? 'Guarantor');
                    // Sanitize name
                    $safeName = $this->sanitizeFilename($name);
                    
                    $oldPath = $res['output_path'];
                    $dir = dirname($oldPath);
                    $ext = pathinfo($oldPath, PATHINFO_EXTENSION);
                    $base = pathinfo($templatePath, PATHINFO_FILENAME); // e.g. Personal_Guarantor
                    
                    // Use microtime or unique ID to ensure no collisions
                    $newFilename = "{$base}_{$safeName}_" . date('YmdHis') . ".{$ext}";
                    $newPath = $dir . '/' . $newFilename;
                    
                    if (rename($oldPath, $newPath)) {
                        $res['output_path'] = $newPath;
                        $res['relative_path'] = str_replace(basename($oldPath), $newFilename, $res['relative_path']);
                        $res['filename'] = $newFilename;
                    }
                    
                    $res['guarantor_name'] = $name; // For logging/DB
                }
                
                $results[] = $res;
            }
            
        } catch (Exception $e) {
            $results[] = ['success' => false, 'error' => $e->getMessage()];
        }
        
        return $results;
    }
}
