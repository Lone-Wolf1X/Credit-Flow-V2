<?php
/**
 * Comment Functions
 * Handles profile comments and review feedback
 */

/**
 * Add a workflow comment to a profile (Submission, Approval, Rejection, Return)
 * @param int $profile_id Customer profile ID
 * @param int $user_id User adding comment
 * @param string $comment_text Comment text
 * @param string $stage Stage (Submission, Approval, Rejection, Return)
 * @return bool Success
 */
function addProfileComment($profile_id, $user_id, $comment_text, $stage = 'General') {
    global $das_conn, $conn;
    
    // Use das_conn if available, otherwise use conn
    $db = isset($das_conn) ? $das_conn : $conn;
    
    if (!$db || $db->connect_error) {
        error_log("addProfileComment: Database connection failed");
        return false;
    }
    
    if (empty($comment_text)) {
        $comment_text = "No comments provided";
    }
    
    // Use profile_comments table with correct column names
    $stmt = $db->prepare("
        INSERT INTO profile_comments 
        (customer_profile_id, section, comment_text, comment_type, commented_by, commented_at)
        VALUES (?, 'workflow', ?, ?, ?, NOW())
    ");
    
    if (!$stmt) {
        error_log("addProfileComment: Prepare failed - " . $db->error);
        return false;
    }
    
    $stmt->bind_param("issi", $profile_id, $comment_text, $stage, $user_id);
    $result = $stmt->execute();
    
    if (!$result) {
        error_log("addProfileComment: Execute failed - " . $stmt->error);
    }
    
    return $result;
}

/**
 * Add a comment to a profile
 * @param int $profile_id Customer profile ID
 * @param string $section Section (borrower, guarantor, collateral, loan, general)
 * @param string $comment_text Comment text
 * @param int $user_id User adding comment
 * @param string $field_name Optional specific field name
 * @param string $comment_type Type (Info, Question, Issue, Approval, Rejection)
 * @return array Result
 */
function addComment($profile_id, $section, $comment_text, $user_id, $field_name = null, $comment_type = 'Info') {
    global $conn, $das_conn;
    $db = isset($das_conn) ? $das_conn : $conn;
    
    if (empty($comment_text)) {
        return ['success' => false, 'error' => 'Comment text is required'];
    }
    
    $stmt = $db->prepare("
        INSERT INTO profile_comments 
        (customer_profile_id, section, field_name, comment_text, comment_type, commented_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issssi", $profile_id, $section, $field_name, $comment_text, $comment_type, $user_id);
    
    if ($stmt->execute()) {
        return [
            'success' => true, 
            'message' => 'Comment added successfully',
            'comment_id' => $db->insert_id
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to add comment'];
}

/**
 * Reply to a comment
 * @param int $parent_comment_id Parent comment ID
 * @param string $comment_text Reply text
 * @param int $user_id User replying
 * @return array Result
 */
function replyToComment($parent_comment_id, $comment_text, $user_id) {
    global $conn, $das_conn;
    $db = isset($das_conn) ? $das_conn : $conn;
    
    // Get parent comment details
    $stmt = $db->prepare("SELECT customer_profile_id, section FROM profile_comments WHERE id = ?");
    $stmt->bind_param("i", $parent_comment_id);
    $stmt->execute();
    $parent = $stmt->get_result()->fetch_assoc();
    
    if (!$parent) {
        return ['success' => false, 'error' => 'Parent comment not found'];
    }
    
    $stmt = $db->prepare("
        INSERT INTO profile_comments 
        (customer_profile_id, section, comment_text, commented_by, parent_comment_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issii", $parent['customer_profile_id'], $parent['section'], 
                      $comment_text, $user_id, $parent_comment_id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Reply added successfully'];
    }
    
    return ['success' => false, 'error' => 'Failed to add reply'];
}

/**
 * Get all comments for a profile
 * @param int $profile_id Customer profile ID
 * @param string $section Optional filter by section
 * @return array Comments with user details
 */
function getProfileComments($profile_id, $section = null) {
    global $conn, $das_conn;
    $db = isset($das_conn) ? $das_conn : $conn;
    
    $sql = "SELECT pc.*, u.full_name as commenter_name, u.role as commenter_role,
                   parent.comment_text as parent_comment_text,
                   parent_user.full_name as parent_commenter_name
            FROM profile_comments pc
            JOIN users u ON pc.commented_by = u.id
            LEFT JOIN profile_comments parent ON pc.parent_comment_id = parent.id
            LEFT JOIN users parent_user ON parent.commented_by = parent_user.id
            WHERE pc.customer_profile_id = ?";
    
    if ($section) {
        $sql .= " AND pc.section = ?";
        $stmt = $db->prepare($sql . " ORDER BY pc.commented_at DESC");
        $stmt->bind_param("is", $profile_id, $section);
    } else {
        $stmt = $db->prepare($sql . " ORDER BY pc.commented_at DESC");
        $stmt->bind_param("i", $profile_id);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Resolve a comment
 * @param int $comment_id Comment ID
 * @param int $user_id User resolving
 * @return array Result
 */
function resolveComment($comment_id, $user_id) {
    global $conn, $das_conn;
    $db = isset($das_conn) ? $das_conn : $conn;
    
    $stmt = $db->prepare("
        UPDATE profile_comments 
        SET is_resolved = 1, resolved_by = ?, resolved_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $user_id, $comment_id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Comment resolved'];
    }
    
    return ['success' => false, 'error' => 'Failed to resolve comment'];
}

/**
 * Get comment count by section
 * @param int $profile_id Customer profile ID
 * @return array Section-wise comment counts
 */
function getCommentCountBySection($profile_id) {
    global $conn, $das_conn;
    $db = isset($das_conn) ? $das_conn : $conn;
    
    $stmt = $db->prepare("
        SELECT section, COUNT(*) as count
        FROM profile_comments
        WHERE customer_profile_id = ?
        GROUP BY section
    ");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $counts = [];
    foreach ($results as $row) {
        $counts[$row['section']] = $row['count'];
    }
    
    return $counts;
}

/**
 * Get unresolved issues count
 * @param int $profile_id Customer profile ID
 * @return int Count of unresolved issues
 */
function getUnresolvedIssuesCount($profile_id) {
    global $conn, $das_conn;
    $db = isset($das_conn) ? $das_conn : $conn;
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM profile_comments
        WHERE customer_profile_id = ?
        AND comment_type = 'Issue'
        AND is_resolved = 0
    ");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['count'];
}

/**
 * Delete a comment (only by commenter or admin)
 * @param int $comment_id Comment ID
 * @param int $user_id User attempting to delete
 * @return array Result
 */
function deleteComment($comment_id, $user_id) {
    global $conn, $das_conn;
    $db = isset($das_conn) ? $das_conn : $conn;
    
    // Check if user owns the comment or is admin
    $stmt = $db->prepare("
        SELECT pc.commented_by, u.role
        FROM profile_comments pc
        JOIN users u ON u.id = ?
        WHERE pc.id = ?
    ");
    $stmt->bind_param("ii", $user_id, $comment_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        return ['success' => false, 'error' => 'Comment not found'];
    }
    
    if ($result['commented_by'] != $user_id && $result['role'] != 'Admin') {
        return ['success' => false, 'error' => 'You can only delete your own comments'];
    }
    
    $stmt = $db->prepare("DELETE FROM profile_comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Comment deleted'];
    }
    
    return ['success' => false, 'error' => 'Failed to delete comment'];
}
?>
