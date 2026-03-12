-- Template Module Setup Migration
-- This migration creates a standalone template module organized by customer type
-- and unlinks loan details from loan schemes

USE das_db;

-- =====================================================
-- 1. UPDATE TEMPLATES TABLE
-- =====================================================

-- Add template_type column to organize templates by customer type
ALTER TABLE templates
ADD COLUMN IF NOT EXISTS template_type ENUM('Individual', 'Institutional') NULL AFTER category;

-- Make scheme_id nullable (templates no longer require scheme linkage)
ALTER TABLE templates MODIFY COLUMN scheme_id INT NULL;

-- Add index for template_type for faster queries
ALTER TABLE templates
ADD INDEX IF NOT EXISTS idx_template_type (template_type);

-- =====================================================
-- 2. UPDATE CUSTOMER_PROFILES TABLE
-- =====================================================

-- Add template_category to link profiles to templates
ALTER TABLE customer_profiles
ADD COLUMN IF NOT EXISTS template_category ENUM('Individual', 'Institutional') NULL AFTER customer_type;

-- Auto-populate template_category for existing profiles
UPDATE customer_profiles
SET
    template_category = CASE
        WHEN customer_type = 'Individual' THEN 'Individual'
        WHEN customer_type = 'Corporate' THEN 'Institutional'
        ELSE 'Individual'
    END
WHERE
    template_category IS NULL;

-- Make template_category NOT NULL after populating
ALTER TABLE customer_profiles
MODIFY COLUMN template_category ENUM('Individual', 'Institutional') NOT NULL;

-- Add index for faster template lookups
ALTER TABLE customer_profiles
ADD INDEX IF NOT EXISTS idx_template_category (template_category);

-- =====================================================
-- 3. UPDATE LOAN_DETAILS TABLE
-- =====================================================

-- Make scheme_id nullable (unlink from loan schemes)
ALTER TABLE loan_details MODIFY COLUMN scheme_id INT NULL;

-- Add plain text field for loan scheme name
ALTER TABLE loan_details
ADD COLUMN IF NOT EXISTS loan_scheme_name VARCHAR(200) NULL AFTER loan_type;

-- Migrate existing scheme names to plain text
UPDATE loan_details ld
LEFT JOIN loan_schemes ls ON ld.scheme_id = ls.id
SET
    ld.loan_scheme_name = ls.scheme_name
WHERE
    ld.loan_scheme_name IS NULL
    AND ls.scheme_name IS NOT NULL;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Check templates table structure
SELECT 'Templates table updated' AS Status;

SHOW COLUMNS FROM templates LIKE 'template_type';

-- Check customer_profiles table structure
SELECT 'Customer profiles table updated' AS Status;

SHOW COLUMNS FROM customer_profiles LIKE 'template_category';

-- Check loan_details table structure
SELECT 'Loan details table updated' AS Status;

SHOW COLUMNS FROM loan_details LIKE 'loan_scheme_name';

-- Count profiles by template category
SELECT
    template_category,
    COUNT(*) as profile_count
FROM customer_profiles
GROUP BY
    template_category;

SELECT 'Migration completed successfully!' AS Status;