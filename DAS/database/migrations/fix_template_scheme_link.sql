-- Fix Template-Scheme Linking Issue
-- Run this in phpMyAdmin (das_db database)

-- The issue: Customer profiles are linked to Scheme 8 (Personal Term Loan)
-- But template ID 20 is linked to Scheme 1 (Home Loan)

-- Solution: Link template ID 20 to Scheme ID 8
INSERT INTO
    loan_scheme_templates (scheme_id, template_id)
VALUES (8, 20)
ON DUPLICATE KEY UPDATE
    scheme_id = scheme_id;

-- Verify the fix
SELECT lst.id, ls.scheme_name, t.template_name, t.file_path
FROM
    loan_scheme_templates lst
    JOIN loan_schemes ls ON lst.scheme_id = ls.id
    JOIN templates t ON lst.template_id = t.id;