const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
});

async function migrate() {
    try {
        console.log('Starting migration...');
        await pool.query(`
            ALTER TABLE leads 
            ADD COLUMN IF NOT EXISTS collateral_type VARCHAR(50),
            ADD COLUMN IF NOT EXISTS estimated_collateral_value DECIMAL(15, 2),
            ADD COLUMN IF NOT EXISTS undivided_family_members INTEGER DEFAULT 1,
            ADD COLUMN IF NOT EXISTS is_pep BOOLEAN DEFAULT FALSE,
            ADD COLUMN IF NOT EXISTS has_legal_dispute BOOLEAN DEFAULT FALSE,
            ADD COLUMN IF NOT EXISTS primary_income DECIMAL(15, 2),
            ADD COLUMN IF NOT EXISTS secondary_income DECIMAL(15, 2),
            ADD COLUMN IF NOT EXISTS other_income_amount DECIMAL(15, 2),
            ADD COLUMN IF NOT EXISTS other_income_source VARCHAR(100);
        `);
        console.log('Migration completed successfully.');
    } catch (err) {
        console.error('Migration failed:', err.message);
    } finally {
        await pool.end();
    }
}

migrate();
