const db = require('./src/db');

async function seedLeads() {
    try {
        console.log('Seeding test leads...');
        
        const leads = [
            ['LEAD-1001', 'Yakub Rain', 'Individual', '9841000001', '2023-01-01', 'Kathmandu', 'Retail', 'Personal Term Loan', 'Monthly', 500000, 1, 'Analysis'],
            ['LEAD-1002', 'Bikash Thapa', 'Individual', '9841000002', '2023-02-15', 'Lalitpur', 'Retail', 'Home Loan', 'Monthly', 5000000, 1, 'Analysis'],
            ['LEAD-1003', 'Sunita Sharma', 'Business', '9841000003', '2023-03-10', 'Biratnagar', 'SME/MSME', 'Business Term Loan', 'Quarterly', 10000000, 1, 'Analysis']
        ];

        for (const l of leads) {
            await db.query(
                `INSERT INTO leads (
                    lead_id, customer_name, customer_type, contact_number, 
                    relationship_date, address, loan_segment, loan_type, 
                    repayment_type, proposed_limit, branch_id, status
                ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12) 
                ON CONFLICT (lead_id) DO NOTHING`,
                l
            );
        }

        console.log('SUCCESS: Test leads seeded.');
        process.exit(0);
    } catch (err) {
        console.error('Seeding leads failed:', err);
        process.exit(1);
    }
}

seedLeads();
