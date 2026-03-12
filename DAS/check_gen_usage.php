<?php
// Test script to generate document for Profile 2 and verify template selection
require_once 'config/config.php';
require_once 'includes/DocumentGenerator.php';

// Profile ID 2 (2025002)
$profileId = 2;

echo "=== TESTING DOCUMENT GENERATION FOR PROFILE $profileId ===\n";

$generator = new DocumentGenerator($conn);

// We need to fetch the loan details to pass to getTemplatePath equivalent or let generate() handle it.
// The generate() method in DocumentGenerator calls getTemplatePath internally IF we used the old method?
// Wait, generate() takes $templatePath as argument!
// Look at DocumentGenerator::generate (step 335, line 109).
// public function generate($templatePath, $customerProfileId, $documentType = 'mortgage_deed')

// But how does the SYSTEM call it?
// customer_api.php calls it.
// Let's see how customer_api.php calls it.
// It calls approveProfile -> ...
// I need to see customer_api.php lines 800-1000 again to see how it determines template path.
// If customer_api.php determines path, MY CHANGE IN DocumentGenerator::getTemplatePath MIGHT BE IGNORED if API doesn't use it!

// Use reflection or just check customer_api.php.
// If customer_api.php uses: $templatePath = $generator->getTemplatePath($loan);
// Then my change works.
// Checks customer_api.php...

// Implementation:
// I'll simulate what customer_api.php does.
// But first I must verify customer_api.php usage.
// I'll assume it uses getTemplatePath because that's where I made the change.

// Fetch loan data to pass to getTemplatePath (it expects $loan array)
// Actually getTemplatePath is private!
// But wait, generateMortgageDeed() (line 35) calls it.
// Is generateMortgageDeed() used?
// Or generate()?

// Let's check customer_api.php.
// checking...
// output of step 198 (session 1) showed approveProfile logic.
// I'll start by checking customer_api.php to see how it invokes generation.
// This is critical.

$gen = new DocumentGenerator($conn);
// I can't call getTemplatePath easily if private.
// But I can check what generateMortgageDeed does.
// If the API calls generateMortgageDeed, I am good.
// If API calls generate(), it passes templatePath.
// If API calculates templatePath itself, I MUST UPDATE API TO USE NEW LOGIC.

// Let's Check customer_api.php first.
echo "Checking customer_api.php usage...\n";
$content = file_get_contents('modules/api/customer_api.php');
if (strpos($content, '->generateMortgageDeed(') !== false) {
    echo "API uses generateMortgageDeed()\n";
} elseif (strpos($content, '->getTemplatePath(') !== false) { // if public
    echo "API uses getTemplatePath()\n";
} else {
    echo "API might be calculating path itself or using generate(path...)\n";
    // Check for loop
    // if (strpos($content, 'templates/ptl/mortgage_deed.docx') !== false) ...
}

?>
