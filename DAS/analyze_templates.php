<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

// Configuration
$baseDir = __DIR__;
$searchDirs = [
    $baseDir, // Root DAS folder
    $baseDir . '/templates/ptl', // System templates
];
$outputFile = $baseDir . '/generated_documents/template_analysis.txt';

// Ensure output dir exists
if (!file_exists(dirname($outputFile))) {
    mkdir(dirname($outputFile), 0777, true);
}

$report = "TEMPLATE ANALYSIS REPORT\n";
$report .= "Generated at: " . date('Y-m-d H:i:s') . "\n";
$report .= "================================================\n\n";

$filesFound = 0;

foreach ($searchDirs as $dir) {
    if (!is_dir($dir)) continue;
    
    $files = glob($dir . '/*.docx');
    
    foreach ($files as $file) {
        $filesFound++;
        $filename = basename($file);
        $path = realpath($file);
        
        $report .= "FILE: $filename\n";
        $report .= "PATH: $path\n";
        
        try {
            $template = new TemplateProcessor($path);
            $variables = $template->getVariables();
            sort($variables);
            
            $report .= "PLACEHOLDERS FOUND (" . count($variables) . "):\n";
            if (empty($variables)) {
                $report .= "  (None found)\n";
            } else {
                foreach ($variables as $var) {
                    $report .= "  - $var\n";
                }
            }
        } catch (Throwable $e) {
            $report .= "ERROR READING FILE: " . $e->getMessage() . "\n";
        }
        
        $report .= "\n------------------------------------------------\n\n";
    }
}

file_put_contents($outputFile, $report);

echo "Analysis complete. Scanned $filesFound files.\n";
echo "Report saved to: $outputFile\n";
