const db = require('./src/db');

async function migrate() {
    try {
        console.log('Creating lead_appraisals table...');
        await db.query(`
            CREATE TABLE IF NOT EXISTS lead_appraisals (
                id SERIAL PRIMARY KEY,
                lead_id VARCHAR(50) REFERENCES leads(lead_id) ON DELETE CASCADE,
                monthly_income DECIMAL(15, 2),
                fair_market_value DECIMAL(15, 2),
                distress_value DECIMAL(15, 2),
                recommended_limit DECIMAL(15, 2),
                interest_rate DECIMAL(5, 2),
                tenure_months INTEGER,
                appraiser_id INTEGER REFERENCES users(id),
                appraisal_status VARCHAR(50) DEFAULT 'Draft',
                borrower_details JSONB DEFAULT '{}',
                income_details JSONB DEFAULT '{}',
                collateral_details JSONB DEFAULT '{}',
                risk_assessment JSONB DEFAULT '{}',
                pricing_details JSONB DEFAULT '{}',
                group_exposure JSONB DEFAULT '{}',
                final_recommendation JSONB DEFAULT '{}',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        `);
        console.log('Table created successfully.');
        process.exit(0);
    } catch (err) {
        console.error('Migration failed:', err);
        process.exit(1);
    }
}

migrate();
