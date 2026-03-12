-- =====================================================
-- Customer Management System Database Schema
-- =====================================================

USE das_db;

-- =====================================================
-- 1. MODIFY USERS TABLE - Add Province and SOL (if not exists)
-- =====================================================
-- Check and add province column
SET @dbname = 'das_db';

SET @tablename = 'users';

SET @columnname = 'province';

SET
    @preparedStatement = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE (table_name = @tablename)
                        AND (table_schema = @dbname)
                        AND (column_name = @columnname)
                ) > 0, "SELECT 1", CONCAT(
                    "ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " VARCHAR(100) NULL AFTER phone"
                )
            )
    );

PREPARE alterIfNotExists FROM @preparedStatement;

EXECUTE alterIfNotExists;

DEALLOCATE PREPARE alterIfNotExists;

-- Check and add sol column
SET @columnname = 'sol';

SET
    @preparedStatement = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE (table_name = @tablename)
                        AND (table_schema = @dbname)
                        AND (column_name = @columnname)
                ) > 0, "SELECT 1", CONCAT(
                    "ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " VARCHAR(50) NULL COMMENT 'Service Outlet Location' AFTER province"
                )
            )
    );

PREPARE alterIfNotExists FROM @preparedStatement;

EXECUTE alterIfNotExists;

DEALLOCATE PREPARE alterIfNotExists;

-- =====================================================
-- 2. CUSTOMER PROFILES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS customer_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id VARCHAR(50) UNIQUE NOT NULL COMMENT 'Auto-generated: YYYY + sequence',
    customer_type ENUM('Individual', 'Corporate') NOT NULL,
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(100),
    contact VARCHAR(20) NOT NULL,

-- User and Location Info
created_by INT NOT NULL,
province VARCHAR(100),
sol VARCHAR(50) COMMENT 'Service Outlet Location from user',

-- Status and Workflow
status ENUM(
    'Draft',
    'Submitted',
    'Approved',
    'Rejected'
) DEFAULT 'Draft',
submitted_at TIMESTAMP NULL,
approved_by INT NULL,
approved_at TIMESTAMP NULL,
rejected_by INT NULL,
rejected_at TIMESTAMP NULL,
rejection_reason TEXT,

