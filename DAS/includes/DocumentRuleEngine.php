<?php
/**
 * Document Rule Engine
 * Intelligently maps database data to template placeholders using configurable rules
 */

require_once __DIR__ . '/PlaceholderLibrary.php';
require_once __DIR__ . '/DocumentDataFetcher.php';
require_once __DIR__ . '/PlaceholderMapper.php';

class DocumentRuleEngine {
    
    private $documentType;
    private $rules;
    private $conn;
    
    /**
     * Initialize rule engine with document type
     * 
     * @param string $documentType Document type identifier (e.g., 'mortgage_deed')
     * @param mysqli $conn Database connection
     */
    public function __construct($documentType, $conn) {
        $this->documentType = $documentType;
        $this->conn = $conn;
        $this->loadRules();
    }
    
    /**
     * Load rules for the document type
     */
    private function loadRules() {
        $rulesFile = __DIR__ . '/rules/' . $this->documentType . '.json';
        
        if (!file_exists($rulesFile)) {
            // Fall back to default rules
            $rulesFile = __DIR__ . '/rules/default.json';
        }
        
        if (file_exists($rulesFile)) {
            $json = file_get_contents($rulesFile);
            $this->rules = json_decode($json, true);
        } else {
            throw new Exception("No rules found for document type: {$this->documentType}");
        }
    }
    
    /**
     * Fetch all data based on rules
     * 
     * @param int $customerProfileId Customer profile ID
     * @return array Complete data set
     */
    public function fetchData($customerProfileId) {
        $fetcher = new DocumentDataFetcher($this->conn, $customerProfileId);
        $rawData = $fetcher->fetchAll();
        
        // Organize data according to rules
        $organizedData = [
            'profile_id' => $customerProfileId,
            'persons' => [],
            'collateral' => [],
            'loan' => [],
            'bank' => []
        ];
        
        // Process persons (borrowers, guarantors, collateral owners)
        if (isset($this->rules['data_sources'])) {
            $sources = $this->rules['data_sources'];
            
            // Borrowers
            if (isset($sources['borrowers'])) {
                $organizedData['persons']['borrowers'] = $this->fetchPersons(
                    $rawData['borrowers'] ?? [],
                    $sources['borrowers']
                );
            }
            
            // Guarantors
            if (isset($sources['guarantors'])) {
                $rawGuarantors = $this->fetchPersons(
                    $rawData['guarantors'] ?? [],
                    $sources['guarantors']
                );
                
                // Separate Guarantors and Co-Borrowers
                $actualGuarantors = [];
                $coBorrowers = [];
                
                foreach ($rawGuarantors as $g) {
                    // Calculate Age if missing
                    if (empty($g['age']) && !empty($g['dob_ad'])) {
                         try {
                             $dob = new DateTime($g['dob_ad']);
                             $now = new DateTime();
                             $interval = $now->diff($dob);
                             $g['age'] = $interval->y;
                         } catch (Exception $e) {}
                    }

                    // Check if is_co_borrower flag is set (added in recent schema update)
                    if (isset($g['is_co_borrower']) && $g['is_co_borrower'] == 1) {
                         // Mark explicit source so fetching family details still works (they are in guarantors table)
                         $g['_source_type'] = 'Guarantor'; 
                         $coBorrowers[] = $g;
                         // IMPORTANT: Also add to actualGuarantors for POA/Guarantor document generation
                         $actualGuarantors[] = $g;
                    } else {
                         $g['_source_type'] = 'Guarantor';
                         $actualGuarantors[] = $g;
                    }
                }
                
                // Include ALL guarantors (both actual guarantors AND co-borrowers) for POA/Guarantor docs
                $organizedData['persons']['guarantors'] = $actualGuarantors;
                
                // Merge Co-Borrowers into Borrowers list
                // This ensures they get BR2, BR3 prefixes and show up in Borrower blocks
                if (!empty($coBorrowers)) {
                    if (!isset($organizedData['persons']['borrowers'])) {
                        $organizedData['persons']['borrowers'] = [];
                    }
                    $organizedData['persons']['borrowers'] = array_merge($organizedData['persons']['borrowers'], $coBorrowers);
                }
            }
            
            // Collateral owners (extracted from collateral)
            if (isset($sources['collateral_owners'])) {
                $organizedData['persons']['collateral_owners'] = $this->extractCollateralOwners(
                    $rawData['collateral'] ?? [],
                    $rawData['borrowers'] ?? [],
                    $rawData['guarantors'] ?? []
                );
            }
            
            // Collateral
            if (isset($sources['collateral'])) {
                $organizedData['collateral'] = $rawData['collateral'] ?? [];
            }
            
            // Loan
            if (isset($sources['loan'])) {
                $organizedData['loan'] = $rawData['loan'] ?? [];
            }
            
            // Limit (NEW)
            $organizedData['limit'] = $rawData['limit'] ?? [];
            $organizedData['loan']['limit'] = $rawData['limit'] ?? [];
            
            // Bank
            if (isset($sources['bank'])) {
                $organizedData['bank'] = $rawData['branch'] ?? [];
            }
        }
        
        return $organizedData;
    }
    
