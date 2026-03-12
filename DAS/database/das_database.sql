-- =====================================================
-- DAS (Document Automation System) Database Setup
-- =====================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS das_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE das_db;

-- =====================================================
-- 1. USERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    designation VARCHAR(100) DEFAULT 'Staff',
    role ENUM('Maker', 'Checker', 'Admin') NOT NULL DEFAULT 'Maker',
    email VARCHAR(100),
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    created_by INT NULL,
    INDEX idx_staff_id (staff_id),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. CUSTOMERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(200) NOT NULL,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),
    father_name VARCHAR(200),
    spouse_name VARCHAR(200),
    
    -- Contact Information
    mobile_number VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    alternate_contact VARCHAR(20),
    
    -- Address
    permanent_province VARCHAR(50),
    permanent_district VARCHAR(50),
    permanent_municipality VARCHAR(100),
    permanent_ward VARCHAR(10),
    permanent_tole VARCHAR(100),
    temporary_province VARCHAR(50),
    temporary_district VARCHAR(50),
    temporary_municipality VARCHAR(100),
    temporary_ward VARCHAR(10),
    temporary_tole VARCHAR(100),
    
    -- Identification
    citizenship_number VARCHAR(50),
    citizenship_issue_date DATE,
    citizenship_issue_district VARCHAR(50),
    pan_number VARCHAR(20),
    passport_number VARCHAR(50),
    
    -- Financial Information
    occupation VARCHAR(100),
    employer_name VARCHAR(200),
    monthly_income DECIMAL(15,2),
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    
    -- Loan Information
    loan_type VARCHAR(100),
    loan_amount DECIMAL(15,2),
    loan_purpose TEXT,
    loan_tenure INT,
    
    -- System Fields
    status ENUM('Draft', 'Active', 'Inactive') DEFAULT 'Draft',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. TEMPLATES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(200) NOT NULL,
    template_code VARCHAR(50) UNIQUE NOT NULL,
    category VARCHAR(100),
    description TEXT,
    file_path VARCHAR(500),
    file_type ENUM('HTML', 'DOCX', 'PDF') DEFAULT 'HTML',
    placeholders JSON,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_template_code (template_code),
    INDEX idx_category (category),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. GENERATED DOCUMENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS generated_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    template_id INT NOT NULL,
    document_name VARCHAR(200) NOT NULL,
    file_path VARCHAR(500),
    file_type VARCHAR(10),
    status ENUM('Draft', 'Pending Approval', 'Approved', 'Rejected') DEFAULT 'Draft',
    generated_by INT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES templates(id),
    FOREIGN KEY (generated_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    INDEX idx_document_number (document_number),
    INDEX idx_customer (customer_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. DOCUMENT COMMENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS document_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    user_id INT NOT NULL,
    action ENUM('Submitted', 'Approved', 'Rejected', 'Returned', 'Comment') NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (document_id) REFERENCES generated_documents(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_document (document_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. AUDIT LOG TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. SYSTEM SETTINGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (updated_by) REFERENCES users(id),
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT USERS (Copied from EDMS & Credit Flow)
-- Password for all users is their staff_id
-- =====================================================

-- Insert users with hashed passwords (password = staff_id)
-- Using bcrypt hash for '100', '101', '102', '103', '104'
INSERT INTO users (staff_id, password, full_name, designation, role, email, is_active) VALUES
-- Admin Users
('104', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ramesh Poudel', 'System Administrator', 'Admin', 'admin@sbi.com', 1),

-- Maker Users
('100', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rajesh Kumar', 'Branch Officer', 'Maker', 'rajesh@sbi.com', 1),

-- Checker Users  
('101', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sita Sharma', 'Branch Manager', 'Checker', 'sita@sbi.com', 1),
('103', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Priya Adhikari', 'Legal Officer', 'Checker', 'priya@sbi.com', 1),

-- Additional Maker
('102', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Amit Thapa', 'Credit Officer', 'Maker', 'amit@sbi.com', 1);

-- =====================================================
-- INSERT DEFAULT SYSTEM SETTINGS
-- =====================================================
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('customer_id_prefix', 'CUST', 'Prefix for auto-generated customer IDs'),
('document_number_prefix', 'DOC', 'Prefix for auto-generated document numbers'),
('max_file_size', '10485760', 'Maximum file upload size in bytes (10MB)'),
('allowed_file_types', 'pdf,doc,docx,jpg,png', 'Allowed file extensions for upload'),
('system_email', 'das@sbi.com', 'System email address'),
('company_name', 'State Bank of India', 'Company name for documents'),
('company_address', 'Kathmandu, Nepal', 'Company address for documents');

-- =====================================================
-- CREATE VIEWS FOR REPORTING
-- =====================================================

-- View: User Statistics
CREATE OR REPLACE VIEW vw_user_statistics AS
SELECT 
    u.id,
    u.staff_id,
    u.full_name,
    u.role,
    COUNT(DISTINCT c.id) as total_customers,
    COUNT(DISTINCT gd.id) as total_documents,
    MAX(u.last_login) as last_login
FROM users u
LEFT JOIN customers c ON u.id = c.created_by
LEFT JOIN generated_documents gd ON u.id = gd.generated_by
WHERE u.is_active = 1
GROUP BY u.id, u.staff_id, u.full_name, u.role;

-- View: Document Status Summary
CREATE OR REPLACE VIEW vw_document_status_summary AS
SELECT 
    status,
    COUNT(*) as count,
    DATE(generated_at) as date
FROM generated_documents
GROUP BY status, DATE(generated_at);

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

DELIMITER //

-- Procedure: Generate Customer ID
CREATE PROCEDURE sp_generate_customer_id(OUT new_customer_id VARCHAR(50))
BEGIN
    DECLARE prefix VARCHAR(10);
    DECLARE next_number INT;
    
    SELECT setting_value INTO prefix FROM system_settings WHERE setting_key = 'customer_id_prefix';
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(customer_id, LENGTH(prefix) + 1) AS UNSIGNED)), 0) + 1 
    INTO next_number
    FROM customers 
    WHERE customer_id LIKE CONCAT(prefix, '%');
    
    SET new_customer_id = CONCAT(prefix, LPAD(next_number, 6, '0'));
END //

-- Procedure: Generate Document Number
CREATE PROCEDURE sp_generate_document_number(OUT new_document_number VARCHAR(50))
BEGIN
    DECLARE prefix VARCHAR(10);
    DECLARE next_number INT;
    
    SELECT setting_value INTO prefix FROM system_settings WHERE setting_key = 'document_number_prefix';
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(document_number, LENGTH(prefix) + 1) AS UNSIGNED)), 0) + 1 
    INTO next_number
    FROM generated_documents 
    WHERE document_number LIKE CONCAT(prefix, '%');
    
    SET new_document_number = CONCAT(prefix, LPAD(next_number, 6, '0'));
END //

-- Procedure: Log Audit
CREATE PROCEDURE sp_log_audit(
    IN p_user_id INT,
    IN p_action VARCHAR(100),
    IN p_entity_type VARCHAR(50),
    IN p_entity_id INT,
    IN p_description TEXT,
    IN p_ip_address VARCHAR(45)
)
BEGIN
    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, description, ip_address)
    VALUES (p_user_id, p_action, p_entity_type, p_entity_id, p_description, p_ip_address);
END //

DELIMITER ;

-- =====================================================
-- GRANT PERMISSIONS (Optional - for security)
-- =====================================================
-- GRANT ALL PRIVILEGES ON das_db.* TO 'das_user'@'localhost' IDENTIFIED BY 'das_password';
-- FLUSH PRIVILEGES;

-- =====================================================
-- END OF SCRIPT
-- =====================================================

SELECT 'DAS Database created successfully!' AS Status;
SELECT COUNT(*) AS 'Total Users' FROM users;
SELECT COUNT(*) AS 'Total Settings' FROM system_settings;
