-- Fix for branch management views - use correct column names from users table

USE das_db;

-- Drop existing views if they exist
DROP VIEW IF EXISTS vw_user_current_branch;

DROP VIEW IF EXISTS vw_pending_assignments;

DROP VIEW IF EXISTS vw_expiring_deputations;

-- View: Current user branch assignments (FIXED)
CREATE VIEW vw_user_current_branch AS
SELECT
    u.id as user_id,
    u.full_name,
    u.email,
    u.role,
    u.primary_branch_id,
    pb.branch_name_en as primary_branch_name,
    pb.branch_name_np as primary_branch_name_np,
    u.current_branch_id,
    cb.branch_name_en as current_branch_name,
    cb.branch_name_np as current_branch_name_np,
    uba.id as assignment_id,
    uba.assignment_type,
    uba.start_date,
    uba.end_date,
    uba.status as assignment_status,
    CASE
        WHEN uba.assignment_type = 'deputation'
        AND uba.end_date >= CURDATE()
        AND uba.status = 'active' THEN 'On Deputation'
        WHEN uba.assignment_type = 'transfer'
        AND uba.status = 'active' THEN 'Transferred'
        WHEN uba.assignment_type = 'primary' THEN 'Regular'
        ELSE 'No Assignment'
    END as display_status,
    DATEDIFF(uba.end_date, CURDATE()) as days_remaining
FROM
    users u
    LEFT JOIN branch_profiles pb ON u.primary_branch_id = pb.id
    LEFT JOIN branch_profiles cb ON u.current_branch_id = cb.id
    LEFT JOIN user_branch_assignments uba ON u.id = uba.user_id
    AND uba.is_current = TRUE;

-- View: Pending transfer/deputation requests (FIXED)
CREATE VIEW vw_pending_assignments AS
SELECT
    uba.id,
    uba.assignment_type,
    uba.start_date,
    uba.end_date,
    uba.request_reason,
    uba.created_at as requested_at,
    u.full_name as user_name,
    u.email,
    pb.branch_name_en as from_branch,
    tb.branch_name_en as to_branch,
    req.full_name as requested_by_name
FROM
    user_branch_assignments uba
    JOIN users u ON uba.user_id = u.id
    JOIN branch_profiles tb ON uba.branch_id = tb.id
    LEFT JOIN branch_profiles pb ON u.primary_branch_id = pb.id
    LEFT JOIN users req ON uba.requested_by = req.id
WHERE
    uba.status = 'pending'
ORDER BY uba.created_at DESC;

-- View: Expiring deputations (FIXED)
CREATE VIEW vw_expiring_deputations AS
SELECT
    uba.id,
    uba.end_date,
    DATEDIFF(uba.end_date, CURDATE()) as days_remaining,
    u.full_name as user_name,
    u.email,
    b.branch_name_en as deputation_branch,
    pb.branch_name_en as primary_branch
FROM
    user_branch_assignments uba
    JOIN users u ON uba.user_id = u.id
    JOIN branch_profiles b ON uba.branch_id = b.id
    LEFT JOIN branch_profiles pb ON u.primary_branch_id = pb.id
WHERE
    uba.assignment_type = 'deputation'
    AND uba.status = 'active'
    AND uba.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
ORDER BY uba.end_date ASC;

SELECT 'Views fixed successfully' as status;