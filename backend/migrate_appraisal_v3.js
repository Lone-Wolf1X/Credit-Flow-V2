const db = require('./src/db');

async function migrate() {
    try {
        const dbName = await db.query('SELECT current_database()');
        console.log('Current Database:', dbName.rows[0].current_database);

        const tables = await db.query("SELECT schemaname, tablename FROM pg_catalog.pg_tables WHERE schemaname NOT IN ('pg_catalog', 'information_schema')");
        console.log('Available Tables:', tables.rows.map(r => `${r.schemaname}.${r.tablename}`).join(', '));

        console.log('Adding final_recommendation column...');
        await db.query(`
            ALTER TABLE lead_appraisals 
            ADD COLUMN IF NOT EXISTS final_recommendation JSONB DEFAULT '{}';
        `);
        console.log('Migration completed successfully.');
        process.exit(0);
    } catch (err) {
        console.error('Migration failed:', err);
        process.exit(1);
    }
}

migrate();
