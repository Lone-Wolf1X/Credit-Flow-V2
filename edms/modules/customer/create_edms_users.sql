-- Create edms_users table
CREATE TABLE IF NOT EXISTS edms_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    designation VARCHAR(100) DEFAULT 'Staff',
    role ENUM('Maker', 'Checker', 'Admin') NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Truncate to reset if exists (for testing)
TRUNCATE TABLE edms_users;

-- Insert Default Users (Password is '123')
-- Hash: $2y$10$YourHashHere... using PHP default cost for '123'
-- We will use a known hash for '123': $2y$10$XwHs.s3.q.F/M.l/w.c/..
-- Actually, let's just use the PHP script to insert with password_hash() to be safe.
