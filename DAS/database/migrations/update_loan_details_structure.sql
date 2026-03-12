-- Migration script to update loan_details table
-- Run this in phpMyAdmin or MySQL command line

USE das_db;

-- Update loan_type to ENUM and remove duplicate loan_scheme column
ALTER TABLE loan_details
MODIFY COLUMN loan_type ENUM(
    'New',
    'Renewal',
    'Enhancement',
    'Reduction'
) DEFAULT 'New' COMMENT 'Type of loan',
DROP COLUMN IF EXISTS loan_scheme;
-- Remove duplicate, use scheme_id instead