-- Migration: Add workflow comment columns to customer_profiles table
-- Date: 2025-12-19
-- Description: Adds columns for checker comments and return workflow

USE das_db;

-- Add checker comment and return workflow columns
ALTER TABLE customer_profiles
ADD COLUMN IF NOT EXISTS checker_comment TEXT NULL COMMENT 'Comments from checker when returning profile',
ADD COLUMN IF NOT EXISTS returned_by INT NULL COMMENT 'User ID who returned the profile',
ADD COLUMN IF NOT EXISTS returned_at DATETIME NULL COMMENT 'Timestamp when profile was returned',
ADD CONSTRAINT fk_returned_by FOREIGN KEY (returned_by) REFERENCES users(id) ON DELETE SET NULL;

-- Update status enum to include 'Returned'
ALTER TABLE customer_profiles 
MODIFY COLUMN status ENUM('Draft', 'Submitted', 'Approved', 'Rejected', 'Returned') DEFAULT 'Draft';

-- Add index for better query performance
CREATE INDEX idx_status ON customer_profiles(status);
CREATE INDEX idx_returned_by ON customer_profiles(returned_by);

-- Display success message
SELECT 'Migration completed successfully! Workflow comment columns added.' AS message;
