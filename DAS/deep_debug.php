<?php
// Deep debug of placeholder generation
require_once 'config/config.php';
require_once 'includes/DocumentDataFetcher.php';
require_once 'includes/DocumentRuleEngine.php';
require_once 'includes/PlaceholderMapper.php';
require_once 'includes/NepaliDateConverter.php';

$profile_id = 1;

echo "=== DEEP PLACEHOLDER DEBUG ===\n\n";

// Test 1: Direct date conversion
echo "--- Test 1: Direct Date Conversion ---\n";
$converter = new NepaliDateConverter();
$bsDate = '2082-10-04';
$adDate = $converter->convertBsToAd($bsDate);
echo "BS: $bsDate → AD: $adDate\n";
echo "AD in Nepali: " . NepaliDateConverter::formatToNepaliNums($adDate) . "\n";
echo "BS in Nepali: " . NepaliDateConverter::formatToNepaliNums($bsDate) . "\n\n";

// Test 2: Fetch loan data
echo "--- Test 2: Raw Loan Data ---\n";
$fetcher = new DocumentDataFetcher($conn, $profile_id);
$rawData = $fetcher->fetchAll();
$loanData = $rawData['loan'];
echo "loan_approved_date: " . ($loanData['loan_approved_date'] ?? 'NULL') . "\n\n";

// Test 3: PlaceholderMapper
echo "--- Test 3: PlaceholderMapper Test ---\n";
$mapper = new PlaceholderMapper(['loan' => $loanData]);
$loanApprovedDate = $mapper->getLoanFieldValue($loanData, 'loan_approved_date');
echo "Mapper returned: $loanApprovedDate\n\n";

// Test 4: Full engine test
echo "--- Test 4: Full DocumentRuleEngine ---\n";
$engine = new DocumentRuleEngine('mortgage_deed', $conn);
$organizedData = $engine->fetchData($profile_id);
$allPlaceholders = $engine->mapToPlaceholders($organizedData);

echo "Date-related placeholders:\n";
foreach ($allPlaceholders as $key => $value) {
    if (strpos($key, 'LSL') !== false || strpos($key, 'DT') !== false) {
        echo "$key = '$value'\n";
    }
}