    /**
     * Map data to placeholders based on rules
     * 
     * @param array $data Organized data from fetchData()
     * @return array Placeholder => Value mapping
     */
    public function mapToPlaceholders($data) {
        $placeholders = [];
        
        // Map persons
        if (isset($data['persons'])) {
            // Borrowers
            $enrichedBorrowers = [];
            if (isset($data['persons']['borrowers'])) {
                $index = 1;
                foreach ($data['persons']['borrowers'] as $borrower) {
                    // Enrich with family details for Legal Block
                    $familyDetails = $this->fetchFamilyDetails($borrower['id'], $borrower['_source_type'] ?? 'Borrower');
                    $enrichedB = array_merge($borrower, $familyDetails);
                    $enrichedBorrowers[] = $enrichedB;

                    $personPlaceholders = $this->mapPersonToPlaceholders('Borrower', $index, $borrower);
                    $placeholders = array_merge($placeholders, $personPlaceholders);
                    $index++;
                }
                
                // Generate Smart Legal Paragraph
                $placeholders['BORROWER_LEGAL_BLOCK'] = $this->generateLegalParagraph($enrichedBorrowers);
            }
            
            // Guarantors
            $enrichedGuarantors = [];
            if (isset($data['persons']['guarantors'])) {
                $index = 1;
                foreach ($data['persons']['guarantors'] as $guarantor) {
                    // Enrich with family details for Legal Block
                    $familyDetails = $this->fetchFamilyDetails($guarantor['id'], $guarantor['_source_type'] ?? 'Guarantor');
                    $enrichedG = array_merge($guarantor, $familyDetails);
                    $enrichedGuarantors[] = $enrichedG;
                    
                    $personPlaceholders = $this->mapPersonToPlaceholders('Guarantor', $index, $guarantor);
                    $placeholders = array_merge($placeholders, $personPlaceholders);
                    $index++;
                }
                
                // Generate Smart Legal Paragraph for Guarantors (Default "Rini" often inaccurate for POA, so we add POA variant)
                $placeholders['GUARANTOR_LEGAL_BLOCK'] = $this->generateLegalParagraph($enrichedGuarantors, 'ऋणी'); // Default fall back or custom? Usually Guarantor is Jamaani. 
                // Wait, standard Guarantor block usually refers to them as Jamaani? 
                // The previous prompt Rule 5 said "(जसलाई यसपछि यस लिखतमा संयुक्त रुपमा ऋणी भनिएको छ)". 
                // If user wants specific POA one:
                $placeholders['GUARANTOR_LEGAL_BLOCK_POA'] = $this->generateLegalParagraph($enrichedGuarantors, 'वारेसनामा दिने व्यक्ति');
            }
            
            // Collateral Owners
            $enrichedOwners = [];
            if (isset($data['persons']['collateral_owners'])) {
                $index = 1;
                foreach ($data['persons']['collateral_owners'] as $owner) {
                    // Enrich
                    $familyDetails = $this->fetchFamilyDetails($owner['id'], $owner['_source_type'] ?? 'Borrower');
                    $enrichedO = array_merge($owner, $familyDetails);
                    $enrichedOwners[] = $enrichedO;
                    
                    $personPlaceholders = $this->mapPersonToPlaceholders('CollateralOwner', $index, $owner);
                    $placeholders = array_merge($placeholders, $personPlaceholders);
                    $index++;
                }
                
                // Generate Smart Legal Paragraph for Collateral Owners (Role: Land Owner)
                // This block is specific: it ends with property ownership declaration
                $placeholders['COLLATERAL_OWNER_LEGAL_BLOCK'] = $this->generateCollateralOwnerBlock($enrichedOwners);
            }
        }
        
        // Map collateral
        if (isset($data['collateral'])) {
            $index = 1;
            foreach ($data['collateral'] as $col) {
                $colPlaceholders = $this->mapCollateralToPlaceholders($index, $col);
                $placeholders = array_merge($placeholders, $colPlaceholders);
                $index++;
            }
        }
        
        // Map loan
        if (isset($data['loan'])) {
            $loanPlaceholders = $this->mapLoanToPlaceholders($data['loan']);
            $placeholders = array_merge($placeholders, $loanPlaceholders);
        }
        
        // Map limit (NEW: Overrides Loan if conflict, handles Base Rate/Premium)
        if (isset($data['limit'])) {
            $limitPlaceholders = $this->mapLimitToPlaceholders($data['limit']);
            $placeholders = array_merge($placeholders, $limitPlaceholders);
        }
        
        // Map bank (branch)
        if (isset($data['bank'])) {
            $mapper = new PlaceholderMapper(['branch' => $data['bank']]);
            $bankPlaceholders = $mapper->mapBranchDetails();
            $placeholders = array_merge($placeholders, $bankPlaceholders);
        }
        
        // Fix for template typos
        if (isset($placeholders['BR1_NM_EN'])) {
            $placeholders['1_NM_EN'] = $placeholders['BR1_NM_EN'];
        }
        // Fix for Loan Amount Words alias (template uses full name)
        if (isset($placeholders['LN_AMT_W'])) {
            $placeholders['LN_AMT_WORDS'] = $placeholders['LN_AMT_W'];
        }

        // ============================================
        // GLOBAL PLACEHOLDERS (SN, DATES, COMBOS)
        // ============================================
        
        // 1. Serial Number (Default to 1 for single docs)
        $placeholders['SN'] = '१';
        $placeholders['SN1'] = '१';
        
        // 2. Combined Borrower Names (Ram / Shyam)
        if (!empty($enrichedBorrowers)) {
             $bNames = [];
             foreach ($enrichedBorrowers as $eb) {
                 $bNames[] = $eb['full_name_np'] ?? $eb['full_name'] ?? '';
             }
             // Filter empty
             $bNames = array_filter($bNames);
             $combined = implode(' / ', $bNames);
             
             // Placeholders
             $placeholders['BORROWERS_COMBINED_NAMES'] = $combined;
             $placeholders['BR_COMBINED_NAMES'] = $combined;
             // Legacy/Request support
             $placeholders['BR_CO_COMBINED_NAME'] = $combined;
        }
        
        return $placeholders;
    }
    
