-- Simple fix for generated_documents table
-- Add all missing columns (MySQL compatible)

USE das_db;

-- Add customer_profile_id column
ALTER TABLE generated_documents
ADD COLUMN customer_profile_id INT NULL AFTER id;

-- Add scheme_id column
ALTER TABLE generated_documents
ADD COLUMN scheme_id INT NULL AFTER customer_profile_id;

-- Add template_name column (check if it already exists first)
-- ALTER TABLE generated_documents
-- ADD COLUMN template_name VARCHAR(255) NULL AFTER scheme_id;

-- Add foreign key constraints
ALTER TABLE generated_documents
ADD CONSTRAINT fk_generated_docs_profile FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles (id) ON DELETE CASCADE;

ALTER TABLE generated_documents
ADD CONSTRAINT fk_generated_docs_scheme FOREIGN KEY (scheme_id) REFERENCES loan_schemes (id) ON DELETE CASCADE;

-- Add indexes for better performance
CREATE INDEX idx_customer_profile ON generated_documents (customer_profile_id);

CREATE INDEX idx_scheme ON generated_documents (scheme_id);

-- Verify the changes
DESCRIBE generated_documents;