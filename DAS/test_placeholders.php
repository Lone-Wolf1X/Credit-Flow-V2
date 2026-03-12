<?php
require_once 'config/config.php';
require_once 'includes/DocumentDataFetcher.php';
require_once 'includes/DocumentRuleEngine.php';
require_once 'includes/PlaceholderLibrary.php';

$profile_id = 1; // Test with profile 1

echo "=== Testing Placeholder Generation for Profile $profile_id ===\n\n";

// Fetch data
$fetcher = new DocumentDataFetcher($conn, $profile_id);
$rawData = $fetcher->fetchAll();

echo "--- Raw Loan Data ---\n";
print_r($rawData['loan']);

echo "\n--- Raw Limit Data ---\n";
print_r($rawData['limit']);

// Generate placeholders
$engine = new DocumentRuleEngine('mortgage_deed', $conn);
$organizedData = $engine->fetchData($profile_id);
$placeholders = $engine->mapToPlaceholders($organizedData);

echo "\n--- Generated Loan Placeholders ---\n";
$loanPlaceholders = array_filter($placeholders, function($key) {
    return strpos($key, 'LN_') === 0;
}, ARRAY_FILTER_USE_KEY);

foreach ($loanPlaceholders as $key => $value) {
    echo "$key = $value\n";
}
