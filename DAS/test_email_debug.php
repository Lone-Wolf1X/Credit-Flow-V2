<?php
// Test MailService isolation
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Starting MailService Test...\n";

try {
    $mailServicePath = __DIR__ . '/includes/MailService.php';
    echo "Converting path: $mailServicePath\n";
    
    if (!file_exists($mailServicePath)) {
        throw new Exception("MailService.php not found at $mailServicePath");
    }

    require_once $mailServicePath;
    echo "Included MailService.php\n";

    if (!class_exists('MailService')) {
        throw new Exception("Class MailService does not exist after include");
    }
    echo "Class MailService exists.\n";

    $svc = new MailService();
    echo "Instantiated MailService.\n";

    // Prepare dummy data
    $recipientEmail = 'abhi.pwn2020@gmail.com'; // Use config or dummy
    if (defined('SMTP_USER')) {
        $recipientEmail = SMTP_USER;
    }
    
    $recipientName = 'Tester';
    $profileData = [
        'customer_id' => 'TEST-001',
        'full_name' => 'Test Customer',
        'created_by_name' => 'Admin',
        'id' => 1
    ];
    $zipLink = 'http://localhost/test.zip';

    echo "Attempting to send email to $recipientEmail...\n";
    // We don't actually need to send successfully to prove it doesn't crash 500.
    // But let's try.
    $result = $svc->sendProfileApprovedEmail($recipientEmail, $recipientName, $profileData, $zipLink);
    
    if ($result) {
        echo "Email Sent Successfully!\n";
    } else {
        echo "Email Failed (Expected if SMTP bad), but script didn't crash.\n";
    }

} catch (Throwable $e) {
    echo "FATAL ERROR CAUGHT: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo $e->getTraceAsString();
}
