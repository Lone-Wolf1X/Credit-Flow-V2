<?php
/**
 * Document Data Fetcher
 * Fetches all required data from database for document generation
 */

class DocumentDataFetcher {
    private $conn;
    private $customer_profile_id;
    
    public function __construct($conn, $customer_profile_id) {
        $this->conn = $conn;
        $this->customer_profile_id = $customer_profile_id;
    }
    
    /**
     * Fetch all data needed for document generation
     */
    public function fetchAllData() {
        return [
            'profile' => $this->fetchProfile(),
            'borrowers' => $this->fetchBorrowers(),
            'guarantors' => $this->fetchGuarantors(),
            'collateral' => $this->fetchCollateral(),
            'loan' => $this->fetchLoanDetails(),
            'branch' => $this->fetchBranchData(),
            'limit' => $this->fetchLimitDetails(),
            'system' => $this->fetchSystemData()
        ];
    }
    
    /**
     * Alias for fetchAllData() - used by DocumentRuleEngine
     */
    public function fetchAll() {
        return $this->fetchAllData();
    }
    
    /**
     * Fetch customer profile
     */
    private function fetchProfile() {
        $stmt = $this->conn->prepare("
            SELECT * FROM customer_profiles 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $this->customer_profile_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Fetch all borrowers for this profile
     */
    private function fetchBorrowers() {
        $stmt = $this->conn->prepare("
            SELECT * FROM borrowers 
            WHERE customer_profile_id = ?
            ORDER BY id ASC
        ");
        $stmt->bind_param("i", $this->customer_profile_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Fetch all guarantors for this profile
     */
    private function fetchGuarantors() {
        $stmt = $this->conn->prepare("
            SELECT * FROM guarantors 
            WHERE customer_profile_id = ?
            ORDER BY id ASC
        ");
        $stmt->bind_param("i", $this->customer_profile_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Fetch all collateral for this profile
     */
    private function fetchCollateral() {
        $stmt = $this->conn->prepare("
            SELECT * FROM collateral 
            WHERE customer_profile_id = ?
            ORDER BY id ASC
        ");
        $stmt->bind_param("i", $this->customer_profile_id);
        $stmt->execute();
        $collateralItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Enrich with Owner Details
        foreach ($collateralItems as &$item) {
            $owner = $this->getCollateralOwner($item);
            if ($owner) {
                // Flatten owner details into collateral item with prefix keys if needed, 
                // OR just add the full object.
                // For simplicity in PlaceholderMapper, we might want specific fields.
                $item['owner_name_np'] = $owner['full_name_np'] ?? '';
                $item['owner_name_en'] = $owner['full_name_en'] ?? $owner['full_name'] ?? '';
                $item['owner_citizenship_number'] = $owner['citizenship_number'] ?? '';
                $item['owner_father_name'] = $owner['father_name'] ?? '';
                $item['owner_id_district'] = $owner['id_issue_district'] ?? '';
                // Add more as needed
            }
        }
        
        return $collateralItems;
    }
    
    /**
     * Fetch loan details
     */
    private function fetchLoanDetails() {
        $stmt = $this->conn->prepare("
            SELECT * FROM loan_details 
            WHERE customer_profile_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $this->customer_profile_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Fetch limit details
     */
    private function fetchLimitDetails() {
        $stmt = $this->conn->prepare("
            SELECT * FROM limit_details 
            WHERE customer_profile_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $this->customer_profile_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Fetch system data (bank info, current date, etc.)
     */
    private function fetchSystemData() {
        // Get bank name from settings
        $result = $this->conn->query("
            SELECT setting_value FROM system_settings 
            WHERE setting_key = 'company_name'
        ");
        $bank_name = $result ? $result->fetch_assoc()['setting_value'] : 'Nepal SBI Bank Limited';
        
        return [
            'bank_name' => $bank_name,
            'bank_name_np' => 'नेपाल एसबिआई बैंक लिमिटेड',
            'current_date_ad' => date('Y-m-d'),
            'current_date_bs' => $this->convertADtoBS(date('Y-m-d'))
        ];
    }
    
    /**
     * Get owner details (borrower or guarantor) for collateral
     */
    public function getCollateralOwner($collateral) {
        if ($collateral['owner_type'] == 'Borrower') {
            $stmt = $this->conn->prepare("SELECT * FROM borrowers WHERE id = ?");
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM guarantors WHERE id = ?");
        }
        
        $stmt->bind_param("i", $collateral['owner_id']);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Fetch branch data for current user (considering deputation)
     */
    private function fetchBranchData() {
        // Get current user ID from session
        $user_id = $_SESSION['user_id'] ?? null;
        
        // Fallback: If no session user, get creator from customer profile
        if (!$user_id && $this->customer_profile_id) {
            $stmt = $this->conn->prepare("SELECT created_by FROM customer_profiles WHERE id = ?");
            $stmt->bind_param("i", $this->customer_profile_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $user_id = $row['created_by'];
            }
        }
        
        if (!$user_id) {
            return null;
        }
        
        // Fetch user's current effective branch (SOL ID)
        $stmt = $this->conn->prepare("SELECT current_branch_id, sol_id FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();
        
        // Use current_branch_id (int SOL ID) or fallback to sol_id (string)
        $sol_id = $user_data['current_branch_id'] ?? $user_data['sol_id'] ?? null;
        
        if (!$sol_id) {
            // Last resort: Get the first available branch
            $stmt = $this->conn->prepare("SELECT sol_id FROM branch_profiles ORDER BY id ASC LIMIT 1");
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $sol_id = $res['sol_id'] ?? null;
        }
        
        if (!$sol_id) {
            return null;
        }
        
        // Fetch branch details using SOL ID
        $stmt = $this->conn->prepare("
            SELECT * FROM branch_profiles 
            WHERE sol_id = ?
        ");
        $stmt->bind_param("s", $sol_id);
        $stmt->execute();
        $branch = $stmt->get_result()->fetch_assoc();

        if ($branch) {
            // Construct Legal Branch Address String
            // Format: "बाग्मती प्रदेश काठमाडौं जिल्ला काठमाडौं महानगरपालिका वडा नं २८ स्थित रजिष्ट्रर्ड कार्यालय रहेको नेपाल एसबिआई बैंक लिमिटेडको [Province] [District] [LocalBody] वडा नं [Ward] को [Branch Name] शाखा"
            
            // Default Head Office Part (Static for now, can be moved to settings)
            $ho_part = "बाग्मती प्रदेश काठमाडौं जिल्ला काठमाडौं महानगरपालिका वडा नं २८ स्थित रजिष्ट्रर्ड कार्यालय रहेको नेपाल एसबिआई बैंक लिमिटेडको";
            
            // Dynamic Branch Part
            $province = $branch['province_np'] ?? '';
            $district = $branch['district_np'] ?? '';
            $local_body = $branch['local_body_np'] ?? ''; // Using new column
            $ward = $branch['ward_no_np'] ?? '';          // Using new column
            $branch_name = $branch['branch_name_np'] ?? '';
            
            // Fallback if Nepali data missing (use English or placeholders)
            if (empty($branch_name)) $branch_name = $branch['branch_name_np'] ?? $branch['branch_name'] . ' (NP)';
            
            $branch_part = " {$province} {$district} {$local_body} वडा नं {$ward} को {$branch_name} शाखा";
            
            $branch['legal_address_string'] = $ho_part . $branch_part;
        }

        return $branch;
    }
    
    /**
     * Simple AD to BS conversion (placeholder - use proper library)
     */
    private function convertADtoBS($adDate) {
        // TODO: Implement proper AD to BS conversion
        // For now, return formatted current BS date
        return '२०८१-०९-२३';
    }
}
