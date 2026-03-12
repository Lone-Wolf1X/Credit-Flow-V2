-- =====================================================
-- DAS WORKFLOW ENHANCEMENTS - DATABASE SCHEMA
-- All phases: Status tracking, Comments, Borrower reuse, Documents
-- =====================================================

USE das_db;

-- =====================================================
-- PHASE 1: PROFILE STATUS TRACKING & COMMENTS
-- =====================================================

-- Modify customer_profiles table for status tracking
ALTER TABLE customer_profiles 
MODIFY COLUMN status ENUM('Draft', 'Submitted', 'Picked', 'Under Review', 'Approved', 'Rejected', 'Returned') DEFAULT 'Draft',
ADD COLUMN picked_by INT NULL COMMENT 'Checker who picked the profile',
ADD COLUMN picked_at TIMESTAMP NULL COMMENT 'When profile was picked',
ADD COLUMN reviewed_by INT NULL COMMENT 'Checker who reviewed',
ADD COLUMN reviewed_at TIMESTAMP NULL COMMENT 'When profile was reviewed',
ADD COLUMN return_reason TEXT NULL COMMENT 'Reason if returned to maker';

-- Add foreign keys for tracking
ALTER TABLE customer_profiles
ADD CONSTRAINT fk_picked_by FOREIGN KEY (picked_by) REFERENCES users(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL;

-- Create profile comments table
CREATE TABLE IF NOT EXISTS profile_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_profile_id INT NOT NULL,
    section VARCHAR(50) NOT NULL COMMENT 'borrower, guarantor, collateral, loan, general',
    field_name VARCHAR(100) NULL COMMENT 'Specific field name if applicable',
    comment_text TEXT NOT NULL,
    comment_type ENUM('Info', 'Question', 'Issue', 'Approval', 'Rejection') DEFAULT 'Info',
    
    -- User tracking
    commented_by INT NOT NULL,
    commented_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Response tracking
    parent_comment_id INT NULL COMMENT 'For replies/responses',
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_by INT NULL,
    resolved_at TIMESTAMP NULL,
    
    FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (commented_by) REFERENCES users(id),
    FOREIGN KEY (parent_comment_id) REFERENCES profile_comments(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id),
    
    INDEX idx_profile (customer_profile_id),
    INDEX idx_section (section),
    INDEX idx_commented_by (commented_by),
    INDEX idx_resolved (is_resolved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PHASE 2: BORROWER/GUARANTOR REUSE SYSTEM
-- =====================================================

-- Create master borrowers table
CREATE TABLE IF NOT EXISTS master_borrowers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    borrower_type ENUM('Individual', 'Corporate') NOT NULL,
    
    -- Common Fields
    full_name VARCHAR(200) NOT NULL,
    
    -- Individual Fields
    date_of_birth DATE NULL,
    gender ENUM('Male', 'Female', 'Other') NULL,
    relationship_status ENUM('Single', 'Married', 'Divorced', 'Widowed') NULL,
    citizenship_number VARCHAR(50) NULL,
    id_issue_date DATE NULL,
    id_issue_district VARCHAR(100) NULL,
    id_issue_authority VARCHAR(200) NULL,
    id_reissue_date DATE NULL,
    reissue_count INT DEFAULT 0,
    father_name VARCHAR(200) NULL,
    
    -- Corporate Fields
    company_name VARCHAR(200) NULL,
    registration_no VARCHAR(100) NULL,
    registration_date DATE NULL,
    registration_type ENUM('Proprietorship', 'Partnership', 'Private Limited') NULL,
    pan_number VARCHAR(50) NULL,
    pan_issue_date DATE NULL,
    pan_issue_authority VARCHAR(200) NULL,
    firm_registration_authority VARCHAR(200) NULL,
    
    -- Address Fields - Permanent
    perm_country VARCHAR(100) DEFAULT 'Nepal',
    perm_province VARCHAR(100),
    perm_district VARCHAR(100),
    perm_municipality_vdc VARCHAR(100),
    perm_ward_no VARCHAR(10),
    perm_town_village VARCHAR(100),
    perm_street_name VARCHAR(100),
    perm_street_number VARCHAR(50),
    
    -- Address Fields - Temporary
    temp_country VARCHAR(100) DEFAULT 'Nepal',
    temp_province VARCHAR(100),
    temp_district VARCHAR(100),
    temp_municipality_vdc VARCHAR(100),
    temp_ward_no VARCHAR(10),
    temp_town_village VARCHAR(100),
    temp_street_name VARCHAR(100),
    temp_street_number VARCHAR(50),
    
    -- Address Fields - Mailing (Corporate)
    mail_country VARCHAR(100) NULL,
    mail_province VARCHAR(100) NULL,
    mail_district VARCHAR(100) NULL,
    mail_municipality_vdc VARCHAR(100) NULL,
    mail_ward_no VARCHAR(10) NULL,
    mail_town_village VARCHAR(100) NULL,
    mail_street_name VARCHAR(100) NULL,
    mail_street_number VARCHAR(50) NULL,
    
    -- Tracking
    usage_count INT DEFAULT 0 COMMENT 'How many times this record is used',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    
    INDEX idx_citizenship (citizenship_number),
    INDEX idx_pan (pan_number),
    INDEX idx_name (full_name),
    INDEX idx_type (borrower_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create master guarantors table
CREATE TABLE IF NOT EXISTS master_guarantors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guarantor_type ENUM('Individual', 'Corporate') NOT NULL,
    
    -- Common Fields
    full_name VARCHAR(200) NOT NULL,
    
    -- Individual Fields
    date_of_birth DATE NULL,
    gender ENUM('Male', 'Female', 'Other') NULL,
    relationship_status ENUM('Single', 'Married', 'Divorced', 'Widowed') NULL,
    citizenship_number VARCHAR(50) NULL,
    id_issue_date DATE NULL,
    id_issue_district VARCHAR(100) NULL,
    id_issue_authority VARCHAR(200) NULL,
    id_reissue_date DATE NULL,
    reissue_count INT DEFAULT 0,
    father_name VARCHAR(200) NULL,
    
    -- Corporate Fields
    company_name VARCHAR(200) NULL,
    registration_no VARCHAR(100) NULL,
    registration_date DATE NULL,
    registration_type ENUM('Proprietorship', 'Partnership', 'Private Limited') NULL,
    pan_number VARCHAR(50) NULL,
    pan_issue_date DATE NULL,
    pan_issue_authority VARCHAR(200) NULL,
    firm_registration_authority VARCHAR(200) NULL,
    
    -- Address Fields - Permanent
    perm_country VARCHAR(100) DEFAULT 'Nepal',
    perm_province VARCHAR(100),
    perm_district VARCHAR(100),
    perm_municipality_vdc VARCHAR(100),
    perm_ward_no VARCHAR(10),
    perm_town_village VARCHAR(100),
    perm_street_name VARCHAR(100),
    perm_street_number VARCHAR(50),
    
    -- Address Fields - Temporary
    temp_country VARCHAR(100) DEFAULT 'Nepal',
    temp_province VARCHAR(100),
    temp_district VARCHAR(100),
    temp_municipality_vdc VARCHAR(100),
    temp_ward_no VARCHAR(10),
    temp_town_village VARCHAR(100),
    temp_street_name VARCHAR(100),
    temp_street_number VARCHAR(50),
    
    -- Address Fields - Mailing (Corporate)
    mail_country VARCHAR(100) NULL,
    mail_province VARCHAR(100) NULL,
    mail_district VARCHAR(100) NULL,
    mail_municipality_vdc VARCHAR(100) NULL,
    mail_ward_no VARCHAR(10) NULL,
    mail_town_village VARCHAR(100) NULL,
    mail_street_name VARCHAR(100) NULL,
    mail_street_number VARCHAR(50) NULL,
    
    -- Tracking
    usage_count INT DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    
    INDEX idx_citizenship (citizenship_number),
    INDEX idx_pan (pan_number),
    INDEX idx_name (full_name),
    INDEX idx_type (guarantor_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modify borrowers table to link to master
ALTER TABLE borrowers 
ADD COLUMN master_borrower_id INT NULL COMMENT 'Link to master borrower record',
ADD CONSTRAINT fk_master_borrower FOREIGN KEY (master_borrower_id) REFERENCES master_borrowers(id) ON DELETE SET NULL;

-- Modify guarantors table to link to master
ALTER TABLE guarantors 
ADD COLUMN master_guarantor_id INT NULL COMMENT 'Link to master guarantor record',
ADD CONSTRAINT fk_master_guarantor FOREIGN KEY (master_guarantor_id) REFERENCES master_guarantors(id) ON DELETE SET NULL;

-- =====================================================
-- PHASE 3: DOCUMENT MANAGEMENT
-- =====================================================

-- Create profile documents table
CREATE TABLE IF NOT EXISTS profile_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_profile_id INT NOT NULL,
    template_id INT NOT NULL,
    
    -- Document info
    document_name VARCHAR(200) NOT NULL,
    document_code VARCHAR(100),
    file_path VARCHAR(500) NOT NULL,
    file_size_bytes INT,
    
    -- Status tracking
    status ENUM('Draft', 'Generated', 'Approved', 'Sent', 'Archived') DEFAULT 'Generated',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    sent_to VARCHAR(200) NULL COMMENT 'Email or recipient',
    sent_at TIMESTAMP NULL,
    
    -- Metadata
    generated_by INT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    download_count INT DEFAULT 0,
    last_downloaded_at TIMESTAMP NULL,
    
    -- Notes
    notes TEXT NULL,
    
    FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES templates(id),
    FOREIGN KEY (generated_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    
    INDEX idx_profile (customer_profile_id),
    INDEX idx_status (status),
    INDEX idx_generated_by (generated_by),
    INDEX idx_generated_at (generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create document download log
CREATE TABLE IF NOT EXISTS document_download_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profile_document_id INT NOT NULL,
    downloaded_by INT NOT NULL,
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    
    FOREIGN KEY (profile_document_id) REFERENCES profile_documents(id) ON DELETE CASCADE,
    FOREIGN KEY (downloaded_by) REFERENCES users(id),
    
    INDEX idx_document (profile_document_id),
    INDEX idx_user (downloaded_by),
    INDEX idx_date (downloaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

DELIMITER //

-- Procedure to pick a profile (with locking check)
DROP PROCEDURE IF EXISTS sp_pick_profile//
CREATE PROCEDURE sp_pick_profile(
    IN p_profile_id INT,
    IN p_user_id INT,
    OUT p_result VARCHAR(100)
)
BEGIN
    DECLARE v_current_status VARCHAR(50);
    DECLARE v_picked_by INT;
    DECLARE v_picked_at TIMESTAMP;
    DECLARE v_minutes_diff INT;
    
    -- Get current profile status
    SELECT status, picked_by, picked_at 
    INTO v_current_status, v_picked_by, v_picked_at
    FROM customer_profiles 
    WHERE id = p_profile_id;
    
    -- Check if already picked by someone else
    IF v_current_status = 'Picked' AND v_picked_by != p_user_id THEN
        -- Calculate time difference in minutes
        SET v_minutes_diff = TIMESTAMPDIFF(MINUTE, v_picked_at, NOW());
        
        -- If less than 30 minutes, profile is locked
        IF v_minutes_diff < 30 THEN
            SET p_result = 'LOCKED';
        ELSE
            -- Timeout expired, can pick
            UPDATE customer_profiles 
            SET status = 'Picked', 
                picked_by = p_user_id, 
                picked_at = NOW() 
            WHERE id = p_profile_id;
            SET p_result = 'SUCCESS';
        END IF;
    ELSE
        -- Profile is available, pick it
        UPDATE customer_profiles 
        SET status = 'Picked', 
            picked_by = p_user_id, 
            picked_at = NOW() 
        WHERE id = p_profile_id;
        SET p_result = 'SUCCESS';
    END IF;
END//

-- Procedure to release a profile
DROP PROCEDURE IF EXISTS sp_release_profile//
CREATE PROCEDURE sp_release_profile(
    IN p_profile_id INT,
    IN p_user_id INT
)
BEGIN
    UPDATE customer_profiles 
    SET status = 'Submitted', 
        picked_by = NULL, 
        picked_at = NULL 
    WHERE id = p_profile_id 
    AND picked_by = p_user_id;
END//

-- Procedure to approve profile
DROP PROCEDURE IF EXISTS sp_approve_profile//
CREATE PROCEDURE sp_approve_profile(
    IN p_profile_id INT,
    IN p_user_id INT,
    IN p_comments TEXT
)
BEGIN
    -- Update profile status
    UPDATE customer_profiles 
    SET status = 'Approved', 
        reviewed_by = p_user_id, 
        reviewed_at = NOW(),
        approved_by = p_user_id,
        approved_at = NOW()
    WHERE id = p_profile_id;
    
    -- Add approval comment
    IF p_comments IS NOT NULL AND p_comments != '' THEN
        INSERT INTO profile_comments (customer_profile_id, section, comment_text, comment_type, commented_by)
        VALUES (p_profile_id, 'general', p_comments, 'Approval', p_user_id);
    END IF;
END//

-- Procedure to return profile to maker
DROP PROCEDURE IF EXISTS sp_return_profile//
CREATE PROCEDURE sp_return_profile(
    IN p_profile_id INT,
    IN p_user_id INT,
    IN p_reason TEXT
)
BEGIN
    -- Update profile status
    UPDATE customer_profiles 
    SET status = 'Returned', 
        reviewed_by = p_user_id, 
        reviewed_at = NOW(),
        return_reason = p_reason,
        picked_by = NULL,
        picked_at = NULL
    WHERE id = p_profile_id;
    
    -- Add return comment
    INSERT INTO profile_comments (customer_profile_id, section, comment_text, comment_type, commented_by)
    VALUES (p_profile_id, 'general', p_reason, 'Rejection', p_user_id);
END//

DELIMITER ;

-- =====================================================
-- VIEWS FOR EASY QUERYING
-- =====================================================

-- View for profile list with picker info
CREATE OR REPLACE VIEW vw_profile_list AS
SELECT 
    cp.*,
    creator.full_name as created_by_name,
    picker.full_name as picked_by_name,
    reviewer.full_name as reviewed_by_name,
    TIMESTAMPDIFF(MINUTE, cp.picked_at, NOW()) as minutes_since_picked,
    (SELECT COUNT(*) FROM profile_comments WHERE customer_profile_id = cp.id) as comment_count,
    (SELECT COUNT(*) FROM profile_documents WHERE customer_profile_id = cp.id) as document_count
FROM customer_profiles cp
LEFT JOIN users creator ON cp.created_by = creator.id
LEFT JOIN users picker ON cp.picked_by = picker.id
LEFT JOIN users reviewer ON cp.reviewed_by = reviewer.id;

-- =====================================================
-- SUCCESS MESSAGE
-- =====================================================
SELECT 'DAS Workflow Enhancement Schema Created Successfully!' AS Status;
SELECT 'Tables Created:' AS Info;
SELECT 'profile_comments, master_borrowers, master_guarantors, profile_documents, document_download_log' AS Tables;
SELECT 'Stored Procedures Created:' AS Info;
SELECT 'sp_pick_profile, sp_release_profile, sp_approve_profile, sp_return_profile' AS Procedures;
