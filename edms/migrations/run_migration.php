<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

echo "Running database migration: Adding is_locked columns...\n\n";

try {
    // Add is_locked to customer_documents table (General/Security docs)
    $sql1 = "ALTER TABLE customer_documents ADD COLUMN is_locked TINYINT(1) DEFAULT 0 AFTER status";
    if ($conn->query($sql1)) {
        echo "✓ Added is_locked column to customer_documents table\n";
    } else {
        if (strpos($conn->error, 'Duplicate column') !== false) {
            echo "✓ is_locked column already exists in customer_documents table\n";
        } else {
            throw new Exception("Error adding column to customer_documents: " . $conn->error);
        }
    }
    
    // Add is_locked to cap_documents table (Legal docs)
    $sql2 = "ALTER TABLE cap_documents ADD COLUMN is_locked TINYINT(1) DEFAULT 0 AFTER status";
    if ($conn->query($sql2)) {
        echo "✓ Added is_locked column to cap_documents table\n";
    } else {
        if (strpos($conn->error, 'Duplicate column') !== false) {
            echo "✓ is_locked column already exists in cap_documents table\n";
        } else {
            throw new Exception("Error adding column to cap_documents: " . $conn->error);
        }
    }
    
    echo "\n✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
