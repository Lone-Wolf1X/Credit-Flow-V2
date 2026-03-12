const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
});

async function migrate() {
    try {
        console.log('Renaming designations to full forms...');
        
        const updates = [
            { old: 'BM', new: 'Branch Manager' },
            { old: 'PH', new: 'Province Head' },
            { old: 'CH', new: 'Credit Head' },
            { old: 'RM', new: 'Relationship Manager' }
        ];

        for (const up of updates) {
            // Update designations table
            await pool.query('UPDATE designations SET name = $1 WHERE name = $2', [up.new, up.old]);
            // Update users table (synchronize existing data)
            await pool.query('UPDATE users SET designation = $1 WHERE designation = $2', [up.new, up.old]);
        }

        console.log('Designations updated to full forms.');
    } catch (err) {
        console.error('Migration failed:', err);
    } finally {
        await pool.end();
    }
}

migrate();
