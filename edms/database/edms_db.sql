-- Enhanced EDMS Database Schema
-- Drop and recreate database
DROP DATABASE IF EXISTS edms_db;
CREATE DATABASE edms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE edms_db;

-- Customers Table (Updated with Client ID)
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    client_id VARCHAR(50) UNIQUE NOT NULL,
    created_by INT NOT NULL,
    status ENUM('Pending', 'Approved', 'Returned') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_client_id (client_id),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB;

-- Customer Documents Table (Updated with CAP ID reference)
CREATE TABLE customer_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    cap_id VARCHAR(50) NOT NULL,
    document_category ENUM('General', 'Security', 'Legal') NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    remark TEXT,
    uploaded_by INT NOT NULL,
    is_locked TINYINT(1) DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_cap_id (cap_id),
    INDEX idx_category (document_category),
    INDEX idx_locked (is_locked)
) ENGINE=InnoDB;

-- CAP Documents Table (Updated with folder path and locking)
CREATE TABLE cap_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    cap_id VARCHAR(50) NOT NULL,
    folder_path VARCHAR(500) NOT NULL,
    status ENUM('Pending Legal', 'Approved', 'Returned') DEFAULT 'Pending Legal',
    is_locked TINYINT(1) DEFAULT 0,
    submitted_by INT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_cap_id (cap_id),
    INDEX idx_status (status),
    INDEX idx_locked (is_locked)
) ENGINE=InnoDB;

-- Legal Comments Table
CREATE TABLE legal_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cap_document_id INT NOT NULL,
    user_id INT NOT NULL,
    action ENUM('Submitted', 'Returned', 'Approved') NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cap_document_id) REFERENCES cap_documents(id) ON DELETE CASCADE,
    INDEX idx_cap_document (cap_document_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Document Dropdowns Table (NEW)
CREATE TABLE document_dropdowns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('General', 'Security', 'Legal') NOT NULL,
    document_name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Notifications Table (NEW)
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    customer_id INT NOT NULL,
    cap_id VARCHAR(50),
    message TEXT NOT NULL,
    type VARCHAR(50),
    link VARCHAR(500),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_customer (customer_id)
) ENGINE=InnoDB;

-- Document Audit Table (NEW)
CREATE TABLE document_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT,
    customer_id INT NOT NULL,
    cap_id VARCHAR(50),
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer (customer_id),
    INDEX idx_cap (cap_id),
    INDEX idx_user (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- EDMS Audit Log Table
CREATE TABLE edms_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Insert Default Document Dropdowns
INSERT INTO document_dropdowns (category, document_name, display_order) VALUES
-- General Documents
('General', 'KYC Documents', 1),
('General', 'Application Form', 2),
('General', 'Photographs', 3),
('General', 'Identity Proof', 4),
('General', 'Address Proof', 5),
('General', 'Income Proof', 6),
('General', 'Bank Statements', 7),

-- Security Documents
('Security', 'Property Papers', 1),
('Security', 'Title Deed', 2),
('Security', 'Hypothecation Deed', 3),
('Security', 'Valuation Report', 4),
('Security', 'Insurance Documents', 5),
('Security', 'NOC from Society', 6),
('Security', 'Encumbrance Certificate', 7),

-- Legal Documents
('Legal', 'Loan Agreement', 1),
('Legal', 'Mortgage Deed', 2),
('Legal', 'Promissory Note', 3),
('Legal', 'Guarantee Documents', 4),
('Legal', 'Legal Opinion', 5),
('Legal', 'Sanction Letter', 6),
('Legal', 'Disbursement Letter', 7);
