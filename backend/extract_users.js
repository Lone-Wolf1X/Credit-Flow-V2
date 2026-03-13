const { Pool } = require('pg');
require('dotenv').config();
const fs = require('fs');

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
});

async function extract() {
    try {
        const res = await pool.query('SELECT * FROM users');
        fs.writeFileSync('users_dump.json', JSON.stringify(res.rows, null, 2));
        console.log('Extraction complete. Data saved to users_dump.json');
    } catch (err) {
        console.error(err);
    } finally {
        await pool.end();
    }
}

extract();
