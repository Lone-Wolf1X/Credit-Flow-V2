USE das_db;

-- Create branch_profiles table
CREATE TABLE IF NOT EXISTS branch_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sol_id VARCHAR(20) NOT NULL,
    province VARCHAR(100) NULL,
    district VARCHAR(100) NULL,
    local_body VARCHAR(100) NULL COMMENT 'Municipality/VDC',
    ward_no VARCHAR(20) NULL,
    location_name VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_sol (sol_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;