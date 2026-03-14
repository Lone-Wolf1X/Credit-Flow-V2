const { Pool } = require('pg');
require('dotenv').config({ path: require('path').join(__dirname, '../.env') });

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
});

async function seed() {
    try {
        console.log('Seeding Valuators and Policies...');

        // 1. Initial Policies
        const policies = [
            ['Retail', 'Real Estate', 50.0],
            ['Retail', 'Vehicle', 40.0],
            ['Corporate', 'Real Estate', 60.0],
            ['SME/MSME', 'Real Estate', 70.0],
            ['Retail', 'Gold/Silver', 80.0]
        ];

        for (const [seg, col, pct] of policies) {
            await pool.query(
                'INSERT INTO valuation_policies (loan_segment, collateral_type, max_financing_percentage) VALUES ($1, $2, $3) ON CONFLICT DO NOTHING',
                [seg, col, pct]
            );
        }

        // 2. Initial Payment Rules
        await pool.query(`
            INSERT INTO valuation_payment_rules (rule_type, min_loan_amount, max_loan_amount, field_charge, final_charge)
            VALUES ('Fixed', 0, 5000000, 2000, 5000) ON CONFLICT DO NOTHING
        `);

        // 3. Initial Valuators for Testing
        const valuators = [
            ['Ram Bahadur', 'Nepal Valuators Pvt Ltd', 'VAL-001', 5, '9801234567', 'ram@nepalval.com'],
            ['Sita Maya', 'Kathmandu Engineering & Valuation', 'VAL-002', 8, '9841234567', 'sita@kev.com'],
            ['Ganesh Sah', 'Terai Technical Services', 'VAL-003', 4, '9811234567', 'ganesh@tts.com']
        ];

        for (const [name, firm, lic, exp, con, em] of valuators) {
            await pool.query(
                "INSERT INTO valuators (name, firm_name, license_number, experience_years, contact_number, email, status) VALUES ($1, $2, $3, $4, $5, $6, 'Active') ON CONFLICT DO NOTHING",
                [name, firm, lic, exp, con, em]
            );
        }

        console.log('Seeding completed!');
        process.exit(0);
    } catch (err) {
        console.error('Seed error:', err);
        process.exit(1);
    }
}

seed();
