-- =====================================================
-- Branch Management System - Database Migration
-- =====================================================
-- This script creates all necessary tables for branch management,
-- user assignments, transfers, and deputations
-- =====================================================

USE das_db;

-- =====================================================
-- 1. CREATE branch_profiles TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS branch_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    branch_code VARCHAR(20) UNIQUE NOT NULL COMMENT 'Unique branch/SOL code',
    branch_name_en VARCHAR(255) NOT NULL COMMENT 'Branch name in English',
    branch_name_np VARCHAR(255) NOT NULL COMMENT 'Branch name in Nepali',

-- Address Details (Nepali)
province_en VARCHAR(100) COMMENT 'Province in English',
province_np VARCHAR(100) COMMENT 'Province in Nepali',
district_en VARCHAR(100) COMMENT 'District in English',
district_np VARCHAR(100) COMMENT 'District in Nepali',
municipality_en VARCHAR(100) COMMENT 'Municipality/Local body in English',
municipality_np VARCHAR(100) COMMENT 'Municipality/Local body in Nepali',
ward_no VARCHAR(10) COMMENT 'Ward number',
tole_location_en VARCHAR(255) COMMENT 'Tole/Location in English',
tole_location_np VARCHAR(255) COMMENT 'Tole/Location in Nepali',

-- Contact Details
phone VARCHAR(50) COMMENT 'Branch phone number',
email VARCHAR(100) COMMENT 'Branch email',

-- Branch Type
branch_type ENUM(
    'head_office',
    'regional_office',
    'branch',
    'sub_branch'
) DEFAULT 'branch',
is_head_office BOOLEAN DEFAULT FALSE COMMENT 'Is this the head office?',
is_active BOOLEAN DEFAULT TRUE COMMENT 'Is branch active?',

