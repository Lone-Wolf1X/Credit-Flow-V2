-- Comprehensive Database Verification for Approval Workflow

-- 1. Check if loan_details table has scheme_id column
DESCRIBE loan_details;

-- 2. Check if there are any loan details with scheme_id
SELECT id, customer_profile_id, scheme_id, sanctioned_amount 
FROM loan_details 
LIMIT 5;

-- 3. Check if loan_schemes table exists and has data
SELECT id, scheme_name, scheme_code, is_active 
FROM loan_schemes 
WHERE is_active = 1;

-- 4. Check if templates table exists and has scheme associations
SELECT id, template_name, scheme_id, file_path 
FROM templates 
LIMIT 5;

-- 5. Check customer_profiles table structure
DESCRIBE customer_profiles;

-- 6. Check if there are submitted profiles
SELECT id, customer_id, full_name, status, submitted_at 
FROM customer_profiles 
WHERE status = 'Submitted';

-- 7. Verify database names
SHOW DATABASES LIKE '%das%';
SHOW DATABASES LIKE '%admin%';
