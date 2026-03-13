const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
});

async function migrate() {
    try {
        console.log('Starting Lead Reviews Schema Extension...');
        
        // Add assessment columns to lead_reviews
        await pool.query("ALTER TABLE lead_reviews ADD COLUMN IF NOT EXISTS income_assessment VARCHAR(50)");
        await pool.query("ALTER TABLE lead_reviews ADD COLUMN IF NOT EXISTS collateral_assessment VARCHAR(50)");
        await pool.query("ALTER TABLE lead_reviews ADD COLUMN IF NOT EXISTS identity_assessment VARCHAR(50)");
        await pool.query("ALTER TABLE lead_reviews ADD COLUMN IF NOT EXISTS other_assessment VARCHAR(50)");
        
        console.log('Lead_reviews table updated with assessment columns.');
        console.log('Migration completed successfully.');
    } catch (err) {
        console.error('Migration failed:', err);
    } finally {
        await pool.end();
    }
}

migrate();
