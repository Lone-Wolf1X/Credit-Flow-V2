-- Remove template_id foreign key constraint that's causing issues
-- The generated_documents table doesn't need template_id since we store template_name

USE das_db;

-- Drop the problematic foreign key constraint
ALTER TABLE generated_documents
DROP FOREIGN KEY IF EXISTS generated_documents_ibfk_2;

-- Drop the template_id column if it exists (we don't need it)
-- ALTER TABLE generated_documents
-- DROP COLUMN IF EXISTS template_id;

-- Verify the change
SHOW CREATE TABLE generated_documents;