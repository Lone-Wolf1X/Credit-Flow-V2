-- Create loan schemes system for DAS (handles existing columns)
USE das_db;

-- Create loan_schemes table
CREATE TABLE IF NOT EXISTS loan_schemes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scheme_name VARCHAR(200) NOT NULL UNIQUE,
    scheme_code VARCHAR(50) NOT NULL UNIQUE,
    scheme_type ENUM('Home Loan', 'Vehicle Loan', 'Personal Loan', 'Business Loan', 'Agriculture Loan', 'Education Loan', 'Other') NOT NULL,
    description TEXT,
    interest_rate DECIMAL(5,2),
    max_tenure_months INT,
    max_loan_amount DECIMAL(15,2),
    min_loan_amount DECIMAL(15,2),
    template_folder_path VARCHAR(500),
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_scheme_code (scheme_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample loan schemes (only if table is empty)
INSERT IGNORE INTO loan_schemes (scheme_name, scheme_code, scheme_type, description, interest_rate, max_tenure_months, max_loan_amount, min_loan_amount, template_folder_path, created_by) VALUES
('Home Loan - Regular Scheme', 'HL_REGULAR', 'Home Loan', 'Standard home loan for residential property purchase with competitive interest rates', 10.50, 240, 10000000.00, 100000.00, 'templates/home_loan_regular', 1),
('Vehicle Loan - Two Wheeler', 'VL_2W', 'Vehicle Loan', 'Loan for purchasing two-wheeler vehicles (bikes, scooters)', 12.00, 60, 500000.00, 50000.00, 'templates/vehicle_loan_2w', 1),
('Vehicle Loan - Four Wheeler', 'VL_4W', 'Vehicle Loan', 'Loan for purchasing four-wheeler vehicles (cars, SUVs)', 11.50, 84, 2000000.00, 200000.00, 'templates/vehicle_loan_4w', 1),
('Personal Loan - Salaried', 'PL_SALARIED', 'Personal Loan', 'Personal loan for salaried individuals with regular income', 13.50, 60, 1000000.00, 50000.00, 'templates/personal_loan_salaried', 1),
('Business Loan - MSME', 'BL_MSME', 'Business Loan', 'Loan for Micro, Small and Medium Enterprises for business expansion', 11.00, 120, 5000000.00, 100000.00, 'templates/business_loan_msme', 1),
('Education Loan - Higher Studies', 'EL_HIGHER', 'Education Loan', 'Loan for higher education and professional courses', 9.50, 180, 2000000.00, 100000.00, 'templates/education_loan_higher', 1),
('Agriculture Loan - Farming', 'AL_FARMING', 'Agriculture Loan', 'Loan for agricultural activities and farming equipment', 8.00, 60, 1000000.00, 50000.00, 'templates/agriculture_loan_farming', 1);

SELECT 'Loan schemes created successfully!' AS Status;
SELECT COUNT(*) AS 'Total Schemes' FROM loan_schemes;
