<?php
/**
 * Debug Document Generation
 * Quick test to see what's wrong
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Document Generation Debug</h2>";

// Test 1: Check database connection
echo "<h3>1. Database Connection</h3>";
$conn = new mysqli('localhost', 'root', '', 'das_db');
if ($conn->connect_error) {
    echo "❌ Connection failed: " . $conn->connect_error . "<br>";
} else {
    echo "✅ Database connected<br>";
}

// Test 2: Check if tables exist
echo "<h3>2. Check Tables</h3>";
$tables = ['generated_documents', 'loan_scheme_templates', 'collateral'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
    } else {
        echo "❌ Table '$table' NOT found<br>";
    }
}

// Test 3: Check collateral column
echo "<h3>3. Check Collateral Column</h3>";
$result = $conn->query("SHOW COLUMNS FROM collateral LIKE 'land_dhito_parit_mulya_words'");
if ($result->num_rows > 0) {
    echo "✅ Column 'land_dhito_parit_mulya_words' exists<br>";
} else {
    echo "❌ Column 'land_dhito_parit_mulya_words' NOT found<br>";
}

// Test 4: Check if PlaceholderMapper exists
echo "<h3>4. Check Files</h3>";
$files = [
    'includes/PlaceholderMapper.php',
    'includes/DocumentGenerator.php',
    'modules/api/document_api.php',
    'vendor/autoload.php'
];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ File '$file' exists<br>";
    } else {
        echo "❌ File '$file' NOT found<br>";
    }
}

// Test 5: Check if generated_documents folder exists
echo "<h3>5. Check Folders</h3>";
$folders = ['generated_documents', 'generated', 'templates'];
foreach ($folders as $folder) {
    if (is_dir($folder)) {
        echo "✅ Folder '$folder' exists<br>";
        if (is_writable($folder)) {
            echo "  ✅ Writable<br>";
        } else {
            echo "  ❌ NOT writable<br>";
        }
    } else {
        echo "❌ Folder '$folder' NOT found<br>";
    }
}

// Test 6: Try to load PlaceholderMapper
echo "<h3>6. Test PlaceholderMapper</h3>";
try {
    require_once 'includes/PlaceholderMapper.php';
    $mapper = new PlaceholderMapper($conn);
    echo "✅ PlaceholderMapper loaded successfully<br>";
    
    // Try to get data for profile 1
    $placeholders = $mapper->getPlaceholderData(1);
    echo "✅ Got " . count($placeholders) . " placeholders<br>";
    echo "<details><summary>View Placeholders</summary><pre>";
    print_r($placeholders);
    echo "</pre></details>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 7: Check PHPWord
echo "<h3>7. Test PHPWord</h3>";
try {
    require_once 'vendor/autoload.php';
    echo "✅ Vendor autoload loaded<br>";
    
    if (class_exists('PhpOffice\PhpWord\TemplateProcessor')) {
        echo "✅ PHPWord TemplateProcessor class exists<br>";
    } else {
        echo "❌ PHPWord TemplateProcessor class NOT found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 8: Check for customer data
echo "<h3>8. Check Customer Data</h3>";
$profile_id = 1;
$borrowers = $conn->query("SELECT COUNT(*) as count FROM borrowers WHERE customer_profile_id = $profile_id");
$row = $borrowers->fetch_assoc();
echo "Borrowers: " . $row['count'] . "<br>";

$guarantors = $conn->query("SELECT COUNT(*) as count FROM guarantors WHERE customer_profile_id = $profile_id");
$row = $guarantors->fetch_assoc();
echo "Guarantors: " . $row['count'] . "<br>";

$collaterals = $conn->query("SELECT COUNT(*) as count FROM collateral WHERE customer_profile_id = $profile_id");
$row = $collaterals->fetch_assoc();
echo "Collaterals: " . $row['count'] . "<br>";

echo "<hr>";
echo "<h3>Summary</h3>";
echo "If all tests pass, the system should work. If not, run the missing SQL migrations.";
?>
