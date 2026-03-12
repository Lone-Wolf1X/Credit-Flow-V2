<?php
// Direct DOCX to PDF conversion using LibreOffice or MS Word COM
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('Unauthorized');
}

$file = $_GET['file'] ?? '';

if (empty($file)) {
    die('File parameter required');
}

// Security: Only allow downloading from generated_documents folder
$basePath = __DIR__ . '/generated_documents/';

// Validate path
$file = str_replace(['../', '..\\'], '', $file); 
$file = str_replace('\\', '/', $file);
$filePath = $basePath . $file;
$realBasePath = realpath($basePath);
$realFilePath = realpath($filePath);

if (!$realFilePath || strpos($realFilePath, $realBasePath) !== 0 || !file_exists($filePath)) {
    die('File not found or access denied.');
}

// Check extension
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if ($ext !== 'docx') {
    die('Only DOCX files can be converted to PDF.');
}

try {
    // Generate PDF filename
    $pdfPath = str_replace('.docx', '.pdf', $filePath);
    $pdfFilename = basename($pdfPath);
    
    // Check if PDF already exists and is newer than DOCX
    if (file_exists($pdfPath) && filemtime($pdfPath) >= filemtime($filePath)) {
        // PDF exists and is up-to-date, serve it
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $pdfFilename . '"');
        header('Content-Length: ' . filesize($pdfPath));
        readfile($pdfPath);
        exit;
    }
    
    // Try Method 1: LibreOffice (if installed)
    $libreOfficePath = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
    
    if (file_exists($libreOfficePath)) {
        $outputDir = dirname($filePath);
        $command = '"' . $libreOfficePath . '" --headless --convert-to pdf --outdir "' . $outputDir . '" "' . $filePath . '"';
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($pdfPath)) {
            // Success! Serve the PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $pdfFilename . '"');
            header('Content-Length: ' . filesize($pdfPath));
            readfile($pdfPath);
            exit;
        }
    }
    
    // Try Method 2: Microsoft Word COM (Windows only)
    if (class_exists('COM')) {
        $word = new COM("Word.Application") or die("Unable to instantiate Word");
        $word->Visible = 0;
        
        $doc = $word->Documents->Open($filePath);
        $doc->SaveAs($pdfPath, 17); // 17 = wdFormatPDF
        $doc->Close();
        $word->Quit();
        
        if (file_exists($pdfPath)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $pdfFilename . '"');
            header('Content-Length: ' . filesize($pdfPath));
            readfile($pdfPath);
            exit;
        }
    }
    
    // If both methods fail, show error
    die('PDF conversion failed. Please install LibreOffice or ensure Microsoft Word is available.');
    
} catch (Exception $e) {
    die('Error converting to PDF: ' . $e->getMessage());
}
