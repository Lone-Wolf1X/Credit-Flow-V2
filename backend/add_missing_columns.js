const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
});

async function migrate() {
    try {
        console.log('Starting migration...');
        
        // Add repayment_type
        await pool.query("ALTER TABLE leads ADD COLUMN IF NOT EXISTS repayment_type VARCHAR(50) DEFAULT 'Monthly'");
        console.log('Added repayment_type');

        // Add observation_end_date
        await pool.query("ALTER TABLE leads ADD COLUMN IF NOT EXISTS observation_end_date TIMESTAMP");
        console.log('Added observation_end_date');

        // Add performance_status
        await pool.query("ALTER TABLE leads ADD COLUMN IF NOT EXISTS performance_status VARCHAR(50)");
        console.log('Added performance_status');

        // Add points_calculated
        await pool.query("ALTER TABLE leads ADD COLUMN IF NOT EXISTS points_calculated BOOLEAN DEFAULT FALSE");
        console.log('Added points_calculated');

        // Add current_handler_id
        await pool.query("ALTER TABLE leads ADD COLUMN IF NOT EXISTS current_handler_id INTEGER REFERENCES users(id)");
        console.log('Added current_handler_id');

        // Add customer_email
        await pool.query("ALTER TABLE leads ADD COLUMN IF NOT EXISTS customer_email VARCHAR(255)");
        console.log('Added customer_email');

        // Add loan_purpose
        await pool.query("ALTER TABLE leads ADD COLUMN IF NOT EXISTS loan_purpose TEXT");
        console.log('Added loan_purpose');

        // Add lead_details
        await pool.query("ALTER TABLE leads ADD COLUMN IF NOT EXISTS lead_details TEXT");
        console.log('Added lead_details');

        // Add is_draft
        await pool.query("ALTER TABLE leads ADD COLUMN IF NOT EXISTS is_draft BOOLEAN DEFAULT FALSE");
        console.log('Added is_draft');

        // Add last_action
        await pool.query("ALTER TABLE leads ADD COLUMN IF NOT EXISTS last_action VARCHAR(50)");
        console.log('Added last_action');

        console.log('Migration completed successfully.');
    } catch (err) {
        console.error('Migration failed:', err);
    } finally {
        await pool.end();
    }
}

migrate();
