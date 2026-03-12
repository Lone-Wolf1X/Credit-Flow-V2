-- Fix profile_documents table schema
USE das_db;

-- Check if table exists and add missing columns
ALTER TABLE profile_documents
ADD COLUMN IF NOT EXISTS document_type VARCHAR(100) AFTER customer_profile_id,
ADD COLUMN IF NOT EXISTS file_name VARCHAR(255) AFTER document_type,
ADD COLUMN IF NOT EXISTS file_path VARCHAR(500) AFTER file_name,
ADD COLUMN IF NOT EXISTS generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER file_path;

SELECT 'profile_documents table updated' AS Status;