const db = require('./src/db');
async function updateLeads() {
    try {
        const result = await db.query("UPDATE leads SET status = 'Analysis' WHERE status = 'Draft' RETURNING *");
        console.log('Updated', result.rowCount, 'leads');
        process.exit(0);
    } catch (err) {
        console.error(err);
        process.exit(1);
    }
}
updateLeads();
