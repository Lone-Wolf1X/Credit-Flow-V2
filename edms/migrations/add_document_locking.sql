-- Migration: Add is_locked column to documents tables
-- Purpose: Support document locking when CAP is approved

-- Add is_locked to documents table (General/Security documents)
ALTER TABLE documents 
ADD COLUMN is_locked TINYINT(1) DEFAULT 0 COMMENT 'Document locked status' AFTER status;

-- Add is_locked to cap_documents table (Legal documents)
ALTER TABLE cap_documents 
ADD COLUMN is_locked TINYINT(1) DEFAULT 0 COMMENT 'Document locked status' AFTER status;

-- Create index for faster queries
CREATE INDEX idx_documents_locked ON documents(is_locked);
CREATE INDEX idx_cap_documents_locked ON cap_documents(is_locked);

-- Verify columns were added
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    COLUMN_TYPE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'edms_db'
AND COLUMN_NAME = 'is_locked'
ORDER BY TABLE_NAME;
