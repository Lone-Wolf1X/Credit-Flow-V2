# Email Configuration Guide

This guide explains how to configure the email notification system in the DAS application. The system uses **SMTP** to send emails.

The configuration file is located at: `c:\xampp\htdocs\Credit\DAS\config\config.php`

## 1. Gmail Configuration (Recommended)

To use Gmail, you cannot use your regular login password if you have 2-Step Verification enabled (which is standard now). You must use an **App Password**.

### Step 1: Generate App Password
1. Go to your [Google Account Security Settings](https://myaccount.google.com/security).
2. Under "How you sign in to Google", select **2-Step Verification**.
3. Scroll to the bottom and choose **App passwords**.
   - *Note: If you don't see this option, search for "App passwords" in the top search bar of your Google Account.*
4. Enter a name (e.g., "Credit DAS") and click **Create**.
5. Copy the 16-character password generated (e.g., `abcd efgh ijkl mnop`).

### Step 2: Update Config
Open `config/config.php` and update the settings:

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'your.email@gmail.com');  // Your full Gmail address
define('SMTP_PASS', 'abcd efgh ijkl mnop');   // The 16-char App Password (spaces don't matter)
define('SMTP_PORT', 587);
```

---

## 2. Microsoft Outlook / Office 365 Configuration

If you want to use a Microsoft account (Outlook.com, Hotmail, or Office 365 Business).

### Step 1: Preparedness
- **Outlook.com/Hotmail**: You function similarly to Gmail. You might need to generate an App Password via [Microsoft Account Security](https://account.live.com/proofs/manage) if 2FA is on.
- **Office 365 Business**: Ensure "Authenticated SMTP" is enabled for the user account in the Microsoft 365 Admin Center.

### Step 2: Update Config
Open `config/config.php` and update the settings:

```php
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_USER', 'your.email@outlook.com'); // Your Microsoft email
define('SMTP_PASS', 'your_password');          // Your Password or App Password
define('SMTP_PORT', 587);
```

*Note: Microsoft often blocks SMTP by default for new Azure tenants. You may need to enable "SMTP AUTH" in Exchange Online PowerShell.*

---

## 3. Custom / Corporate SMTP Configuration

If you have a corporate email server (e.g., Exchange On-Premise, cPanel, Zoho Mail).

### Required Details
Ask your IT administrator for:
1. **SMTP Host** (e.g., `mail.yourcompany.com`)
2. **SMTP Port** (Usually `587` for TLS or `465` for SSL)
3. **Username** (Usually your full email)
4. **Password**

### Example Config (Zoho Mail)
```php
define('SMTP_HOST', 'smtp.zoho.com');
define('SMTP_USER', 'info@yourdomain.com');
define('SMTP_PASS', 'your_secure_password');
define('SMTP_PORT', 465); // Note: Port 465 often uses implicit SSL
```

### Note on Encryption
The system is configured to use `STARTTLS` automatically. 
- If using **Port 587**, it works out of the box.
- If using **Port 465** (SSL), PHPMailer usually auto-negotiates, but ensure your config matches the provider's instructions.

## Troubleshooting

- **Authentication Error**: Double-check your Username and App Password.
- **Connection Timeout**: Your firewall or antivirus might be blocking the port. Try turning off antivirus temporarily to test.
- **Gmail Alert**: If you get an email saying "Sign-in attempt blocked", you MUST use an App Password.
