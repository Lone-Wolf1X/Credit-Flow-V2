const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
});

async function migrate() {
    try {
        console.log('Starting Designations migration...');
        
        // 1. Create designations table
        await pool.query(`
            CREATE TABLE IF NOT EXISTS designations (
                id SERIAL PRIMARY KEY,
                name VARCHAR(100) UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        `);
        console.log('Created designations table');

        // 2. Seed default designations with default power limits
        const defaultDesignations = [
            { name: 'Assistant', limit: 500000 },
            { name: 'BM', limit: 2500000 },
            { name: 'PH', limit: 10000000 },
            { name: 'CH', limit: 50000000 },
            { name: 'RM', limit: 0 },
            { name: 'Supervisor', limit: 0 },
            { name: 'Admin', limit: 999999999 }
        ];
        
        for (const { name, limit } of defaultDesignations) {
            await pool.query(
                'INSERT INTO designations (name, default_power_limit) VALUES ($1, $2) ON CONFLICT (name) DO NOTHING',
                [name, limit]
            );
        }
        console.log('Seeded default designations');

        console.log('Migration completed successfully.');
    } catch (err) {
        console.error('Migration failed:', err);
    } finally {
        await pool.end();
    }
}

migrate();
