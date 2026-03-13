const db = require('./src/db');

async function migrate() {
    try {
        console.log('Migrating lead_appraisals table...');
        
        await db.query(`
            ALTER TABLE lead_appraisals 
            ADD COLUMN IF NOT EXISTS appraisal_status VARCHAR(50) DEFAULT 'Draft',
            ADD COLUMN IF NOT EXISTS borrower_details JSONB DEFAULT '{}',
            ADD COLUMN IF NOT EXISTS income_details JSONB DEFAULT '{}',
            ADD COLUMN IF NOT EXISTS collateral_details JSONB DEFAULT '{}',
            ADD COLUMN IF NOT EXISTS risk_assessment JSONB DEFAULT '{}',
            ADD COLUMN IF NOT EXISTS pricing_details JSONB DEFAULT '{}',
            ADD COLUMN IF NOT EXISTS group_exposure JSONB DEFAULT '{}';
        `);

        // Also add a table for CRA/Retail Scoring if needed, 
        // but for now, we can store it in risk_assessment JSONB.
        
        console.log('Migration completed successfully.');
        process.exit(0);
    } catch (err) {
        console.error('Migration failed:', err);
        process.exit(1);
    }
}

migrate();
