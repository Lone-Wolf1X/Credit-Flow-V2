-- Complete fix for generated_documents table
-- Add all missing columns needed by the new document generation system

USE das_db;

-- Add customer_profile_id column (if not exists)
ALTER TABLE generated_documents
ADD COLUMN IF NOT EXISTS customer_profile_id INT NULL AFTER id;

-- Add scheme_id column (if not exists)
ALTER TABLE generated_documents
ADD COLUMN IF NOT EXISTS scheme_id INT NULL AFTER customer_profile_id;

-- Add template_name column (if not exists)
ALTER TABLE generated_documents
ADD COLUMN IF NOT EXISTS template_name VARCHAR(255) NULL AFTER scheme_id;

-- Add foreign key constraints
ALTER TABLE generated_documents
ADD CONSTRAINT IF NOT EXISTS fk_generated_docs_profile FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles (id) ON DELETE CASCADE;

ALTER TABLE generated_documents
ADD CONSTRAINT IF NOT EXISTS fk_generated_docs_scheme FOREIGN KEY (scheme_id) REFERENCES loan_schemes (id) ON DELETE CASCADE;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_customer_profile ON generated_documents (customer_profile_id);

CREATE INDEX IF NOT EXISTS idx_scheme ON generated_documents (scheme_id);

-- Verify the changes
DESCRIBE generated_documents;

SELECT 'Migration completed successfully!' as status;