<?php
require __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpWord\TemplateProcessor;

$file = __DIR__ . '/templates/Individual/rokka_letter.docx';
$tempPath = __DIR__ . '/temp_rokka.docx';

if (!copy($file, $tempPath)) {
    die("Failed to copy file");
}

try {
    $template = new TemplateProcessor($tempPath);
    $vars = $template->getVariables();
    // Sort and unique
    $vars = array_unique($vars);
    sort($vars);
    
    echo "# Rokka Letter Placeholders\n";
    echo "Count: " . count($vars) . "\n\n";
    foreach ($vars as $var) {
        echo "- $var\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage();
}

unlink($tempPath);
?>
