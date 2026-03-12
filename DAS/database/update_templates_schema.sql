-- Update templates table for HTML content
USE das_db;

ALTER TABLE templates 
ADD COLUMN html_content LONGTEXT AFTER file_path,
ADD COLUMN css_styles TEXT AFTER html_content,
ADD COLUMN last_modified_by INT AFTER created_by,
ADD COLUMN last_modified_at TIMESTAMP NULL AFTER updated_at;

-- Create template_placeholders table
CREATE TABLE IF NOT EXISTS template_placeholders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    placeholder_key VARCHAR(100) UNIQUE NOT NULL,
    placeholder_name VARCHAR(200) NOT NULL,
    category VARCHAR(50),
    description TEXT,
    example_value VARCHAR(500),
    data_type ENUM('text', 'number', 'date', 'currency') DEFAULT 'text',
    is_system TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default placeholders
INSERT INTO template_placeholders (placeholder_key, placeholder_name, category, description, example_value, data_type, is_system) VALUES
-- Customer Placeholders
('customer.full_name', 'Customer Full Name', 'Customer', 'Full name of the customer', 'Rajesh Kumar Sharma', 'text', 1),
('customer.address', 'Customer Address', 'Customer', 'Complete address of customer', 'Kathmandu-10, Nepal', 'text', 1),
('customer.citizenship_number', 'Citizenship Number', 'Customer', 'Citizenship number', '12345-6789-0123', 'text', 1),
('customer.pan_number', 'PAN Number', 'Customer', 'PAN number', '123456789', 'text', 1),
('customer.mobile', 'Mobile Number', 'Customer', 'Contact mobile number', '9841234567', 'text', 1),
('customer.email', 'Email Address', 'Customer', 'Email address', 'customer@example.com', 'text', 1),
('customer.dob', 'Date of Birth', 'Customer', 'Customer date of birth', '1990-01-15', 'date', 1),
('customer.father_name', 'Father Name', 'Customer', 'Father\'s name', 'Ram Kumar Sharma', 'text', 1),

-- Loan Placeholders
('loan.amount', 'Loan Amount', 'Loan', 'Sanctioned loan amount', '500000', 'currency', 1),
('loan.amount_words', 'Loan Amount in Words', 'Loan', 'Loan amount in words', 'Five Lakh Only', 'text', 1),
('loan.interest_rate', 'Interest Rate', 'Loan', 'Annual interest rate', '12.5', 'number', 1),
('loan.tenure', 'Loan Tenure', 'Loan', 'Loan tenure in months', '60', 'number', 1),
('loan.type', 'Loan Type', 'Loan', 'Type of loan', 'Personal Loan', 'text', 1),
('loan.scheme', 'Loan Scheme', 'Loan', 'Loan scheme name', 'Regular Scheme', 'text', 1),
('loan.purpose', 'Loan Purpose', 'Loan', 'Purpose of loan', 'Business Expansion', 'text', 1),
('loan.application_date', 'Application Date', 'Loan', 'Loan application date', '2025-12-18', 'date', 1),

-- Collateral Placeholders
('collateral.type', 'Collateral Type', 'Collateral', 'Type of collateral', 'Land', 'text', 1),
('collateral.value', 'Collateral Value', 'Collateral', 'Market value of collateral', '1500000', 'currency', 1),
('collateral.location', 'Collateral Location', 'Collateral', 'Location of collateral', 'Kathmandu, Ward 10', 'text', 1),
('collateral.owner', 'Collateral Owner', 'Collateral', 'Owner of collateral', 'Rajesh Kumar Sharma', 'text', 1),

-- System Placeholders
('date.today', 'Today\'s Date', 'System', 'Current date', '2025-12-18', 'date', 1),
('date.today_nepali', 'Today\'s Date (Nepali)', 'System', 'Current date in Nepali', '2081-09-03', 'date', 1),
('bank.name', 'Bank Name', 'System', 'Name of the bank', 'State Bank of India', 'text', 1),
('bank.address', 'Bank Address', 'System', 'Bank address', 'Kathmandu, Nepal', 'text', 1),
('bank.branch', 'Branch Name', 'System', 'Branch name', 'Kathmandu Branch', 'text', 1);

SELECT 'DAS Template tables updated successfully!' AS Status;
