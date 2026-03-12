-- =====================================================
-- DATABASE VERIFICATION SCRIPT
-- Run this to check if all changes are applied
-- =====================================================

USE das_db;

SELECT '
╔═══════════════════════════════════════════════════╗
║         DATABASE VERIFICATION REPORT              ║
╚═══════════════════════════════════════════════════╝
' AS '';

-- =====================================================
-- 1. CHECK loan_details TABLE STRUCTURE
-- =====================================================
SELECT '1. Checking loan_details table...' AS 'Step';

SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_KEY,
    COLUMN_DEFAULT,
    EXTRA
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'das_db'
AND TABLE_NAME = 'loan_details'
AND COLUMN_NAME IN ('template_id', 'scheme_id')
ORDER BY COLUMN_NAME;

-- Expected result:
-- scheme_id exists (INT, can be NULL, has FK)
-- template_id SHOULD NOT exist

-- =====================================================
-- 2. CHECK profile_documents TABLE
-- =====================================================
SELECT '2. Checking profile_documents table...' AS 'Step';

SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN '✅ profile_documents table EXISTS'
        ELSE '❌ profile_documents table MISSING - Import workflow_enhancements.sql'
    END AS Status
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'das_db'
AND TABLE_NAME = 'profile_documents';

-- =====================================================
-- 3. CHECK master_borrowers TABLE
-- =====================================================
SELECT '3. Checking master_borrowers table...' AS 'Step';

SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN '✅ master_borrowers table EXISTS'
        ELSE '❌ master_borrowers table MISSING - Import workflow_enhancements.sql'
    END AS Status
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'das_db'
AND TABLE_NAME = 'master_borrowers';

-- =====================================================
-- 4. CHECK master_guarantors TABLE
-- =====================================================
SELECT '4. Checking master_guarantors table...' AS 'Step';

SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN '✅ master_guarantors table EXISTS'
        ELSE '❌ master_guarantors table MISSING - Import workflow_enhancements.sql'
    END AS Status
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'das_db'
AND TABLE_NAME = 'master_guarantors';

-- =====================================================
-- 5. CHECK profile_comments TABLE
-- =====================================================
SELECT '5. Checking profile_comments table...' AS 'Step';

SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN '✅ profile_comments table EXISTS'
        ELSE '❌ profile_comments table MISSING - Import workflow_enhancements.sql'
    END AS Status
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'das_db'
AND TABLE_NAME = 'profile_comments';

-- =====================================================
-- 6. CHECK STORED PROCEDURES
-- =====================================================
SELECT '6. Checking stored procedures...' AS 'Step';

SELECT 
    ROUTINE_NAME,
    '✅ EXISTS' AS Status
FROM INFORMATION_SCHEMA.ROUTINES
WHERE ROUTINE_SCHEMA = 'das_db'
AND ROUTINE_TYPE = 'PROCEDURE'
AND ROUTINE_NAME LIKE 'sp_%'
ORDER BY ROUTINE_NAME;

-- Expected procedures:
-- sp_pick_profile
-- sp_release_profile
-- sp_approve_profile
-- sp_return_profile
-- sp_get_available_templates
-- sp_record_generated_document

-- =====================================================
-- 7. CHECK VIEWS
-- =====================================================
SELECT '7. Checking views...' AS 'Step';

SELECT 
    TABLE_NAME,
    '✅ EXISTS' AS Status
FROM INFORMATION_SCHEMA.VIEWS
WHERE TABLE_SCHEMA = 'das_db'
AND TABLE_NAME LIKE 'vw_%'
ORDER BY TABLE_NAME;

-- Expected views:
-- vw_profile_list
-- vw_profile_complete

-- =====================================================
-- 8. CHECK PLACEHOLDER COUNT
-- =====================================================
SELECT '8. Checking placeholders...' AS 'Step';

SELECT 
    COUNT(*) as placeholder_count,
    CASE 
        WHEN COUNT(*) >= 150 THEN '✅ Comprehensive placeholders imported'
        WHEN COUNT(*) > 0 THEN '⚠️ Some placeholders exist but not complete set'
        ELSE '❌ No placeholders - Import comprehensive_placeholders.sql'
    END AS Status
FROM template_placeholders;

-- =====================================================
-- 9. CHECK FOREIGN KEYS
-- =====================================================
SELECT '9. Checking foreign key constraints...' AS 'Step';

SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'das_db'
AND (
    (TABLE_NAME = 'loan_details' AND COLUMN_NAME IN ('scheme_id', 'template_id'))
    OR
    (TABLE_NAME = 'profile_documents' AND COLUMN_NAME = 'template_id')
)
ORDER BY TABLE_NAME, COLUMN_NAME;

-- Expected:
-- loan_details.scheme_id → loan_schemes.id (should exist)
-- loan_details.template_id (should NOT exist)
-- profile_documents.template_id → templates.id (should exist)

-- =====================================================
-- 10. CHECK customer_profiles STATUS VALUES
-- =====================================================
SELECT '10. Checking customer_profiles status...' AS 'Step';

SELECT 
    COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'das_db'
AND TABLE_NAME = 'customer_profiles'
AND COLUMN_NAME = 'status';

-- Should show: enum('Draft','Submitted','Picked','Under Review','Approved','Rejected','Returned')

-- =====================================================
-- FINAL SUMMARY
-- =====================================================
SELECT '
╔═══════════════════════════════════════════════════╗
║              VERIFICATION COMPLETE                ║
╠═══════════════════════════════════════════════════╣
║ Review the results above:                         ║
║                                                   ║
║ ✅ = Working correctly                            ║
║ ⚠️  = Partially complete                          ║
║ ❌ = Action required                              ║
║                                                   ║
║ If you see ❌, import the missing SQL files       ║
╚═══════════════════════════════════════════════════╝
' AS 'VERIFICATION SUMMARY';

-- =====================================================
-- QUICK FIX: If template_id still exists in loan_details
-- =====================================================
-- Uncomment and run these if verification shows template_id exists:
/*
ALTER TABLE loan_details DROP FOREIGN KEY loan_details_ibfk_2;
ALTER TABLE loan_details DROP COLUMN template_id;
ALTER TABLE loan_details ADD COLUMN scheme_id INT NULL;
ALTER TABLE loan_details ADD FOREIGN KEY (scheme_id) REFERENCES loan_schemes(id);
*/