-- Metadata
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT COMMENT 'User who created this record',
    updated_by INT COMMENT 'User who last updated this record',
    
    INDEX idx_branch_code (branch_code),
    INDEX idx_active (is_active),
    INDEX idx_type (branch_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Branch profiles with Nepali address details';

-- =====================================================
-- 2. CREATE user_branch_assignments TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS user_branch_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL COMMENT 'User being assigned',
    branch_id INT NOT NULL COMMENT 'Branch assigned to',
    assignment_type ENUM('primary', 'transfer', 'deputation') NOT NULL COMMENT 'Type of assignment',

-- Assignment Period
start_date DATE NOT NULL COMMENT 'Assignment start date',
end_date DATE NULL COMMENT 'Assignment end date (NULL for permanent)',

-- Status
status ENUM(
    'pending',
    'active',
    'expired',
    'cancelled',
    'rejected'
) DEFAULT 'pending',
is_current BOOLEAN DEFAULT FALSE COMMENT 'Is this the current active assignment?',

-- Request & Approval
requested_by INT COMMENT 'User who requested this assignment',
request_reason TEXT COMMENT 'Reason for transfer/deputation',
approved_by INT COMMENT 'Admin who approved',
approved_at TIMESTAMP NULL COMMENT 'When it was approved',
rejection_reason TEXT COMMENT 'Reason for rejection if rejected',

-- Metadata
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branch_profiles(id) ON DELETE RESTRICT,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_user_current (user_id, is_current),
    INDEX idx_user_status (user_id, status),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_type (assignment_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User branch assignments including transfers and deputations';

-- =====================================================
-- 3. UPDATE users TABLE
-- =====================================================

-- Add branch-related columns to users table
ALTER TABLE users
ADD COLUMN IF NOT EXISTS primary_branch_id INT COMMENT 'User primary/home branch',
ADD COLUMN IF NOT EXISTS current_branch_id INT COMMENT 'User current working branch (may differ due to deputation)',
ADD CONSTRAINT fk_users_primary_branch FOREIGN KEY (primary_branch_id) REFERENCES branch_profiles (id) ON DELETE SET NULL,
ADD CONSTRAINT fk_users_current_branch FOREIGN KEY (current_branch_id) REFERENCES branch_profiles (id) ON DELETE SET NULL,
ADD INDEX idx_primary_branch (primary_branch_id),
ADD INDEX idx_current_branch (current_branch_id);

-- =====================================================
-- 4. CREATE VIEWS FOR EASY QUERYING
-- =====================================================

-- View: Current user branch assignments
CREATE OR REPLACE VIEW vw_user_current_branch AS
SELECT
    u.id as user_id,
    u.full_name,
    u.username,
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

-- View: Pending transfer/deputation requests
CREATE OR REPLACE VIEW vw_pending_assignments AS
SELECT
    uba.id,
    uba.assignment_type,
    uba.start_date,
    uba.end_date,
    uba.request_reason,
    uba.created_at as requested_at,
    u.full_name as user_name,
    u.username,
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

-- View: Expiring deputations (next 7 days)
CREATE OR REPLACE VIEW vw_expiring_deputations AS
SELECT
    uba.id,
    uba.end_date,
    DATEDIFF(uba.end_date, CURDATE()) as days_remaining,
    u.full_name as user_name,
    u.username,
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

-- =====================================================
-- 5. INSERT DEFAULT HEAD OFFICE BRANCH
-- =====================================================

INSERT INTO
    branch_profiles (
        branch_code,
        branch_name_en,
        branch_name_np,
        province_en,
        province_np,
        district_en,
        district_np,
        municipality_en,
        municipality_np,
        ward_no,
        tole_location_en,
        tole_location_np,
        phone,
        email,
        branch_type,
        is_head_office,
        is_active
    )
VALUES (
        'HO001',
        'Head Office',
        'प्रधान कार्यालय',
        'Bagmati Province',
        'बागमती प्रदेश',
        'Kathmandu',
        'काठमाडौं',
        'Kathmandu Metropolitan City',
        'काठमाडौं महानगरपालिका',
        '1',
        'New Baneshwor',
        'नयाँ बानेश्वर',
        '01-4123456',
        'headoffice@bank.com',
        'head_office',
        TRUE,
        TRUE
    )
ON DUPLICATE KEY UPDATE
    branch_code = branch_code;

-- =====================================================
-- 6. CREATE STORED PROCEDURES
-- =====================================================

-- Procedure: Get user's effective branch (considering deputation)
DELIMITER $$

CREATE PROCEDURE sp_get_effective_branch(IN p_user_id INT)
BEGIN
    -- Check for active deputation first
    SELECT branch_id
    FROM user_branch_assignments
    WHERE user_id = p_user_id
    AND assignment_type = 'deputation'
    AND status = 'active'
    AND start_date <= CURDATE()
    AND (end_date IS NULL OR end_date >= CURDATE())
    ORDER BY start_date DESC
    LIMIT 1;
    
    -- If no active deputation, return primary branch
    IF ROW_COUNT() = 0 THEN
        SELECT primary_branch_id as branch_id
        FROM users
        WHERE id = p_user_id;
    END IF;
END$$

-- Procedure: Expire deputations
CREATE PROCEDURE sp_expire_deputations()
BEGIN
    -- Update expired deputations
    UPDATE user_branch_assignments
    SET status = 'expired',
        is_current = FALSE
    WHERE assignment_type = 'deputation'
    AND status = 'active'
    AND end_date < CURDATE();
    
    -- Update users' current_branch_id back to primary
    UPDATE users u
    SET current_branch_id = primary_branch_id
    WHERE id IN (
        SELECT user_id 
        FROM user_branch_assignments
        WHERE assignment_type = 'deputation'
        AND status = 'expired'
        AND is_current = FALSE
    );
    
    SELECT ROW_COUNT() as expired_count;
END$$

-- Procedure: Approve transfer/deputation
CREATE PROCEDURE sp_approve_assignment(
    IN p_assignment_id INT,
    IN p_approved_by INT
)
BEGIN
    DECLARE v_user_id INT;
    DECLARE v_branch_id INT;
    DECLARE v_assignment_type VARCHAR(20);
    
    -- Get assignment details
    SELECT user_id, branch_id, assignment_type
    INTO v_user_id, v_branch_id, v_assignment_type
    FROM user_branch_assignments
    WHERE id = p_assignment_id;
    
    -- Update assignment status
    UPDATE user_branch_assignments
    SET status = 'active',
        is_current = TRUE,
        approved_by = p_approved_by,
        approved_at = NOW()
    WHERE id = p_assignment_id;
    
    -- Mark previous assignments as not current
    UPDATE user_branch_assignments
    SET is_current = FALSE
    WHERE user_id = v_user_id
    AND id != p_assignment_id;
    
    -- Update user's current branch
    UPDATE users
    SET current_branch_id = v_branch_id
    WHERE id = v_user_id;
    
    -- If transfer, also update primary branch
    IF v_assignment_type = 'transfer' THEN
        UPDATE users
        SET primary_branch_id = v_branch_id
        WHERE id = v_user_id;
    END IF;
    
    SELECT 'Assignment approved successfully' as message;
END$$

DELIMITER;

-- =====================================================
-- 7. CREATE TRIGGERS
-- =====================================================

-- Trigger: Auto-update user's current_branch_id on assignment approval
DELIMITER $$

CREATE TRIGGER tr_update_user_branch_after_approval
AFTER UPDATE ON user_branch_assignments
FOR EACH ROW
BEGIN
    IF NEW.status = 'active' AND OLD.status != 'active' THEN
        UPDATE users
        SET current_branch_id = NEW.branch_id
        WHERE id = NEW.user_id;
    END IF;
END$$

DELIMITER;

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================

SELECT 'Branch management database schema created successfully!' as status;