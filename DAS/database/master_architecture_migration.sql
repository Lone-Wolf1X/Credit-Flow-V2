-- =====================================================
-- DAS MASTER ARCHITECTURE - DATABASE MIGRATION
-- CRITICAL: Run this FIRST before any other implementation
-- =====================================================

USE das_db;

-- =====================================================
-- STEP 1: Fix loan_details Table (CRITICAL)
-- =====================================================

-- Remove wrong foreign key constraint
ALTER TABLE loan_details 
DROP FOREIGN KEY IF EXISTS loan_details_ibfk_2;

-- Remove template_id column (should not be here)
ALTER TABLE loan_details 
DROP COLUMN IF EXISTS template_id;

-- Add correct scheme_id reference
ALTER TABLE loan_details 
ADD COLUMN IF NOT EXISTS scheme_id INT NULL COMMENT 'Links to loan_schemes table';

-- Add foreign key for scheme
ALTER TABLE loan_details 
ADD CONSTRAINT fk_loan_scheme 
FOREIGN KEY (scheme_id) REFERENCES loan_schemes(id) 
ON DELETE SET NULL;

-- =====================================================
-- STEP 2: Ensure profile_documents exists
-- =====================================================

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
    
    -- Metadata
    generated_by INT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    download_count INT DEFAULT 0,
    last_downloaded_at TIMESTAMP NULL,
    
    -- Notes
    notes TEXT NULL,
    
    FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES templates(id) ON DELETE RESTRICT,
    FOREIGN KEY (generated_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    
    INDEX idx_profile (customer_profile_id),
    INDEX idx_status (status),
    INDEX idx_generated_by (generated_by),
    INDEX idx_generated_at (generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- STEP 3: Create generated documents directory structure
-- =====================================================

-- Note: Directory creation will be done via PHP
-- Structure: DAS/generated/{year}/{month}/{customer_id}/

-- =====================================================
-- STEP 4: Add missing indexes for performance
-- =====================================================

-- Index on borrowers for faster querying
ALTER TABLE borrowers 
ADD INDEX IF NOT EXISTS idx_customer_coborrower (customer_profile_id, is_co_borrower);

-- Index on guarantors
ALTER TABLE guarantors 
ADD INDEX IF NOT EXISTS idx_customer_guarantor (customer_profile_id);

-- Index on collateral
ALTER TABLE collateral 
ADD INDEX IF NOT EXISTS idx_customer_collateral (customer_profile_id);

-- =====================================================
-- STEP 5: Create helper views for document generation
-- =====================================================

-- View for complete profile data
CREATE OR REPLACE VIEW vw_profile_complete AS
SELECT 
    cp.*,
    ls.scheme_name,
    ls.scheme_code,
    ls.template_folder_path,
    COUNT(DISTINCT b.id) as borrower_count,
    COUNT(DISTINCT g.id) as guarantor_count,
    COUNT(DISTINCT col.id) as collateral_count,
    COUNT(DISTINCT pd.id) as generated_document_count
FROM customer_profiles cp
LEFT JOIN loan_details ld ON cp.id = ld.customer_profile_id
LEFT JOIN loan_schemes ls ON ld.scheme_id = ls.id
LEFT JOIN borrowers b ON cp.id = b.customer_profile_id
LEFT JOIN guarantors g ON cp.id = g.customer_profile_id
LEFT JOIN collateral col ON cp.id = col.customer_profile_id
LEFT JOIN profile_documents pd ON cp.id = pd.customer_profile_id
GROUP BY cp.id;

-- =====================================================
-- STEP 6: Create stored procedures for document generation
-- =====================================================

DELIMITER //

-- Get templates available for a profile
DROP PROCEDURE IF EXISTS sp_get_available_templates//
CREATE PROCEDURE sp_get_available_templates(IN p_profile_id INT)
BEGIN
    SELECT t.*
    FROM templates t
    JOIN loan_details ld ON t.scheme_id = ld.scheme_id
    WHERE ld.customer_profile_id = p_profile_id
    AND t.is_active = 1
    ORDER BY t.template_name;
END//

-- Record generated document
DROP PROCEDURE IF EXISTS sp_record_generated_document//
CREATE PROCEDURE sp_record_generated_document(
    IN p_profile_id INT,
    IN p_template_id INT,
    IN p_file_path VARCHAR(500),
    IN p_file_size INT,
    IN p_user_id INT,
    OUT p_document_id INT
)
BEGIN
    DECLARE v_template_name VARCHAR(200);
    DECLARE v_template_code VARCHAR(100);
    
    -- Get template info
    SELECT template_name, template_code 
    INTO v_template_name, v_template_code
    FROM templates WHERE id = p_template_id;
    
    -- Insert record
    INSERT INTO profile_documents 
    (customer_profile_id, template_id, document_name, document_code, 
     file_path, file_size_bytes, generated_by)
    VALUES 
    (p_profile_id, p_template_id, v_template_name, v_template_code,
     p_file_path, p_file_size, p_user_id);
    
    SET p_document_id = LAST_INSERT_ID();
END//

DELIMITER ;

-- =====================================================
-- STEP 7: Update templates table structure (if needed)
-- =====================================================

-- Ensure is_active column exists
ALTER TABLE templates 
ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 
COMMENT 'Template is active and can be used';

-- Add template version tracking
ALTER TABLE templates 
ADD COLUMN IF NOT EXISTS version VARCHAR(20) DEFAULT '1.0' 
COMMENT 'Template version number';

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Check if migration was successful
SELECT 'Checking loan_details structure...' AS Status;
SHOW COLUMNS FROM loan_details LIKE 'scheme_id';

SELECT 'Checking profile_documents table...' AS Status;
SHOW TABLES LIKE 'profile_documents';

SELECT 'Checking views...' AS Status;
SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_das_db LIKE 'vw_profile%';

SELECT 'Checking stored procedures...' AS Status;
SHOW PROCEDURE STATUS WHERE Db = 'das_db' AND Name LIKE 'sp_%';

-- =====================================================
-- SUCCESS MESSAGE
-- =====================================================
SELECT '
╔═══════════════════════════════════════════════════╗
║   DATABASE MIGRATION COMPLETED SUCCESSFULLY!      ║
╠═══════════════════════════════════════════════════╣
║ ✓ loan_details.template_id REMOVED               ║
║ ✓ loan_details.scheme_id ADDED                   ║
║ ✓ profile_documents table CREATED                ║
║ ✓ Helper views CREATED                           ║
║ ✓ Stored procedures CREATED                      ║
║                                                   ║
║ Next Steps:                                       ║
║ 1. Install PHPWord (composer require...)         ║
║ 2. Create document generation logic               ║
║ 3. Create master templates                        ║
╚═══════════════════════════════════════════════════╝
' AS 'MIGRATION STATUS';