    /**
     * Map a person to placeholders
     */
    private function mapPersonToPlaceholders($personType, $number, $personData) {
        // Determine DB person type (use source type if available, e.g., for Collateral Owner who is really a Guarantor)
        $dbPersonType = $personData['_source_type'] ?? $personType;
        
        // Fetch family details and merge into person data
        $familyDetails = $this->fetchFamilyDetails($personData['id'], $dbPersonType);
        $personData = array_merge($personData, $familyDetails);
        
        $mapper = new PlaceholderMapper(['person' => $personData]);
        
        // Get prefix (BR, GR, CO)
        $prefix = PlaceholderLibrary::$PERSON_TYPES[$personType] ?? 'BR';
        $fullPrefix = $prefix . $number . '_';
        
        $placeholders = [];
        
        // Map each field from person_fields
        foreach (PlaceholderLibrary::$FIELD_MAPPING['person_fields'] as $field => $code) {
            $placeholder = $fullPrefix . $code;
            $value = $mapper->getPersonFieldValue($personData, $field);
            $placeholders[$placeholder] = $value;
        }
        
        return $placeholders;
    }
    
    /**
     * Fetch family details from database
     */
    private function fetchFamilyDetails($personId, $personType) {
        $data = [];
        $stmt = $this->conn->prepare("SELECT relation, name FROM family_details WHERE person_id = ? AND person_type = ?");
        $stmt->bind_param("is", $personId, $personType);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $relation = strtolower(trim($row['relation']));
            $name = $row['name'];
            
            // Map common relations
            if (in_array($relation, ['father', 'बुबा', 'buwa'])) $data['father_name'] = $name;
            elseif (in_array($relation, ['mother', 'आमा', 'aama'])) $data['mother_name'] = $name;
            elseif (in_array($relation, ['grandfather', 'बाजे', 'hjur buwa'])) $data['grandfather_name'] = $name;
            elseif (in_array($relation, ['grandmother', 'बज्यै', 'hjur aama'])) $data['grandmother_name'] = $name;
            elseif (in_array($relation, ['spouse', 'husband', 'wife', 'पति', 'पत्नी', 'पति/पत्नी'])) $data['spouse_name'] = $name;
            elseif (in_array($relation, ['son', 'छोरा'])) $data['son_name'] = $name;
            elseif (in_array($relation, ['daughter', 'छोरी'])) $data['daughter_name'] = $name;
            elseif (in_array($relation, ['father-in-law', 'ससुरा'])) $data['father_in_law'] = $name;
            elseif (in_array($relation, ['mother-in-law', 'सासु'])) $data['mother_in_law'] = $name;
        }
        
