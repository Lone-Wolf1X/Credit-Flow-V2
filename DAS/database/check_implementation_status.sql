-- =====================================================
-- QUICK DATABASE STATUS CHECK
-- Run this to see what's implemented and what's missing
-- =====================================================

USE das_db;

SELECT '
╔═══════════════════════════════════════════════════╗
║     DAS IMPLEMENTATION STATUS CHECK               ║
╚═══════════════════════════════════════════════════╝
' AS '';

-- =====================================================
-- 1. CHECK: Has migration been run?
-- =====================================================
SELECT '1. Database Migration Status' AS 'Check';

SELECT 
    CASE 
        WHEN (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_SCHEMA = 'das_db' 
              AND TABLE_NAME = 'loan_details' 
              AND COLUMN_NAME = 'template_id') > 0 
        THEN '❌ NOT MIGRATED - loan_details still has template_id column'
        ELSE '✅ MIGRATED - loan_details.template_id removed'
    END AS 'Migration Status',
    CASE 
        WHEN (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_SCHEMA = 'das_db' 
              AND TABLE_NAME = 'loan_details' 
              AND COLUMN_NAME = 'scheme_id') > 0 
        THEN '✅ scheme_id exists'
        ELSE '❌ scheme_id missing'
    END AS 'Scheme ID Status';

-- =====================================================
-- 2. CHECK: New tables created?
-- =====================================================
SELECT '2. New Tables Status' AS 'Check';

SELECT 
    'profile_documents' AS 'Table Name',
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END AS Status
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'das_db' AND TABLE_NAME = 'profile_documents'
UNION ALL
SELECT 
    'master_borrowers',
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'das_db' AND TABLE_NAME = 'master_borrowers'
UNION ALL
SELECT 
    'master_guarantors',
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'das_db' AND TABLE_NAME = 'master_guarantors'
UNION ALL
SELECT 
    'profile_comments',
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'das_db' AND TABLE_NAME = 'profile_comments';

-- =====================================================
-- 3. CHECK: Placeholders imported?
-- =====================================================
SELECT '3. Placeholder Library Status' AS 'Check';

SELECT 
    COUNT(*) as 'Total Placeholders',
    CASE 
        WHEN COUNT(*) >= 150 THEN '✅ COMPLETE (150+)'
        WHEN COUNT(*) > 0 THEN '⚠️ PARTIAL'
        ELSE '❌ EMPTY'
    END AS Status
FROM template_placeholders;

-- =====================================================
-- 4. CHECK: Stored procedures created?
-- =====================================================
SELECT '4. Stored Procedures Status' AS 'Check';

SELECT 
    ROUTINE_NAME AS 'Procedure Name',
    '✅ EXISTS' AS Status
FROM INFORMATION_SCHEMA.ROUTINES
WHERE ROUTINE_SCHEMA = 'das_db'
AND ROUTINE_TYPE = 'PROCEDURE'
AND ROUTINE_NAME IN (
    'sp_pick_profile',
    'sp_release_profile', 
    'sp_approve_profile',
    'sp_return_profile',
    'sp_get_available_templates',
    'sp_record_generated_document'
)
ORDER BY ROUTINE_NAME;

-- =====================================================
-- 5. CHECK: PHPWord installed?
-- =====================================================
SELECT '5. PHPWord Installation' AS 'Check';

SELECT 
    CASE 
        WHEN (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'das_db') > 0
        THEN 'Check file: c:\\xampp\\htdocs\\Credit\\DAS\\vendor\\autoload.php'
        ELSE 'Database connection OK'
    END AS 'PHPWord Status',
    'Run: composer require phpoffice/phpword in DAS folder' AS 'If Missing';

-- =====================================================
-- FINAL SUMMARY
-- =====================================================
SELECT '
╔═══════════════════════════════════════════════════╗
║              IMPLEMENTATION SUMMARY               ║
╠═══════════════════════════════════════════════════╣
║                                                   ║
║ ✅ = Implemented and working                      ║
║ ⚠️  = Partially implemented                       ║
║ ❌ = Not implemented yet                          ║
║                                                   ║
║ NEXT STEPS IF YOU SEE ❌:                         ║
║                                                   ║
║ 1. Import master_architecture_migration.sql      ║
║ 2. Import workflow_enhancements.sql              ║
║ 3. Import comprehensive_placeholders.sql         ║
║ 4. Run: composer require phpoffice/phpword       ║
║                                                   ║
╚═══════════════════════════════════════════════════╝
' AS 'SUMMARY';

-- =====================================================
-- QUICK FIX SCRIPT (if needed)
-- =====================================================
SELECT '
-- If migration shows ❌, run these commands:
-- (Uncomment and execute)

/*
USE das_db;

-- Remove old template_id from loan_details
ALTER TABLE loan_details DROP FOREIGN KEY IF EXISTS loan_details_ibfk_2;
ALTER TABLE loan_details DROP COLUMN IF EXISTS template_id;

-- Add new scheme_id
ALTER TABLE loan_details ADD COLUMN IF NOT EXISTS scheme_id INT NULL;
ALTER TABLE loan_details ADD CONSTRAINT fk_loan_scheme 
    FOREIGN KEY (scheme_id) REFERENCES loan_schemes(id);

SELECT "✅ Migration completed!" AS Status;
*/
' AS 'QUICK FIX';
