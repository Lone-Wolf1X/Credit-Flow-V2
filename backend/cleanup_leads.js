const db = require('./src/db');

async function cleanup() {
    try {
        console.log('Cleaning up leads data...');
        // Cascade will delete lead_interactions and other foreign key dependencies
        await db.query('TRUNCATE TABLE leads CASCADE');
        await db.query("DELETE FROM notifications WHERE message LIKE '%Lead %'");
        console.log('SUCCESS: All lead data cleared. Ready for fresh testing.');
        process.exit(0);
    } catch (err) {
        console.error('ERROR during cleanup:', err);
        process.exit(1);
    }
}

cleanup();
