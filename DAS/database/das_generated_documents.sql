-- =====================================================
-- DAS Generated Documents Table
-- Stores profile-based generated documents
-- Decoupled from templates for independent lifecycle
-- =====================================================

CREATE TABLE IF NOT EXISTS das_generated_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_profile_id INT NOT NULL COMMENT 'Links to customer profile',
    loan_scheme_id INT NULL COMMENT 'Loan scheme used',
    template_id INT NULL COMMENT 'Template reference (soft, can be deleted)',
    template_snapshot JSON COMMENT 'Template metadata at generation time',
    document_type ENUM(
        'original',
        'renewal',
        'enhancement',
        'reduction'
    ) DEFAULT 'original',
    parent_document_id INT NULL COMMENT 'Links to previous version for renewals',
    file_path VARCHAR(500) NOT NULL COMMENT 'Absolute path to generated file',
    file_name VARCHAR(255) NOT NULL COMMENT 'Original filename',
    file_size_kb DECIMAL(10, 2) COMMENT 'File size in KB',
    status ENUM(
        'draft',
        'generated',
        'approved',
        'archived'
    ) DEFAULT 'generated',
    generated_by INT NOT NULL COMMENT 'User who generated the document',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    notes TEXT COMMENT 'Additional notes about this document',
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_profile_documents (customer_profile_id),
    INDEX idx_document_type (document_type),
    INDEX idx_status (status),
    INDEX idx_generated_at (generated_at),
    FOREIGN KEY (parent_document_id) REFERENCES das_generated_documents (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Add comment to table
ALTER TABLE das_generated_documents COMMENT = 'Stores generated documents with profile-based organization, independent of templates';