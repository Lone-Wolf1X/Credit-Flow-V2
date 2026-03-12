<?php
require_once __DIR__ . '/vendor/autoload.php';

$templateDir = __DIR__ . '/Templates 2';
$files = glob($templateDir . '/*.docx');

echo "Scanning templates in $templateDir...\n\n";

foreach ($files as $file) {
    try {
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($file);
        $variables = $templateProcessor->getVariables();
        
        echo "File: " . basename($file) . "\n";
        echo "----------------------------------------\n";
        if (empty($variables)) {
            echo "No variables found.\n";
        } else {
            // Filter out unique variables to keep report clean
            $variables = array_unique($variables);
            sort($variables);
            foreach ($variables as $var) {
                echo " - \${" . $var . "}\n";
            }
        }
        echo "\n";
    } catch (Exception $e) {
        echo "Error processing " . basename($file) . ": " . $e->getMessage() . "\n\n";
    }
}
?>
