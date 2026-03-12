-- Add admin_messages table for banner messages
USE das_db;

CREATE TABLE IF NOT EXISTS admin_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
    is_active TINYINT(1) DEFAULT 1,
    start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    end_date DATETIME NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_active (is_active),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert a sample admin message
INSERT INTO admin_messages (title, message, message_type, created_by) VALUES
('Welcome to DAS!', 'Document Automation System is now live. Please ensure all customer profiles are complete before generating documents.', 'info', 1);
