<?php
/**
 * Profile Locking Functions
 * Handles profile picking, locking, and releasing for checker workflow
 */

/**
 * Pick a profile for review
 * @param int $profile_id Customer profile ID
 * @param int $user_id User picking the profile
 * @return array Result with success/error message
 */
function pickProfile($profile_id, $user_id) {
    global $das_conn;
    
    // Call stored procedure
    $stmt = $das_conn->prepare("CALL sp_pick_profile(?, ?, @result)");
    $stmt->bind_param("ii", $profile_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Get result
    $result = $das_conn->query("SELECT @result as result")->fetch_assoc();
    
    if ($result['result'] === 'SUCCESS') {
        return ['success' => true, 'message' => 'Profile picked successfully'];
    } else if ($result['result'] === 'LOCKED') {
        // Get who picked it
        $stmt = $das_conn->prepare("
            SELECT u.full_name, cp.picked_at,
                   TIMESTAMPDIFF(MINUTE, cp.picked_at, NOW()) as minutes_ago
            FROM customer_profiles cp
            JOIN users u ON cp.picked_by = u.id
            WHERE cp.id = ?
        ");
        $stmt->bind_param("i", $profile_id);
        $stmt->execute();
        $picker = $stmt->get_result()->fetch_assoc();
        
        return [
            'success' => false, 
            'error' => 'Profile is currently being reviewed by ' . $picker['full_name'] . 
                      ' (picked ' . $picker['minutes_ago'] . ' minutes ago)'
        ];
    }
    
    return ['success' => false, 'error' => 'Unknown error occurred'];
}

/**
 * Release a picked profile
 * @param int $profile_id Customer profile ID
 * @param int $user_id User releasing the profile
 * @return array Result
 */
function releaseProfile($profile_id, $user_id) {
    global $das_conn;
    
    $stmt = $das_conn->prepare("CALL sp_release_profile(?, ?)");
    $stmt->bind_param("ii", $profile_id, $user_id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Profile released successfully'];
    }
    
    return ['success' => false, 'error' => 'Failed to release profile'];
}

/**
 * Approve a profile
 * @param int $profile_id Customer profile ID
 * @param int $user_id User approving
 * @param string $comments Approval comments
 * @return array Result
 */
function approveProfile($profile_id, $user_id, $comments = '') {
    global $das_conn;
    
    $stmt = $das_conn->prepare("CALL sp_approve_profile(?, ?, ?)");
    $stmt->bind_param("iis", $profile_id, $user_id, $comments);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Profile approved successfully'];
    }
    
    return ['success' => false, 'error' => 'Failed to approve profile'];
}

/**
 * Return profile to maker
 * @param int $profile_id Customer profile ID
 * @param int $user_id User returning
 * @param string $reason Return reason
 * @return array Result
 */
function returnProfile($profile_id, $user_id, $reason) {
    global $das_conn;
    
    if (empty($reason)) {
        return ['success' => false, 'error' => 'Return reason is required'];
    }
    
    $stmt = $das_conn->prepare("CALL sp_return_profile(?, ?, ?)");
    $stmt->bind_param("iis", $profile_id, $user_id, $reason);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Profile returned to maker'];
    }
    
    return ['success' => false, 'error' => 'Failed to return profile'];
}

/**
 * Check if profile is locked
 * @param int $profile_id Customer profile ID
 * @return array Lock status and details
 */
function isProfileLocked($profile_id) {
    global $das_conn;
    
    $stmt = $das_conn->prepare("
        SELECT cp.status, cp.picked_by, u.full_name as picked_by_name, cp.picked_at,
               TIMESTAMPDIFF(MINUTE, cp.picked_at, NOW()) as minutes_since_picked
        FROM customer_profiles cp
        LEFT JOIN users u ON cp.picked_by = u.id
        WHERE cp.id = ?
    ");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['status'] === 'Picked') {
        // Check if timeout expired
        if ($result['minutes_since_picked'] >= 30) {
            return ['locked' => false, 'expired' => true];
        }
        
        return [
            'locked' => true,
            'picked_by' => $result['picked_by'],
            'picked_by_name' => $result['picked_by_name'],
            'picked_at' => $result['picked_at'],
            'minutes_ago' => $result['minutes_since_picked']
        ];
    }
    
    return ['locked' => false];
}

/**
 * Get profiles pending review for checker
 * @param int $user_id Checker user ID
 * @return array List of profiles
 */
function getPendingProfiles($user_id = null) {
    global $das_conn;
    
    $sql = "SELECT * FROM vw_profile_list 
            WHERE status IN ('Submitted', 'Picked')";
    
    if ($user_id) {
        $sql .= " AND (picked_by IS NULL OR picked_by = ?)";
        $stmt = $das_conn->prepare($sql . " ORDER BY submitted_at ASC");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $das_conn->prepare($sql . " ORDER BY submitted_at ASC");
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Auto-release expired locks (run via cron or on page load)
 */
function autoReleaseExpiredLocks() {
    global $das_conn;
    
    $sql = "UPDATE customer_profiles 
            SET status = 'Submitted', picked_by = NULL, picked_at = NULL
            WHERE status = 'Picked' 
            AND TIMESTAMPDIFF(MINUTE, picked_at, NOW()) >= 30";
    
    $das_conn->query($sql);
    
    return $das_conn->affected_rows;
}
