-- Fix generated_documents table structure
-- Add customer_profile_id column

USE das_db;

-- Add the missing column
ALTER TABLE generated_documents
ADD COLUMN customer_profile_id INT NULL AFTER id;

-- Add foreign key constraint
ALTER TABLE generated_documents
ADD CONSTRAINT fk_generated_docs_profile FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles (id) ON DELETE CASCADE;

-- Add index for better performance
CREATE INDEX idx_customer_profile ON generated_documents (customer_profile_id);

-- Verify the change
DESCRIBE generated_documents;