const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
});

async function checkSchema() {
    try {
        const res = await pool.query(`
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_name = 'leads';
        `);
        console.log('Columns in leads table:');
        res.rows.forEach(row => console.log(`- ${row.column_name}`));
    } catch (err) {
        console.error('Error checking schema:', err.message);
    } finally {
        await pool.end();
    }
}

checkSchema();
