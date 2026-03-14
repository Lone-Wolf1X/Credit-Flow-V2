const db = require('./src/db');

const migrate = async () => {
    try {
        console.log('Starting migration: Adding start_date and expiry_date to valuators...');
        const query = `
            ALTER TABLE valuators 
            ADD COLUMN IF NOT EXISTS start_date DATE,
            ADD COLUMN IF NOT EXISTS expiry_date DATE;
        `;
        await db.query(query);
        console.log('Migration completed successfully.');
        process.exit(0);
    } catch (err) {
        console.error('Migration failed:', err);
        process.exit(1);
    }
};

migrate();
