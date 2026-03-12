-- Add Parent Profile ID and Application Type for Renewal Workflow
ALTER TABLE customer_profiles
ADD COLUMN parent_profile_id INT DEFAULT NULL,
ADD COLUMN application_type ENUM(
    'New',
    'Renewal',
    'Enhancement',
    'Renewal with Enhancement',
    'Reduction',
    'Renewal with Reduction'
) DEFAULT 'New';

-- Add index for faster lookup of history
ALTER TABLE customer_profiles
ADD INDEX idx_parent_profile (parent_profile_id);

-- Add foreign key constraint (optional but recommended)
-- ALTER TABLE customer_profiles
-- ADD CONSTRAINT fk_parent_profile FOREIGN KEY (parent_profile_id) REFERENCES customer_profiles(id) ON DELETE SET NULL;