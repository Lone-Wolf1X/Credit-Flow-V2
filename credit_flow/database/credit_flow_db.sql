-- Credit Flow Database Schema
-- Drop database if exists and create fresh
DROP DATABASE IF EXISTS credit_flow_db;
CREATE DATABASE credit_flow_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE credit_flow_db;

-- Users Master Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    province VARCHAR(50),
    branch VARCHAR(100),
    sol_id VARCHAR(50),
    designation VARCHAR(100),
    role ENUM('Initiator', 'Reviewer', 'Approver', 'Admin') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- Escalation Matrix Table
CREATE TABLE escalation_matrix (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_segment VARCHAR(50) NOT NULL,
    loan_type VARCHAR(100) NOT NULL,
    initiator_designation VARCHAR(100),
    reviewer_designation VARCHAR(100) NOT NULL,
    approver_designation VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_loan_segment (loan_segment),
    INDEX idx_loan_type (loan_type)
) ENGINE=InnoDB;

-- CAP ID Configuration Table
CREATE TABLE cap_id_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_segment VARCHAR(50) NOT NULL,
    loan_type VARCHAR(100) NOT NULL,
    prefix VARCHAR(20) NOT NULL,
    counter INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_segment_type (loan_segment, loan_type)
) ENGINE=InnoDB;

-- Loan Applications Table
CREATE TABLE loan_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cap_id VARCHAR(50) UNIQUE NOT NULL,
    applicant_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    loan_segment VARCHAR(50) NOT NULL,
    loan_scheme VARCHAR(100),
    loan_type VARCHAR(100) NOT NULL,
    loan_limit DECIMAL(15,2),
    relationship_start_date DATE,
    proposed_limit DECIMAL(15,2) NOT NULL,
    relationship_manager VARCHAR(100),
    initiator_id INT NOT NULL,
    reviewer_id INT,
    approver_id INT,
    status ENUM('Initiated', 'Under Review', 'Returned', 'Approved', 'Rejected') DEFAULT 'Initiated',
    current_stage ENUM('Initiator', 'Reviewer', 'Approver', 'Completed') DEFAULT 'Initiator',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (initiator_id) REFERENCES users(id),
    FOREIGN KEY (reviewer_id) REFERENCES users(id),
    FOREIGN KEY (approver_id) REFERENCES users(id),
    INDEX idx_cap_id (cap_id),
    INDEX idx_status (status),
    INDEX idx_initiator (initiator_id),
    INDEX idx_reviewer (reviewer_id),
    INDEX idx_approver (approver_id)
) ENGINE=InnoDB;

-- Application Files Table
CREATE TABLE application_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES loan_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_application (application_id)
) ENGINE=InnoDB;

-- Application Comments/Audit Trail Table
CREATE TABLE application_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    user_id INT NOT NULL,
    action ENUM('Initiated', 'Reviewed', 'Recommended', 'Returned', 'Approved', 'Rejected') NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES loan_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_application (application_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Insert Default Users (Staff ID 100-104)
INSERT INTO users (staff_id, password, full_name, email, contact_number, province, branch, sol_id, designation, role) VALUES
('100', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rajesh Kumar', 'rajesh.kumar@bank.com', '9841234567', 'Province 1', 'Kathmandu Branch', 'SOL001', 'Branch Officer', 'Initiator'),
('101', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sita Sharma', 'sita.sharma@bank.com', '9841234568', 'Province 3', 'Lalitpur Branch', 'SOL002', 'Branch Manager', 'Reviewer'),
('102', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Amit Thapa', 'amit.thapa@bank.com', '9841234569', 'Province 1', 'Head Office', 'SOL003', 'Retail Credit Head', 'Approver'),
('103', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Priya Adhikari', 'priya.adhikari@bank.com', '9841234570', 'Province 2', 'Biratnagar Branch', 'SOL004', 'Legal Officer', 'Reviewer'),
('104', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ramesh Poudel', 'ramesh.poudel@bank.com', '9841234571', 'Province 1', 'Head Office', 'SOL005', 'System Administrator', 'Admin');

-- Insert Default Escalation Matrix
INSERT INTO escalation_matrix (loan_segment, loan_type, initiator_designation, reviewer_designation, approver_designation) VALUES
('Retail', 'Personal Term Loan', 'Branch Officer', 'Branch Manager', 'Retail Credit Head'),
('Retail', 'Personal OD Loan', 'Branch Officer', 'Branch Manager', 'Retail Credit Head'),
('Retail', 'Professional Loan', 'Branch Officer', 'Branch Manager', 'Retail Credit Head'),
('Retail', 'Home Loan', 'Branch Officer', 'Branch Manager', 'Retail Credit Head'),
('Retail', 'LAP', 'Branch Officer', 'Branch Manager', 'Retail Credit Head'),
('Retail', 'Vehicle Loan', 'Branch Officer', 'Branch Manager', 'Retail Credit Head'),
('Retail', 'Education Loan', 'Branch Officer', 'Branch Manager', 'Retail Credit Head'),
('SME/MSME', 'Business Term Loan', 'Branch Officer', 'Branch Manager', 'SME Credit Head'),
('SME/MSME', 'Working Capital', 'Branch Officer', 'Branch Manager', 'SME Credit Head'),
('SME/MSME', 'Mudra Loan', 'Branch Officer', 'Branch Manager', 'SME Credit Head'),
('SME/MSME', 'CGTMSE', 'Branch Officer', 'Branch Manager', 'SME Credit Head'),
('Micro', 'Group Loan', 'Branch Officer', 'Branch Manager', 'Micro Credit Head'),
('Micro', 'Individual Micro Loan', 'Branch Officer', 'Branch Manager', 'Micro Credit Head'),
('Agriculture', 'Crop Loan/KCC', 'Branch Officer', 'Branch Manager', 'Agriculture Credit Head'),
('Agriculture', 'Tractor Loan', 'Branch Officer', 'Branch Manager', 'Agriculture Credit Head');

-- Insert CAP ID Configuration
INSERT INTO cap_id_config (loan_segment, loan_type, prefix, counter) VALUES
('Retail', 'Personal Term Loan', 'PML-RTL-TL', 0),
('Retail', 'Personal OD Loan', 'POD-RTL-OD', 0),
('Retail', 'Professional Loan', 'PRL-RTL-PR', 0),
('Retail', 'Home Loan', 'HML-RTL-HL', 0),
('Retail', 'LAP', 'LAP-RTL-LP', 0),
('Retail', 'Vehicle Loan', 'VHL-RTL-VL', 0),
('Retail', 'Education Loan', 'EDL-RTL-EL', 0),
('SME/MSME', 'Business Term Loan', 'BTL-SME-BT', 0),
('SME/MSME', 'Working Capital', 'WCL-SME-WC', 0),
('SME/MSME', 'Mudra Loan', 'MDL-SME-MD', 0),
('SME/MSME', 'CGTMSE', 'CGT-SME-CG', 0),
('Micro', 'Group Loan', 'GRL-MIC-GL', 0),
('Micro', 'Individual Micro Loan', 'IML-MIC-IM', 0),
('Agriculture', 'Crop Loan/KCC', 'CRL-AGR-CL', 0),
('Agriculture', 'Tractor Loan', 'TRL-AGR-TL', 0);
