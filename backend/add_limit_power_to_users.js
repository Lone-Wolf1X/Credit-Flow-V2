const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
});

async function migrate() {
    try {
        console.log('Adding limit_power to users table...');
        
        await pool.query("ALTER TABLE users ADD COLUMN IF NOT EXISTS limit_power NUMERIC DEFAULT 0");
        
        console.log('Column limit_power added successfully to users table.');
        console.log('Migration completed.');
    } catch (err) {
        console.error('Migration failed:', err);
    } finally {
        await pool.end();
    }
}

migrate();
