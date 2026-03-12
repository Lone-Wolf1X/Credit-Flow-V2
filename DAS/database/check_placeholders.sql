-- Quick script to check and update placeholders
-- Run this in phpMyAdmin to see current placeholders

SELECT 'Current Placeholders:' AS Info;
SELECT placeholder_key, placeholder_name, category FROM template_placeholders LIMIT 10;

-- To update all placeholders, run the complete_placeholders.sql file
-- Or run this command in terminal:
-- mysql -u root das_db < C:/xampp/htdocs/Credit/DAS/database/complete_placeholders.sql
