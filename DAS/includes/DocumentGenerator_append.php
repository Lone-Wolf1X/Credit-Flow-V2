
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
                    $name = $guarantor['name'] ?? $guarantor['full_name'] ?? 'Guarantor';
                    // Sanitize name
                    $safeName = $this->sanitizeFilename($name);
                    
                    $oldPath = $res['output_path'];
                    $dir = dirname($oldPath);
                    $ext = pathinfo($oldPath, PATHINFO_EXTENSION);
                    $base = pathinfo($templatePath, PATHINFO_FILENAME); // e.g. Personal_Guarantor
                    
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
