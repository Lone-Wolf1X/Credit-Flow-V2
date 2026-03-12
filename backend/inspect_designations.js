const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
});

async function inspect() {
    try {
        const res = await pool.query(`
            SELECT column_name, data_type, is_nullable
            FROM information_schema.columns
            WHERE table_name = 'designations'
        `);
        console.log('Designations columns:', JSON.stringify(res.rows, null, 2));
    } catch (err) {
        console.error('Inspection failed:', err);
    } finally {
        await pool.end();
    }
}

inspect();
