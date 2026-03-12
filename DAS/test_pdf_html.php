<?php
// Debug Script: Convert DOCX to HTML and display directly to check encoding
session_start();
require_once 'config/config.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('Unauthorized');
}

$file = $_GET['file'] ?? '';

if (empty($file)) {
    die('File parameter required');
}

$basePath = __DIR__ . '/generated_documents/';
$file = str_replace(['../', '..\\'], '', $file); 
$file = str_replace('\\', '/', $file);
$filePath = $basePath . $file;

if (!file_exists($filePath)) {
    die('File not found: ' . htmlspecialchars($file));
}

try {
    // 1. Load DOCX
    $phpWord = IOFactory::load($filePath);

    // 2. Convert to HTML
    $xmlWriter = IOFactory::createWriter($phpWord, 'HTML');

    // 3. Capture HTML
    ob_start();
    $xmlWriter->save("php://output");
    $htmlContent = ob_get_clean();

    // 4. Inject Font for Browser
    $fontPath = __DIR__ . '/assets/fonts/Mangal.otf';
    $fontCss = '';
    
    if (file_exists($fontPath)) {
        $fontData = base64_encode(file_get_contents($fontPath));
        $fontCss = "
            @font-face {
                font-family: 'Mangal';
                src: url('data:font/opentype;base64,{$fontData}');
            }
            body {
                font-family: 'Mangal', sans-serif !important;
            }
        ";
    }

    // 5. Output
    echo "
    <html>
    <head>
        <meta charset='utf-8'>
        <title>Debug PDF HTML</title>
        <style>
            {$fontCss}
            body { padding: 20px; background: #f0f0f0; }
            .page { background: white; padding: 40px; margin: 0 auto; max-width: 800px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        </style>
    </head>
    <body>
        <div class='page'>
            <h1>HTML Preview (Debug)</h1>
            <hr>
            {$htmlContent}
        </div>
    </body>
    </html>
    ";

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