-- Timestamps

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (rejected_by) REFERENCES users(id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_customer_type (customer_type),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. BORROWERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS borrowers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_profile_id INT NOT NULL,
    borrower_type ENUM('Individual', 'Corporate') NOT NULL,
    is_co_borrower TINYINT(1) DEFAULT 0 COMMENT 'For joint loans',

-- Common Fields
full_name VARCHAR(200) NOT NULL,

-- Individual Fields
date_of_birth DATE NULL,
gender ENUM('Male', 'Female', 'Other') NULL,
relationship_status ENUM(
    'Single',
    'Married',
    'Divorced',
    'Widowed'
) NULL,
citizenship_number VARCHAR(50) NULL,
id_issue_date DATE NULL,
id_issue_district VARCHAR(100) NULL,
id_issue_authority VARCHAR(200) NULL,
id_reissue_date DATE NULL,
reissue_count INT DEFAULT 0 COMMENT 'First, Second, Third reissue',
father_name VARCHAR(200) NULL,

-- Corporate Fields
company_name VARCHAR(200) NULL,
registration_no VARCHAR(100) NULL,
registration_date DATE NULL,
registration_type ENUM(
    'Proprietorship',
    'Partnership',
    'Private Limited'
) NULL,
pan_number VARCHAR(50) NULL,
pan_issue_date DATE NULL,
pan_issue_authority VARCHAR(200) NULL,
firm_registration_authority VARCHAR(200) NULL,

-- Address Fields - Permanent/Registered
perm_country VARCHAR(100) DEFAULT 'Nepal',
perm_province VARCHAR(100),
perm_district VARCHAR(100),
perm_municipality_vdc VARCHAR(100),
perm_ward_no VARCHAR(10),
perm_town_village VARCHAR(100),
perm_street_name VARCHAR(100),
perm_street_number VARCHAR(50),

-- Address Fields - Temporary/Current
temp_country VARCHAR(100) DEFAULT 'Nepal',
temp_province VARCHAR(100),
temp_district VARCHAR(100),
temp_municipality_vdc VARCHAR(100),
temp_ward_no VARCHAR(10),
temp_town_village VARCHAR(100),
temp_street_name VARCHAR(100),
temp_street_number VARCHAR(50),

-- Address Fields - Mailing (Corporate only)
mail_country VARCHAR(100) NULL,
mail_province VARCHAR(100) NULL,
mail_district VARCHAR(100) NULL,
mail_municipality_vdc VARCHAR(100) NULL,
mail_ward_no VARCHAR(10) NULL,
mail_town_village VARCHAR(100) NULL,
mail_street_name VARCHAR(100) NULL,
mail_street_number VARCHAR(50) NULL,

-- Timestamps

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles(id) ON DELETE CASCADE,
    INDEX idx_customer_profile (customer_profile_id),
    INDEX idx_borrower_type (borrower_type),
    INDEX idx_co_borrower (is_co_borrower)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. GUARANTORS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS guarantors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_profile_id INT NOT NULL,
    guarantor_type ENUM('Individual', 'Corporate') NOT NULL,

-- Common Fields
full_name VARCHAR(200) NOT NULL,

-- Individual Fields
date_of_birth DATE NULL,
gender ENUM('Male', 'Female', 'Other') NULL,
relationship_status ENUM(
    'Single',
    'Married',
    'Divorced',
    'Widowed'
) NULL,
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
registration_type ENUM(
    'Proprietorship',
    'Partnership',
    'Private Limited'
) NULL,
pan_number VARCHAR(50) NULL,
pan_issue_date DATE NULL,
pan_issue_authority VARCHAR(200) NULL,
firm_registration_authority VARCHAR(200) NULL,

-- Address Fields - Permanent/Registered
perm_country VARCHAR(100) DEFAULT 'Nepal',
perm_province VARCHAR(100),
perm_district VARCHAR(100),
perm_municipality_vdc VARCHAR(100),
perm_ward_no VARCHAR(10),
perm_town_village VARCHAR(100),
perm_street_name VARCHAR(100),
perm_street_number VARCHAR(50),

-- Address Fields - Temporary/Current
temp_country VARCHAR(100) DEFAULT 'Nepal',
temp_province VARCHAR(100),
temp_district VARCHAR(100),
temp_municipality_vdc VARCHAR(100),
temp_ward_no VARCHAR(10),
temp_town_village VARCHAR(100),
temp_street_name VARCHAR(100),
temp_street_number VARCHAR(50),

-- Address Fields - Mailing (Corporate only)
mail_country VARCHAR(100) NULL,
mail_province VARCHAR(100) NULL,
mail_district VARCHAR(100) NULL,
mail_municipality_vdc VARCHAR(100) NULL,
mail_ward_no VARCHAR(10) NULL,
mail_town_village VARCHAR(100) NULL,
mail_street_name VARCHAR(100) NULL,
mail_street_number VARCHAR(50) NULL,

-- Timestamps

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles(id) ON DELETE CASCADE,
    INDEX idx_customer_profile (customer_profile_id),
    INDEX idx_guarantor_type (guarantor_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. FAMILY DETAILS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS family_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL COMMENT 'ID from borrowers or guarantors table',
    person_type ENUM('Borrower', 'Guarantor') NOT NULL,
    name VARCHAR(200) NOT NULL,
    relation VARCHAR(100) NOT NULL COMMENT 'Father, Mother, Spouse, Son, Daughter, etc.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_person (person_id, person_type)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- =====================================================
-- 6. AUTHORIZED PERSONS TABLE (Corporate)
-- =====================================================
CREATE TABLE IF NOT EXISTS authorized_persons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    corporate_id INT NOT NULL COMMENT 'ID from borrowers or guarantors table',
    person_type ENUM('Borrower', 'Guarantor') NOT NULL,
    name VARCHAR(200) NOT NULL,
    designation VARCHAR(100),
    contact VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_corporate (corporate_id, person_type)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- =====================================================
-- 7. COLLATERAL TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS collateral (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_profile_id INT NOT NULL,
    collateral_type ENUM('Land', 'Vehicle') NOT NULL,

-- Owner Information
owner_id INT NOT NULL COMMENT 'ID from borrowers or guarantors table',
owner_type ENUM('Borrower', 'Guarantor') NOT NULL,

-- Land Fields
land_country VARCHAR(100) NULL,
land_province VARCHAR(100) NULL,
land_district VARCHAR(100) NULL,
land_municipality_vdc VARCHAR(100) NULL,
land_ward_no VARCHAR(10) NULL,
land_sheet_no VARCHAR(50) NULL,
land_kitta_no VARCHAR(50) NULL,
land_area VARCHAR(100) NULL COMMENT 'Area with unit',
land_malpot_office VARCHAR(200) NULL,

-- Vehicle Fields
vehicle_model_no VARCHAR(100) NULL,
vehicle_engine_no VARCHAR(100) NULL,
vehicle_chassis_no VARCHAR(100) NULL,
vehicle_no VARCHAR(50) NULL COMMENT 'Registration/Plate number',

-- Timestamps

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles(id) ON DELETE CASCADE,
    INDEX idx_customer_profile (customer_profile_id),
    INDEX idx_collateral_type (collateral_type),
    INDEX idx_owner (owner_id, owner_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. LIMIT DETAILS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS limit_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_profile_id INT NOT NULL,
    loan_type VARCHAR(100) NOT NULL,
    loan_purpose VARCHAR(255) NULL,
    amount DECIMAL(15,2) NOT NULL,
    tenure INT NOT NULL COMMENT 'In months',
    interest_rate DECIMAL(5,2) NOT NULL COMMENT 'Annual percentage',
    base_rate DECIMAL(5,2) NULL,
    premium DECIMAL(5,2) NULL,

-- Timestamps

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles(id) ON DELETE CASCADE,
    INDEX idx_customer_profile (customer_profile_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. LOAN DETAILS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS loan_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_profile_id INT NOT NULL,
    loan_type VARCHAR(100) NOT NULL,
    loan_scheme VARCHAR(100) NOT NULL,
    loan_approved_date DATE NOT NULL,
    remarks TEXT NULL,

-- Link to template for document generation
template_id INT NULL,

-- Timestamps

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES templates(id),
    INDEX idx_customer_profile (customer_profile_id),
    INDEX idx_loan_scheme (loan_scheme)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. STORED PROCEDURE - Generate Customer Profile ID
-- =====================================================
DELIMITER /
/

DROP PROCEDURE IF EXISTS sp_generate_customer_profile_id /
/

CREATE PROCEDURE sp_generate_customer_profile_id(OUT new_customer_id VARCHAR(50))
BEGIN
    DECLARE current_year VARCHAR(4);
    DECLARE next_number INT;
    
    -- Get current year
    SET current_year = YEAR(CURDATE());
    
    -- Get next sequence number for this year
    SELECT COALESCE(MAX(CAST(SUBSTRING(customer_id, 5) AS UNSIGNED)), 0) + 1 
    INTO next_number
    FROM customer_profiles 
    WHERE customer_id LIKE CONCAT(current_year, '%');
    
    -- Generate new customer ID: YYYY + 3-digit sequence
    SET new_customer_id = CONCAT(current_year, LPAD(next_number, 3, '0'));
END
/
/

DELIMITER;

-- =====================================================
-- 11. CREATE VIEW - Customer Profile Summary
-- =====================================================
CREATE OR REPLACE VIEW vw_customer_profile_summary AS
SELECT
    cp.id,
    cp.customer_id,
    cp.customer_type,
    cp.full_name,
    cp.email,
    cp.contact,
    cp.status,
    u.full_name as created_by_name,
    cp.province,
    cp.sol,
    cp.created_at,
    COUNT(DISTINCT b.id) as borrower_count,
    COUNT(DISTINCT g.id) as guarantor_count,
    COUNT(DISTINCT c.id) as collateral_count,
    COUNT(DISTINCT ld.id) as loan_count
FROM
    customer_profiles cp
    LEFT JOIN users u ON cp.created_by = u.id
    LEFT JOIN borrowers b ON cp.id = b.customer_profile_id
    LEFT JOIN guarantors g ON cp.id = g.customer_profile_id
    LEFT JOIN collateral c ON cp.id = c.customer_profile_id
    LEFT JOIN loan_details ld ON cp.id = ld.customer_profile_id
GROUP BY
    cp.id,
    cp.customer_id,
    cp.customer_type,
    cp.full_name,
    cp.email,
    cp.contact,
    cp.status,
    u.full_name,
    cp.province,
    cp.sol,
    cp.created_at;

-- =====================================================
-- END OF SCHEMA
-- =====================================================

SELECT 'Customer Management Schema created successfully!' AS Status;

SELECT COUNT(*) AS 'New Tables Created'
FROM information_schema.tables
WHERE
    table_schema = 'das_db'
    AND table_name IN (
        'customer_profiles',
        'borrowers',
        'guarantors',
        'family_details',
        'authorized_persons',
        'collateral',
        'limit_details',
        'loan_details'
    );