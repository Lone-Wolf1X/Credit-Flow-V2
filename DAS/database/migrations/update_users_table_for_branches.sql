-- =====================================================
-- Update DAS Users Table for Branch Management
-- =====================================================
-- Add province, district, branch, and SOL fields to users table
-- =====================================================

USE das_db;

-- Add new columns to users table
ALTER TABLE users
ADD COLUMN IF NOT EXISTS province VARCHAR(100) COMMENT 'User province',
ADD COLUMN IF NOT EXISTS district VARCHAR(100) COMMENT 'User district',
ADD COLUMN IF NOT EXISTS sol_id VARCHAR(20) COMMENT 'SOL ID from branchsol table',
ADD COLUMN IF NOT EXISTS primary_branch_id INT COMMENT 'User primary/home branch',
ADD COLUMN IF NOT EXISTS current_branch_id INT COMMENT 'User current working branch (may differ due to deputation)',
ADD INDEX idx_province (province),
ADD INDEX idx_district (district),
ADD INDEX idx_sol (sol_id);

-- Add foreign keys for branch references
ALTER TABLE users
ADD CONSTRAINT fk_users_primary_branch FOREIGN KEY (primary_branch_id) REFERENCES branch_profiles (id) ON DELETE SET NULL,
ADD CONSTRAINT fk_users_current_branch FOREIGN KEY (current_branch_id) REFERENCES branch_profiles (id) ON DELETE SET NULL;

-- Create indexes for better performance
ALTER TABLE users
ADD INDEX idx_primary_branch (primary_branch_id),
ADD INDEX idx_current_branch (current_branch_id);

SELECT 'Users table updated successfully with branch and location fields' as status;