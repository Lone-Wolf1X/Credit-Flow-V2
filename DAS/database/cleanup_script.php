<?php
/**
 * Database Cleanup Script
 * Deletes generated documents and logs for fresh start
 * Preserves: customer data, loan schemes, templates
 */

echo "=== Database Cleanup Script ===\n\n";

try {
    // Connect to database
    $conn = new mysqli('localhost', 'root', '', 'das_db');
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "✓ Connected to database\n\n";
    
    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // =====================================================
    // 1. DELETE GENERATED DOCUMENTS
    // =====================================================
    
    echo "Step 1: Deleting generated documents...\n";
    
    // Check and delete from profile_documents
    $result = $conn->query("SHOW TABLES LIKE 'profile_documents'");
    if ($result && $result->num_rows > 0) {
        $count = $conn->query("SELECT COUNT(*) as cnt FROM profile_documents")->fetch_assoc()['cnt'];
        $conn->query("DELETE FROM profile_documents");
        echo "  ✓ Deleted $count records from profile_documents\n";
    }
    
    // Check and delete from generated_documents
    $result = $conn->query("SHOW TABLES LIKE 'generated_documents'");
    if ($result && $result->num_rows > 0) {
        $count = $conn->query("SELECT COUNT(*) as cnt FROM generated_documents")->fetch_assoc()['cnt'];
        $conn->query("DELETE FROM generated_documents");
        echo "  ✓ Deleted $count records from generated_documents\n";
    }
    
    // Check and delete from das_generated_documents
    $result = $conn->query("SHOW TABLES LIKE 'das_generated_documents'");
    if ($result && $result->num_rows > 0) {
        $count = $conn->query("SELECT COUNT(*) as cnt FROM das_generated_documents")->fetch_assoc()['cnt'];
        $conn->query("DELETE FROM das_generated_documents");
        echo "  ✓ Deleted $count records from das_generated_documents\n";
    }
    
    // =====================================================
    // 2. DELETE DOCUMENT COMMENTS/LOGS
    // =====================================================
    
    echo "\nStep 2: Deleting document comments and logs...\n";
    
    // Delete document comments
    $result = $conn->query("SHOW TABLES LIKE 'document_comments'");
    if ($result && $result->num_rows > 0) {
        $count = $conn->query("SELECT COUNT(*) as cnt FROM document_comments")->fetch_assoc()['cnt'];
        $conn->query("DELETE FROM document_comments");
        echo "  ✓ Deleted $count records from document_comments\n";
    }
    
    // Delete profile comments
    $result = $conn->query("SHOW TABLES LIKE 'profile_comments'");
    if ($result && $result->num_rows > 0) {
        $count = $conn->query("SELECT COUNT(*) as cnt FROM profile_comments")->fetch_assoc()['cnt'];
        $conn->query("DELETE FROM profile_comments");
        echo "  ✓ Deleted $count records from profile_comments\n";
    }
    
    // Delete download logs
    $result = $conn->query("SHOW TABLES LIKE 'document_download_log'");
    if ($result && $result->num_rows > 0) {
        $count = $conn->query("SELECT COUNT(*) as cnt FROM document_download_log")->fetch_assoc()['cnt'];
        $conn->query("DELETE FROM document_download_log");
        echo "  ✓ Deleted $count records from document_download_log\n";
    }
    
    // =====================================================
    // 3. RESET AUTO INCREMENT
    // =====================================================
    
    echo "\nStep 3: Resetting auto increment counters...\n";
    
    $tables = ['profile_documents', 'generated_documents', 'document_comments'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            $conn->query("ALTER TABLE $table AUTO_INCREMENT = 1");
            echo "  ✓ Reset $table\n";
        }
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // =====================================================
    // 4. VERIFICATION
    // =====================================================
    
    echo "\n=== Verification ===\n\n";
    
    echo "Deleted Tables:\n";
    $result = $conn->query("
        SELECT 'Generated Documents' AS table_name, COUNT(*) AS count FROM generated_documents
        UNION ALL
        SELECT 'Profile Documents', COUNT(*) FROM profile_documents
        UNION ALL
        SELECT 'Document Comments', COUNT(*) FROM document_comments
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "  - {$row['table_name']}: {$row['count']} records\n";
        }
    }
    
    echo "\nPreserved Tables:\n";
    $result = $conn->query("
        SELECT 'Customer Profiles' AS table_name, COUNT(*) AS count FROM customer_profiles
        UNION ALL
        SELECT 'Borrowers', COUNT(*) FROM borrowers
        UNION ALL
        SELECT 'Guarantors', COUNT(*) FROM guarantors
        UNION ALL
        SELECT 'Collateral', COUNT(*) FROM collateral
        UNION ALL
        SELECT 'Loan Schemes', COUNT(*) FROM loan_schemes
        UNION ALL
        SELECT 'Templates', COUNT(*) FROM templates
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "  - {$row['table_name']}: {$row['count']} records\n";
        }
    }
    
    echo "\n✅ Database cleanup complete!\n";
    echo "\n⚠️  Manual Step Required:\n";
    echo "   Delete physical files from:\n";
    echo "   - C:\\xampp\\htdocs\\Credit\\DAS\\generated\\\n";
    echo "   - C:\\xampp\\htdocs\\Credit\\DAS\\uploads\\documents\\\n\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
