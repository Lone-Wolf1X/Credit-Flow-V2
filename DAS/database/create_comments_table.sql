CREATE TABLE IF NOT EXISTS profile_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_profile_id INT NOT NULL,
    user_id INT NOT NULL, /* Linked to users table */
    comment_text TEXT NOT NULL,
    stage ENUM('Submission', 'Verification', 'Return', 'Rejection', 'Approval') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles(id) ON DELETE CASCADE
);
