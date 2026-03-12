<?php
/**
 * Borrower Master Functions
 * Handles master borrower/guarantor records for reuse
 */

/**
 * Search master borrowers
 * @param string $search_term Search by name, citizenship, or PAN
 * @param string $type Individual or Corporate
 * @return array Matching borrowers
 */
function searchMasterBorrowers($search_term, $type = null) {
    global $das_conn;
    
    $search = "%{$search_term}%";
    $sql = "SELECT * FROM master_borrowers WHERE (
                full_name LIKE ? OR 
                citizenship_number LIKE ? OR 
                pan_number LIKE ? OR
                company_name LIKE ?
            )";
    
    if ($type) {
        $sql .= " AND borrower_type = ?";
        $stmt = $das_conn->prepare($sql . " LIMIT 20");
        $stmt->bind_param("sssss", $search, $search, $search, $search, $type);
    } else {
        $stmt = $das_conn->prepare($sql . " LIMIT 20");
        $stmt->bind_param("ssss", $search, $search, $search, $search);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Search master guarantors
 */
function searchMasterGuarantors($search_term, $type = null) {
    global $das_conn;
    
    $search = "%{$search_term}%";
    $sql = "SELECT * FROM master_guarantors WHERE (
                full_name LIKE ? OR 
                citizenship_number LIKE ? OR 
                pan_number LIKE ? OR
                company_name LIKE ?
            )";
    
    if ($type) {
        $sql .= " AND guarantor_type = ?";
        $stmt = $das_conn->prepare($sql . " LIMIT 20");
        $stmt->bind_param("sssss", $search, $search, $search, $search, $type);
    } else {
        $stmt = $das_conn->prepare($sql . " LIMIT 20");
        $stmt->bind_param("ssss", $search, $search, $search, $search);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get master borrower by ID
 */
function getMasterBorrower($id) {
    global $das_conn;
    
    $stmt = $das_conn->prepare("SELECT * FROM master_borrowers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get master guarantor by ID
 */
function getMasterGuarantor($id) {
    global $das_conn;
    
    $stmt = $das_conn->prepare("SELECT * FROM master_guarantors WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Create or update master borrower from borrower data
 */
function syncBorrowerToMaster($borrower_id) {
    global $das_conn;
    
    // Get borrower data
    $stmt = $das_conn->prepare("SELECT * FROM borrowers WHERE id = ?");
    $stmt->bind_param("i", $borrower_id);
    $stmt->execute();
    $borrower = $stmt->get_result()->fetch_assoc();
    
    if (!$borrower) return false;
    
    // Check if master already exists
    if ($borrower['master_borrower_id']) {
        // Update existing master
        $stmt = $das_conn->prepare("
            UPDATE master_borrowers SET
                full_name = ?, borrower_type = ?, date_of_birth = ?,
                citizenship_number = ?, pan_number = ?, company_name = ?,
                perm_province = ?, perm_district = ?, perm_ward_no = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("sssssssssi", 
            $borrower['full_name'], $borrower['borrower_type'], $borrower['date_of_birth'],
            $borrower['citizenship_number'], $borrower['pan_number'], $borrower['company_name'],
            $borrower['perm_province'], $borrower['perm_district'], $borrower['perm_ward_no'],
            $borrower['master_borrower_id']
        );
        $stmt->execute();
        return $borrower['master_borrower_id'];
    } else {
        // Create new master (simplified - add all fields in production)
        $stmt = $das_conn->prepare("
            INSERT INTO master_borrowers 
            (full_name, borrower_type, citizenship_number, pan_number, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssi", 
            $borrower['full_name'], $borrower['borrower_type'],
            $borrower['citizenship_number'], $borrower['pan_number'],
            $_SESSION['user_id']
        );
        $stmt->execute();
        $master_id = $das_conn->insert_id;
        
        // Link borrower to master
        $stmt = $das_conn->prepare("UPDATE borrowers SET master_borrower_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $master_id, $borrower_id);
        $stmt->execute();
        
        return $master_id;
    }
}

/**
 * Increment usage count for master record
 */
function incrementMasterUsage($master_id, $type = 'borrower') {
    global $das_conn;
    
    $table = $type === 'borrower' ? 'master_borrowers' : 'master_guarantors';
    $sql = "UPDATE {$table} SET usage_count = usage_count + 1 WHERE id = ?";
    $stmt = $das_conn->prepare($sql);
    $stmt->bind_param("i", $master_id);
    $stmt->execute();
}
