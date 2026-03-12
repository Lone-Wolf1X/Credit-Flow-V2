<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

class MailService {
    
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->setup();
    }

    private function setup() {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host       = SMTP_HOST;
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = SMTP_USER;
            $this->mailer->Password   = SMTP_PASS;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = SMTP_PORT;

            // Sender
            $this->mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $this->mailer->isHTML(true);
        } catch (Exception $e) {
            error_log("Mail Setup Error: " . $e->getMessage());
        }
    }

    public function sendProfileApprovedEmail($recipientEmail, $recipientName, $profileData, $zipLink) {
        if (empty($recipientEmail)) {
            return false;
        }

        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($recipientEmail, $recipientName);

            $this->mailer->Subject = "Profile Approved: " . $profileData['full_name'] . " (" . $profileData['customer_id'] . ")";
            $this->mailer->Body    = $this->getApprovalTemplate($recipientName, $profileData, $zipLink);
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Mail Send Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    private function getApprovalTemplate($recipientName, $profileData, $zipLink) {
        $link = "http://" . $_SERVER['HTTP_HOST'] . "/Credit/DAS/modules/customer/customer_profile.php?id=" . $profileData['id'];
        
        $css = "
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow: hidden; }
            .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; }
            .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .info-table td { padding: 10px; border-bottom: 1px solid #eee; }
            .label { font-weight: bold; color: #555; width: 40%; }
            .value { color: #333; }
            .btn { display: inline-block; padding: 12px 24px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 20px; }
            .btn-download { background-color: #28a745; margin-left: 10px; }
            .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #888; }
        ";

        return "
        <html>
        <head>
            <style>{$css}</style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ Profile Approved</h1>
                    <p>The loan profile has been successfully approved.</p>
                </div>
                <div class='content'>
                    <p>Dear <strong>{$recipientName}</strong>,</p>
                    <p>The following customer profile has been reviewed and approved by the Checker.</p>
                    
                    <table class='info-table'>
                        <tr>
                            <td class='label'>Customer ID:</td>
                            <td class='value'>{$profileData['customer_id']}</td>
                        </tr>
                        <tr>
                            <td class='label'>Customer Name:</td>
                            <td class='value'>{$profileData['full_name']}</td>
                        </tr>
                        <tr>
                            <td class='label'>Submitted By:</td>
                            <td class='value'>{$profileData['created_by_name']}</td>
                        </tr>
                        <tr>
                            <td class='label'>Approved On:</td>
                            <td class='value'>" . date('Y-m-d H:i') . "</td>
                        </tr>
                        <tr>
                            <td class='label'>Status:</td>
                            <td class='value'><span style='color: green; font-weight: bold;'>Approved</span></td>
                        </tr>
                    </table>

                    <div style='text-align: center;'>
                        <a href='{$link}' class='btn'>View Profile</a>
                        <a href='{$zipLink}' class='btn btn-download'> Download Documents (ZIP)</a>
                    </div>
                </div>
                <div class='footer'>
                    <p>This is an automated message from Credit DAS System.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
