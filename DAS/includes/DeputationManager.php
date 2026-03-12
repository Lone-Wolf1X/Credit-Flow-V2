<?php
/**
 * Deputation Manager
 * Handles temporary user deputations to different branches
 */

class DeputationManager {
    
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get user's effective branch (considering active deputation)
     * 
     * @param int $user_id User ID
     * @return int Branch ID
     */
    public function getEffectiveBranch($user_id) {
        // Check for active deputation
        $stmt = $this->conn->prepare("
            SELECT branch_id
            FROM user_branch_assignments
            WHERE user_id = ?
            AND assignment_type = 'deputation'
            AND status = 'active'
            AND start_date <= CURDATE()
            AND (end_date IS NULL OR end_date >= CURDATE())
            ORDER BY start_date DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result) {
            return $result['branch_id'];
        }
        
        // No active deputation, return primary branch
        $stmt = $this->conn->prepare("
            SELECT primary_branch_id
            FROM users
            WHERE id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['primary_branch_id'] ?? null;
    }
    
    /**
     * Get active deputation for user
     * 
     * @param int $user_id User ID
     * @return array|null Deputation details or null
     */
    public function getActiveDeputation($user_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                uba.*,
                b.branch_name_en,
                b.branch_name_np
            FROM user_branch_assignments uba
            JOIN branch_profiles b ON uba.branch_id = b.id
            WHERE uba.user_id = ?
            AND uba.assignment_type = 'deputation'
            AND uba.status = 'active'
            AND uba.start_date <= CURDATE()
            AND (uba.end_date IS NULL OR uba.end_date >= CURDATE())
            ORDER BY uba.start_date DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Check if user has active deputation
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public function hasActiveDeputation($user_id) {
        return $this->getActiveDeputation($user_id) !== null;
    }
    
    /**
     * Request deputation
     * 
     * @param int $user_id User ID
     * @param int $branch_id Destination branch ID
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @param string $reason Reason for deputation
     * @return array Result with success status
     */
    public function requestDeputation($user_id, $branch_id, $start_date, $end_date, $reason) {
        // Validate dates
        if (strtotime($end_date) <= strtotime($start_date)) {
            return ['success' => false, 'error' => 'End date must be after start date'];
        }
        
        // Check for overlapping deputations
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count
            FROM user_branch_assignments
            WHERE user_id = ?
            AND assignment_type = 'deputation'
            AND status IN ('pending', 'active')
            AND (
                (start_date BETWEEN ? AND ?)
                OR (end_date BETWEEN ? AND ?)
                OR (start_date <= ? AND end_date >= ?)
            )
        ");
        $stmt->bind_param("issssss", $user_id, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            return ['success' => false, 'error' => 'Overlapping deputation exists for this period'];
        }
        
        // Create deputation request
        $stmt = $this->conn->prepare("
            INSERT INTO user_branch_assignments 
            (user_id, branch_id, assignment_type, start_date, end_date, status, requested_by, request_reason)
            VALUES (?, ?, 'deputation', ?, ?, 'pending', ?, ?)
        ");
        $stmt->bind_param("iissis", $user_id, $branch_id, $start_date, $end_date, $user_id, $reason);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Deputation request submitted successfully',
                'request_id' => $this->conn->insert_id
            ];
        } else {
            return ['success' => false, 'error' => 'Failed to create deputation request'];
        }
    }
    
    /**
     * Approve deputation
     * 
     * @param int $assignment_id Assignment ID
     * @param int $approved_by Admin user ID
     * @return array Result
     */
    public function approveDeputation($assignment_id, $approved_by) {
        // Call stored procedure
        $stmt = $this->conn->prepare("CALL sp_approve_assignment(?, ?)");
        $stmt->bind_param("ii", $assignment_id, $approved_by);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Deputation approved successfully'];
        } else {
            return ['success' => false, 'error' => 'Failed to approve deputation'];
        }
    }
    
    /**
     * Reject deputation
     * 
     * @param int $assignment_id Assignment ID
     * @param string $reason Rejection reason
     * @return array Result
     */
    public function rejectDeputation($assignment_id, $reason) {
        $stmt = $this->conn->prepare("
            UPDATE user_branch_assignments
            SET status = 'rejected',
                rejection_reason = ?
            WHERE id = ?
        ");
        $stmt->bind_param("si", $reason, $assignment_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Deputation rejected'];
        } else {
            return ['success' => false, 'error' => 'Failed to reject deputation'];
        }
    }
    
    /**
     * Expire deputations (called by cron job)
     * 
     * @return array Result with count of expired deputations
     */
    public function expireDeputations() {
        // Call stored procedure
        $stmt = $this->conn->query("CALL sp_expire_deputations()");
        $result = $stmt->fetch_assoc();
        
        return [
            'success' => true,
            'expired_count' => $result['expired_count'] ?? 0
        ];
    }
    
    /**
     * Get expiring deputations (next N days)
     * 
     * @param int $days Number of days to look ahead
     * @return array List of expiring deputations
     */
    public function getExpiringDeputations($days = 7) {
        $stmt = $this->conn->prepare("
            SELECT 
                uba.id,
                uba.end_date,
                DATEDIFF(uba.end_date, CURDATE()) as days_remaining,
                u.full_name as user_name,
                u.username,
                u.email,
                b.branch_name_en as deputation_branch,
                pb.branch_name_en as primary_branch
            FROM user_branch_assignments uba
            JOIN users u ON uba.user_id = u.id
            JOIN branch_profiles b ON uba.branch_id = b.id
            LEFT JOIN branch_profiles pb ON u.primary_branch_id = pb.id
            WHERE uba.assignment_type = 'deputation'
            AND uba.status = 'active'
            AND uba.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY uba.end_date ASC
        ");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get user's deputation history
     * 
     * @param int $user_id User ID
     * @return array List of past deputations
     */
    public function getDeputationHistory($user_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                uba.*,
                b.branch_name_en,
                b.branch_name_np,
                app.full_name as approved_by_name
            FROM user_branch_assignments uba
            JOIN branch_profiles b ON uba.branch_id = b.id
            LEFT JOIN users app ON uba.approved_by = app.id
            WHERE uba.user_id = ?
            AND uba.assignment_type = 'deputation'
            ORDER BY uba.created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
