-- Add template_folder_path column to templates table
ALTER TABLE templates 
ADD COLUMN template_folder_path VARCHAR(500) NULL COMMENT 'Path to template file relative to DAS directory'
AFTER template_code;

-- Update existing templates with their file paths
UPDATE templates 
SET template_folder_path = 'templates/home_loan_regular/rokka_letter.docx'
WHERE id = 1;

UPDATE templates 
SET template_folder_path = 'templates/ptl/mortgage_deed_new.docx'
WHERE id = 11;

-- Verify the update
SELECT id, template_name, template_folder_path, scheme_id 
FROM templates 
ORDER BY id;