        return $data;
    }
    
    /**
     * Map collateral to placeholders
     */
    private function mapCollateralToPlaceholders($number, $collateralData) {
        $mapper = new PlaceholderMapper(['collateral' => $collateralData]);
        $placeholders = [];
        
        foreach (PlaceholderLibrary::$FIELD_MAPPING['collateral_fields'] as $field => $code) {
            // Handle COL prefix vs COL1 prefix
            $suffix = str_replace('COL_', '', $code);
            $placeholder = 'COL' . $number . '_' . $suffix;
            $value = $mapper->getCollateralFieldValue($collateralData, $field);
            $placeholders[$placeholder] = $value;
        }
        
        return $placeholders;
    }
    
    /**
     * Map loan to placeholders
     */
    private function mapLoanToPlaceholders($loanData) {
        require_once __DIR__ . '/NepaliDateConverter.php'; // Ensure loaded

        $mapper = new PlaceholderMapper(['loan' => $loanData]);
        $converter = new NepaliDateConverter();
        
        $placeholders = [];
        
        foreach (PlaceholderLibrary::$FIELD_MAPPING['loan_fields'] as $field => $code) {
            $placeholder = $code;
            
            // Special handling for Approved Date - get RAW value, not pre-converted
            if ($field === 'loan_approved_date') {
                 $bsDateEn = $loanData['loan_approved_date'] ?? ''; // Get raw BS date (2082-10-04)
                 
                 if (!empty($bsDateEn)) {
                     // 1. Calculate AD Date from BS
                     $adDate = $converter->convertBsToAd($bsDateEn);
                     
                     // 2. Map Placeholders - ALL in Nepali numerals
                     $placeholders['LN_LSL_DT'] = NepaliDateConverter::formatToNepaliNums($adDate); // AD Date in Nepali
                     $placeholders['LN_LSL_DT_AD'] = NepaliDateConverter::formatToNepaliNums($adDate); // Explicit AD in Nepali
                     $placeholders['LN_LSL_DT_BS'] = NepaliDateConverter::formatToNepaliNums($bsDateEn); // BS in Nepali
                     $placeholders['LN_LSL_DT_NP'] = NepaliDateConverter::formatToNepaliNums($bsDateEn); // BS in Nepali (alias)
                 }
                 
                 // Skip the default assignment below since we handled it specially
                 continue;
            }
            
            // For all other fields, use mapper
            $value = $mapper->getLoanFieldValue($loanData, $field);
            $placeholders[$placeholder] = $value;
        }
        
        // Manual Map for Ref No since explicit field mapping might be mixed
        if (isset($loanData['approval_ref_no'])) {
            $placeholders['LN_REF_NO'] = $loanData['approval_ref_no'];
        }
        
        return $placeholders;
    }
    
    /**
     * Map bank to placeholders
     */
    private function mapBankToPlaceholders($bankData) {
        $placeholders = [];
        
        foreach (PlaceholderLibrary::$FIELD_MAPPING['bank_fields'] as $field => $code) {
            $placeholder = $code;
            $placeholders[$placeholder] = $bankData[$field] ?? '';
        }
        
        return $placeholders;
    }

    /**
     * Map limit details to placeholders (Base Rate, Premium, etc.)
     */
    private function mapLimitToPlaceholders($limitData) {
        require_once __DIR__ . '/NepaliDateConverter.php';
        
        $placeholders = [];
        
        // Interest/Base/Premium - Convert to Nepali numerals
        if (isset($limitData['base_rate'])) {
            $placeholders['LN_BASE'] = NepaliDateConverter::formatToNepaliNums($limitData['base_rate']);
        }
        if (isset($limitData['premium'])) {
            $placeholders['LN_PREMIUM'] = NepaliDateConverter::formatToNepaliNums($limitData['premium']);
        }
        if (isset($limitData['interest_rate'])) {
            $placeholders['LN_RATE'] = NepaliDateConverter::formatToNepaliNums($limitData['interest_rate']);
        }
        
        // Amount - Format with commas then convert to Nepali
        if (isset($limitData['amount'])) {
            $formatted = number_format((float)$limitData['amount'], 2);
            $placeholders['LN_AMT'] = NepaliDateConverter::formatToNepaliNums($formatted);
        }
        
        // Amount Words (already in Nepali from user input)
        if (isset($limitData['amount_in_words'])) {
            $placeholders['LN_AMT_WORDS'] = $limitData['amount_in_words'];
            $placeholders['LN_AMT_W'] = $limitData['amount_in_words']; // Alias
        }
        
        // Tenure - Convert to Nepali numerals
        if (isset($limitData['tenure'])) {
            $placeholders['LN_TENURE'] = NepaliDateConverter::formatToNepaliNums($limitData['tenure']); // Months
            $placeholders['LN_TENURE_Y'] = NepaliDateConverter::formatToNepaliNums(round($limitData['tenure'] / 12, 1)); // Years
        }
        
        return $placeholders;
    }
    
    /**
     * Fetch persons with limits
     */
    private function fetchPersons($persons, $rules) {
        $maxCount = $rules['max_count'] ?? 99;
        return array_slice($persons, 0, $maxCount);
    }
    
    /**
     * Extract collateral owners from collateral data
     */
    private function extractCollateralOwners($collateral, $borrowers, $guarantors) {
        $owners = [];
        
        foreach ($collateral as $col) {
            $ownerId = $col['owner_id'] ?? null;
            $ownerType = $col['owner_type'] ?? 'Borrower';
            
            if (!$ownerId) continue;
            
            // Find owner in borrowers or guarantors
            $owner = null;
            if ($ownerType === 'Borrower') {
                foreach ($borrowers as $b) {
                    if ($b['id'] == $ownerId) {
                        $owner = $b;
                        $owner['_source_type'] = 'Borrower';
                        break;
                    }
                }
            } else {
                foreach ($guarantors as $g) {
                    if ($g['id'] == $ownerId) {
                        $owner = $g;
                        $owner['_source_type'] = 'Guarantor';
                        break;
                    }
                }
            }
            
            if ($owner && !in_array($owner, $owners)) {
                $owners[] = $owner;
            }
        }
        
        return $owners;
    }
    
    /**
     * Fetch bank information
     */
    private function fetchBankInfo() {
        // TODO: Fetch from system settings or config
        return [
            'bank_name_np' => 'सहकारी बैंक',
            'bank_name_en' => 'Sahakari Bank',
            'branch_name' => 'Main Branch',
            'branch_sol' => '001',
            'branch_province' => 'Bagmati',
            'branch_address' => 'Kathmandu',
            'document_date_bs' => date('Y-m-d'), // TODO: Convert to BS
            'document_date_ad' => date('Y-m-d')
        ];
    }

    // ===================================
    // LEGAL DRAFTING ENGINE
    // ===================================
    
    /**
     * Generate formal Nepali legal paragraph block
     * @param array $persons List of persons
     * @param string $roleName The role description (e.g. 'ऋणी', 'वारेसनामा दिने व्यक्ति')
     */
    private function generateLegalParagraph($persons, $roleName = 'ऋणी') {
        if (empty($persons)) return '';

        $paragraphs = [];
        
        foreach ($persons as $person) {
            $part = "";
            
            // -----------------------------------------
            // A. RELATIONSHIP LOGIC
            // -----------------------------------------
            $gender = $person['gender'] ?? ''; 
            $status = $person['relationship_status'] ?? ''; 
            
            $grandparent_rel = 'नाति';
            $parent_rel = 'छोरा';
            $spouse_rel = '';
            
            if ($gender == 'Male') {
                if ($status == 'Married') {
                    $grandparent_rel = 'नाति';
                    $parent_rel = 'छोरा';
                    $spouse_rel = 'पति';
                } else {
                    $grandparent_rel = 'नाति';
                    $parent_rel = 'छोरा';
                }
            } else {
                if ($status == 'Married') {
                    $grandparent_rel = 'बुहारी';
                    $parent_rel = 'छोरी';
                    $spouse_rel = 'पत्नी';
                } else {
                    $grandparent_rel = 'नातिनि';
                    $parent_rel = 'छोरी';
                }
            }
            
            // -----------------------------------------
            // BUILD PARAGRAPH - SINGLE LINE FORMAT
            // -----------------------------------------
            // 1. Grandfather
            $gfName = $person['grandfather_name_np'] ?? $person['grandfather_name'] ?? '..........';
            $part .= $gfName . "को " . $grandparent_rel . ", ";
            
            // 2. Father/Mother
            $fatherName = $person['father_name_np'] ?? $person['father_name'] ?? '..........';
            $motherName = $person['mother_name_np'] ?? $person['mother_name'] ?? '';
            
            // Include both father and mother if available
            if (!empty($motherName)) {
                $part .= $fatherName . "/" . $motherName . "को " . $parent_rel . " ";
            } else {
                $part .= $fatherName . "को " . $parent_rel . " ";
            }
            
            // 3. Spouse (if applicable)
            $spouseName = $person['spouse_name_np'] ?? $person['spouse_name'] ?? '';
            if (!empty($spouse_rel) && !empty($spouseName)) {
                 $part .= $spouseName . "को " . $spouse_rel . " ";
            }

            // 4. Permanent Address
            $pDist = $person['perm_district_np'] ?? $person['perm_district'] ?? '..........';
            $pMun = $person['perm_municipality_vdc_np'] ?? $person['perm_municipality_vdc'] ?? '..........';
            $pWard = $person['perm_ward_no'] ?? '...';
            
            $part .= $pDist . " जिल्ला, " . $pMun . " वडा नं- " . $pWard . " स्थायी ठेगाना भई ";
            
            // 5. Temporary Address
            $tDist = $person['temp_district_np'] ?? $person['temp_district'] ?? $pDist;
            $tMun = $person['temp_municipality_vdc_np'] ?? $person['temp_municipality_vdc'] ?? $pMun;
            $tWard = $person['temp_ward_no'] ?? $pWard;
            
            $part .= "हाल " . $tDist . " जिल्ला, " . $tMun . " वडा नं- " . $tWard . " बस्ने ";
            
            // 6. Name/Age
            // Calculate age from DOB if not provided
            $age = $person['age'] ?? null;
            if (empty($age) && !empty($person['date_of_birth'])) {
                $dob = $person['date_of_birth'];
                // DOB is in BS format (YYYY-MM-DD)
                $dobParts = explode('-', $dob);
                if (count($dobParts) == 3) {
                    $currentBS = 2082; // Current BS year (approximate)
                    $age = $currentBS - (int)$dobParts[0];
                }
            }
            $age = $age ?? '...';
            
            $name = $person['full_name_np'] ?? $person['full_name'] ?? '..........';
            $part .= "वर्ष " . $age . " को " . $name . " ";

            // -----------------------------------------
            // B. CITIZENSHIP LOGIC
            // -----------------------------------------
            $citNo = $person['citizenship_number'] ?? '..........';
            $issueDist = $person['id_issue_district'] ?? '..........';
            $issueDate = $person['id_issue_date'] ?? '..........';
            $authorityInput = $person['id_issue_authority'] ?? '';
            
            // Map Authority to Abbreviation
            $authorityAbbr = 'जि.प्र.का / ई.प्र.का'; // Default fallback
            if (mb_stripos($authorityInput, 'District') !== false || mb_stripos($authorityInput, 'Jilla') !== false || mb_stripos($authorityInput, 'जिल्ला') !== false) {
                $authorityAbbr = 'जि.प्र.का';
            } elseif (mb_stripos($authorityInput, 'Area') !== false || mb_stripos($authorityInput, 'Ilaka') !== false || mb_stripos($authorityInput, 'ईलाका') !== false) {
                $authorityAbbr = 'ई.प्र.का';
            }
            
            $citText = "(ना.प्र.नं " . $citNo;
            $citText .= " मिति " . $issueDate . " मा जारी";
            
            // Re-issue
            $reissueTimes = $person['reissue_times'] ?? 0;
            if (!empty($person['id_reissue_date']) && !empty($reissueTimes)) {
                 $reissueDate = $person['id_reissue_date'];
                 $citText .= " भई\nमिति " . $reissueDate . " मा " . $reissueTimes . " जारी";
            }
            $citText .= ")";
            $part .= $citText;
            
            // -----------------------------------------
            // C. SUFFIX (No line break - keep on same line)
            // -----------------------------------------
            $part .= " ......१"; 

            $paragraphs[] = $part;
        }
        
        // D. JOIN (No line breaks - keep on same line)
        $fullText = implode(" तथा ", $paragraphs);
        
        // E. CLOSE (No line breaks - continuous text)
        $fullText .= " समेत गरी जम्मा जना " . count($persons);
        $fullText .= "(जसलाई यसपछि यस लिखतमा संयुक्त रुपमा " . $roleName . " भनिएको छ)";
        
        return $fullText;
    }
    /**
     * Generate Collateral Owner Block (Specific Logic)
     * Ends with ownership declaration
     */
    private function generateCollateralOwnerBlock($persons) {
        if (empty($persons)) return '';

        $paragraphs = [];
        foreach ($persons as $person) {
            $part = "";
            
            // A. RELATIONSHIP
            $gender = $person['gender'] ?? ''; 
            $status = $person['relationship_status'] ?? ''; 
            $gfName = $person['grandfather_name_np'] ?? $person['grandfather_name'] ?? '..........';
            $fatherName = $person['father_name_np'] ?? $person['father_name'] ?? '..........';
            $motherName = $person['mother_name_np'] ?? $person['mother_name'] ?? '..........';
            
            // Logic: Male -> Naati   | Female+Single -> Naatini | Female+Married -> Buhari
            $grandparent_rel = ($gender == 'Male') ? 'नाति' : ( ($status == 'Married') ? 'बुहारी' : 'नातिनि' );
            
            // Logic: Male -> Chhora  | Female -> Chhori
            $parent_rel = ($gender == 'Male') ? 'छोरा' : 'छोरी';
            
            // Build: GF... ko naati/naatini
            $part .= $gfName . "को " . $grandparent_rel . ", ";
            
            // Build: Father... ko chhora/chhori (Request said "Father/Mother ko chhora/chhori")
            if (!empty($motherName) && $motherName !== '..........') {
                 $part .= $fatherName . "/" . $motherName . "को " . $parent_rel . " ";
            } else {
                 $part .= $fatherName . "को " . $parent_rel . " ";
            }
            
            // B. ADDRESS
            $pDist = $person['perm_district_np'] ?? $person['perm_district'] ?? '..........';
            $pMun = $person['perm_municipality_vdc_np'] ?? $person['perm_municipality_vdc'] ?? '..........';
            $pWard = $person['perm_ward_no'] ?? '...';
            $tDist = $person['temp_district_np'] ?? $person['temp_district'] ?? $pDist;
            $tMun = $person['temp_municipality_vdc_np'] ?? $person['temp_municipality_vdc'] ?? $pMun;
            $tWard = $person['temp_ward_no'] ?? $pWard;
            
            $part .= $pDist . " जिल्ला, " . $pMun . " वडा नं- " . $pWard . " स्थायी ठेगाना भई हाल ";
            $part .= $tDist . " जिल्ला, " . $tMun . " वडा नं- " . $tWard . " बस्ने ";
            
            // C. NAME & CITIZENSHIP
            $age = $person['age'] ?? '...';
            $name = $person['full_name_np'] ?? $person['full_name'] ?? '..........';
            $citNo = $person['citizenship_number'] ?? '..........';
            $issueDate = $person['id_issue_date'] ?? '..........';
            
            $part .= "वर्ष " . $age . " को " . $name . " (ना.प्र.नं " . $citNo . " मिति " . $issueDate . " मा जारी)………१";
            
            $paragraphs[] = $part;
        }
        
        // D. JOIN
        $fullText = implode("\nतथा\n", $paragraphs);
        
        // E. SPECIFIC SUFFIX
        $suffix = " को नाममा दर्ता श्रेस्ता कायम रहेको निज";
        if (count($persons) > 1) {
            $suffix .= "हरुको संयुक्त"; // "Their Joint"
        } else {
            $suffix .= "को एकलौटी"; // "His/Her Sole"
        }
        $suffix .= " हक, भोग तथा स्वामितवको देहाय बमोजिमको अचल सम्पत्ति (जग्गा, घर/जग्गा)";
        
        return $fullText . $suffix;
    }
}
