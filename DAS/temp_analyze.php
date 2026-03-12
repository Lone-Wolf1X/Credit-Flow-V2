<?php
// DAS/temp_analyze.php

require __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpWord\TemplateProcessor;

$dir = __DIR__ . '/templates';
if (!is_dir($dir)) die("Templates dir not found");

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

echo "# Template Analysis Report\n";
echo "Generated on: " . date('Y-m-d H:i:s') . "\n\n";

foreach ($iterator as $file) {
    if ($file->isFile() && strtolower($file->getExtension()) === 'docx') {
        $filename = $file->getFilename();
        
        // Skip temp files and dummy files
        if (strpos($filename, '~') === 0 || stripos($filename, 'dummy') !== false) {
            continue;
        }

        $path = $file->getRealPath();
        // Copy to temp to avoid lock issues
        $tempPath = __DIR__ . '/temp_' . $filename;
        if (!copy($path, $tempPath)) {
            echo "## [FAILED TO COPY] $filename\n\n";
            continue;
        }

        $relativePath = str_replace($dir . DIRECTORY_SEPARATOR, '', $path);
        
        echo "## $relativePath\n";
        
        try {
            $template = new TemplateProcessor($tempPath);
            $vars = $template->getVariables();
            $vars = array_unique($vars);
            sort($vars);
            
            if (empty($vars)) {
                 echo "**No Placeholders Found**\n";
            } else {
                echo "**Count:** " . count($vars) . "\n\n";
                echo "| Placeholder |\n";
                echo "| --- |\n";
                foreach ($vars as $var) {
                    echo "| $var |\n";
                }
            }
        } catch (Throwable $e) {
             echo "**Error:** " . $e->getMessage() . "\n";
        }
        
        echo "\n---\n\n";
        
        // Cleanup
        unlink($tempPath);
    }
}
?>
