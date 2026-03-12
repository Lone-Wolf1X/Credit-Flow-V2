-- Fix loan_details table schema to match the API and form
-- Run this script to update the database

USE das_db;

-- Check current structure
DESCRIBE loan_details;

-- Add missing columns if they don't exist
SET @dbname = 'das_db';

SET @tablename = 'loan_details';

-- Add approval_ref_no column
SET @columnname = 'approval_ref_no';

SET
    @preparedStatement = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE
                        table_name = @tablename
                        AND table_schema = @dbname
                        AND column_name = @columnname
                ) > 0, "SELECT 'approval_ref_no already exists' AS Status", "ALTER TABLE loan_details ADD COLUMN approval_ref_no VARCHAR(100) NULL AFTER loan_approved_date"
            )
    );

PREPARE alterIfNotExists FROM @preparedStatement;

EXECUTE alterIfNotExists;

DEALLOCATE PREPARE alterIfNotExists;

-- Add scheme_id column
SET @columnname = 'scheme_id';

SET
    @preparedStatement = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE
                        table_name = @tablename
                        AND table_schema = @dbname
                        AND column_name = @columnname
                ) > 0, "SELECT 'scheme_id already exists' AS Status", "ALTER TABLE loan_details ADD COLUMN scheme_id INT NULL AFTER approval_ref_no"
            )
    );

PREPARE alterIfNotExists FROM @preparedStatement;

EXECUTE alterIfNotExists;

DEALLOCATE PREPARE alterIfNotExists;

-- Add loan_purpose column
SET @columnname = 'loan_purpose';

SET
    @preparedStatement = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE
                        table_name = @tablename
                        AND table_schema = @dbname
                        AND column_name = @columnname
                ) > 0, "SELECT 'loan_purpose already exists' AS Status", "ALTER TABLE loan_details ADD COLUMN loan_purpose TEXT NULL AFTER scheme_id"
            )
    );

PREPARE alterIfNotExists FROM @preparedStatement;

EXECUTE alterIfNotExists;

DEALLOCATE PREPARE alterIfNotExists;

-- Show final structure
SELECT 'Updated loan_details structure:' AS Status;

DESCRIBE loan_details;