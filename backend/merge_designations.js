const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
});

async function migrate() {
    try {
        console.log('Merging duplicate designations...');
        
        const merges = [
            { old: 'BM', new: 'Branch Manager' },
            { old: 'PH', new: 'Province Head' },
            { old: 'CH', new: 'Credit Head' }
        ];

        for (const m of merges) {
            // Update users table first
            await pool.query('UPDATE users SET designation = $1 WHERE designation = $2', [m.new, m.old]);
            // Delete old designation entry
            await pool.query('DELETE FROM designations WHERE name = $1', [m.old]);
        }

        // Rename single ones
        await pool.query('UPDATE designations SET name = $1 WHERE name = $2', ['Relationship Manager', 'RM']);
        await pool.query('UPDATE users SET designation = $1 WHERE designation = $2', ['Relationship Manager', 'RM']);

        console.log('Designations merged and renamed.');
    } catch (err) {
        console.error('Migration failed:', err);
    } finally {
        await pool.end();
    }
}

migrate();
