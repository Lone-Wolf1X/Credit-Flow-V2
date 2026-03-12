-- Update branch_profiles with Nepali Address Fields
ALTER TABLE `branch_profiles`
ADD COLUMN `province_id` INT DEFAULT NULL AFTER `branch_address`,
ADD COLUMN `district_id` INT DEFAULT NULL AFTER `province_id`,
ADD COLUMN `local_body_np` VARCHAR(100) DEFAULT NULL COMMENT 'e.g. मिर्चैया न.पा' AFTER `district_id`,
ADD COLUMN `ward_no_np` VARCHAR(10) DEFAULT NULL COMMENT 'e.g. ६' AFTER `local_body_np`,
ADD COLUMN `branch_name_np` VARCHAR(100) DEFAULT NULL COMMENT 'e.g. मिर्चैया' AFTER `branch_name`,
ADD COLUMN `address_np` VARCHAR(255) DEFAULT NULL COMMENT 'Full Nepali Address String' AFTER `branch_address`;

-- Create User Transfer Table
CREATE TABLE IF NOT EXISTS `user_branch_transfers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `from_branch_id` INT NOT NULL,
    `to_branch_id` INT NOT NULL,
    `reason` TEXT,
    `status` ENUM(
        'Pending',
        'Approved',
        'Rejected'
    ) DEFAULT 'Pending',
    `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `approved_at` TIMESTAMP NULL,
    `approved_by` INT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
    FOREIGN KEY (`from_branch_id`) REFERENCES `branch_profiles` (`id`),
    FOREIGN KEY (`to_branch_id`) REFERENCES `branch_profiles` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;