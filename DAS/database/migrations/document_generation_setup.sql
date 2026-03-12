-- Migration script for document generation system
-- Run this in phpMyAdmin or MySQL command line

USE das_db;

-- Add missing field to collateral table
ALTER TABLE collateral
ADD COLUMN land_dhito_parit_mulya_words VARCHAR(500) DEFAULT NULL COMMENT 'Dhito Parit Mulya in words (Nepali)';

-- Create generated_documents table to track generated files
CREATE TABLE IF NOT EXISTS generated_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_profile_id INT NOT NULL,
    scheme_id INT NOT NULL,
    template_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    generated_by INT NOT NULL,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles (id) ON DELETE CASCADE,
    FOREIGN KEY (scheme_id) REFERENCES loan_schemes (id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_profile (customer_profile_id),
    INDEX idx_scheme (scheme_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Create loan_scheme_templates junction table if not exists
CREATE TABLE IF NOT EXISTS loan_scheme_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scheme_id INT NOT NULL,
    template_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scheme_id) REFERENCES loan_schemes (id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES templates (id) ON DELETE CASCADE,
    UNIQUE KEY unique_scheme_template (scheme_id, template_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;