const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
});

async function checkUsersSchema() {
    try {
        const res = await pool.query(`
            SELECT column_name, data_type, is_nullable, column_default
            FROM information_schema.columns
            WHERE table_name = 'users'
            ORDER BY column_name
        `);
        console.log('Columns in users table:');
        console.log(JSON.stringify(res.rows, null, 2));

        const userRes = await pool.query('SELECT id, name, must_reset_password FROM users LIMIT 5');
        console.log('\nSample users:');
        console.log(JSON.stringify(userRes.rows, null, 2));
    } catch (err) {
        console.error(err);
    } finally {
        await pool.end();
    }
}

checkUsersSchema();
