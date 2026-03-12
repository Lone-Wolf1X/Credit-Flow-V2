<?php
// Standalone test to verify generation logic bypassing Class Loading issues
require_once 'config/config.php';
require_once 'vendor/autoload.php';
require_once 'includes/DocumentDataFetcher.php';
require_once 'includes/PlaceholderMapper.php';
require_once 'includes/DocumentRuleEngine.php';

use PhpOffice\PhpWord\TemplateProcessor;

function generateDoc($conn, $templatePath, $customerProfileId, $documentType = 'mortgage_deed') {
    try {
        echo "Starting Generation for Profile $customerProfileId using $templatePath\n";
        
        $ruleEngine = new DocumentRuleEngine($documentType, $conn);
        $data = $ruleEngine->fetchData($customerProfileId);
        $placeholders = $ruleEngine->mapToPlaceholders($data);
        
        $templateProcessor = new TemplateProcessor($templatePath);
        
        // Log setup
        $logContent = "Generation Log - " . date('Y-m-d H:i:s') . "\n";
        $templateVariables = $templateProcessor->getVariables();
        
        foreach ($templateVariables as $var) {
            $value = $placeholders[$var] ?? null;
            if ($value !== null && $value !== '') {
                $logContent .= "[MATCH] \${$var} = '$value'\n";
                // Simple set for test
                $templateProcessor->setValue($var, $value);
            } else {
                $templateProcessor->setValue($var, ' ');
            }
        }
        
        file_put_contents(__DIR__ . '/generated_documents/last_generation_log.txt', $logContent);
        
        $outputPath = __DIR__ . '/generated_documents/TEST_DOC.docx';
        $templateProcessor->saveAs($outputPath);
        
        return ['success' => true, 'output_path' => $outputPath];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Logic from resolveDynamicPath (Simplified)
// We know Profile 2 has 1 borrower (from previous check)
// So we force use of mortgage_deed_1.docx if checking placeholders
// But previous check said mortgage_deed_1.docx
$profileId = 2;
// Check if mortgage_deed_1.docx exists
$templatePath = __DIR__ . '/templates/ptl/mortgage_deed_1.docx';
if (!file_exists($templatePath)) {
    die("Template $templatePath not found. Using default.\n");
    $templatePath = __DIR__ . '/templates/ptl/mortgage_deed.docx';
}

$result = generateDoc($conn, $templatePath, $profileId);

if ($result['success']) {
    echo "Files generated.\n";
    // Check Logs
    $log = file_get_contents(__DIR__ . '/generated_documents/last_generation_log.txt');
    if (strpos($log, '[MATCH] $COL1_KITTA_WORDS') !== false) {
        echo "SUCCESS: Found COL1_KITTA_WORDS\n";
    } else {
        echo "FAILURE: Missing COL1_KITTA_WORDS\n";
    }
    if (strpos($log, '[MATCH] $COL1_DHITO_MULYA') !== false) {
        echo "SUCCESS: Found COL1_DHITO_MULYA\n";
    } else {
        echo "FAILURE: Missing COL1_DHITO_MULYA\n";
    }
} else {
    echo "Generation Failed: " . $result['error'] . "\n";
}
