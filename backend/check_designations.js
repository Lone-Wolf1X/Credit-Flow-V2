const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
});

async function check() {
    try {
        const res = await pool.query('SELECT * FROM designations');
        console.log('Current Designations:', JSON.stringify(res.rows, null, 2));
    } catch (err) {
        console.error('Check failed:', err);
    } finally {
        await pool.end();
    }
}

check();
