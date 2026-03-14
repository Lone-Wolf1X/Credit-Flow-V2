const db = require('./index');

const migrate = async () => {
    try {
        console.log('Starting migration: Making lead_id nullable in CIC tables...');

        await db.query(`
            ALTER TABLE cic_requests ALTER COLUMN lead_id DROP NOT NULL;
            ALTER TABLE cic_personal ALTER COLUMN lead_id DROP NOT NULL;
            ALTER TABLE cic_institutional ALTER COLUMN lead_id DROP NOT NULL;
            ALTER TABLE cic_credit_facilities ALTER COLUMN lead_id DROP NOT NULL;
        `);

        console.log('Migration completed successfully.');
        process.exit(0);
    } catch (err) {
        console.error('Migration failed:', err);
        process.exit(1);
    }
};

migrate();
