-- =====================================================
-- Clean Up Generated Documents and Logs
-- Safe deletion - preserves customer data, schemes, templates
-- =====================================================

USE das_db;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. DELETE GENERATED DOCUMENTS
-- =====================================================

-- Delete from profile_documents (if exists)
DELETE FROM profile_documents WHERE 1 = 1;

-- Delete from generated_documents (old schema)
DELETE FROM generated_documents WHERE 1 = 1;

-- Delete from das_generated_documents (if exists)
DELETE FROM das_generated_documents WHERE 1 = 1;

-- =====================================================
-- 2. DELETE DOCUMENT COMMENTS/LOGS
-- =====================================================

-- Delete document comments
DELETE FROM document_comments WHERE 1 = 1;

-- Delete from profile_comments (if exists)
DELETE FROM profile_comments WHERE 1 = 1;

-- =====================================================
-- 3. DELETE AUDIT LOGS (OPTIONAL - UNCOMMENT IF NEEDED)
-- =====================================================

-- Uncomment below to delete audit logs
-- DELETE FROM audit_logs WHERE 1=1;

-- =====================================================
-- 4. DELETE DOCUMENT DOWNLOAD LOGS (if exists)
-- =====================================================

DELETE FROM document_download_log WHERE 1 = 1;

-- =====================================================
-- 5. RESET AUTO INCREMENT COUNTERS
-- =====================================================

ALTER TABLE profile_documents AUTO_INCREMENT = 1;

ALTER TABLE generated_documents AUTO_INCREMENT = 1;

ALTER TABLE document_comments AUTO_INCREMENT = 1;

-- =====================================================
-- 6. DELETE PHYSICAL FILES (Manual Step)
-- =====================================================

-- You need to manually delete these folders:
-- C:\xampp\htdocs\Credit\DAS\generated\
-- C:\xampp\htdocs\Credit\DAS\uploads\documents\

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

SELECT 'Cleanup Complete!' AS Status;

SELECT
    'Generated Documents' AS Table_Name,
    COUNT(*) AS Remaining_Records
FROM generated_documents
UNION ALL
SELECT 'Profile Documents', COUNT(*)
FROM profile_documents
UNION ALL
SELECT 'Document Comments', COUNT(*)
FROM document_comments
UNION ALL
SELECT 'Audit Logs', COUNT(*)
FROM audit_logs;

-- Show what's preserved
SELECT
    'Customer Profiles' AS Table_Name,
    COUNT(*) AS Preserved_Records
FROM customer_profiles
UNION ALL
SELECT 'Borrowers', COUNT(*)
FROM borrowers
UNION ALL
SELECT 'Guarantors', COUNT(*)
FROM guarantors
UNION ALL
SELECT 'Collateral', COUNT(*)
FROM collateral
UNION ALL
SELECT 'Loan Schemes', COUNT(*)
FROM loan_schemes
UNION ALL
SELECT 'Templates', COUNT(*)
FROM templates;

SELECT '✅ Database cleaned. Customer data, loan schemes, and templates preserved.' AS Message;