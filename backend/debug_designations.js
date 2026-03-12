const db = require('./src/db');

async function check() {
    try {
        const res = await db.query('SELECT * FROM designations');
        console.table(res.rows);
    } catch (e) {
        console.error(e);
    } finally {
        process.exit();
    }
}

check();
