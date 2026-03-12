-- Fix template_id column to allow NULL values
-- This is the issue preventing database inserts

USE das_db;

-- Change template_id to allow NULL
ALTER TABLE generated_documents MODIFY COLUMN template_id INT NULL;

-- Verify the change
DESCRIBE generated_documents;

SELECT 'Fix applied successfully! template_id now allows NULL values.' as status;