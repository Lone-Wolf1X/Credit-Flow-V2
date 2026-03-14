const db = require('./src/db');

async function seedAppraisalConfig() {
    try {
        console.log('Seeding CAP ID configurations...');
        
        const capConfigs = [
            // Retail Segment
            ['Retail', 'Personal Term Loan', 'PML', 0],
            ['Retail', 'Mortgage Plus Overdraft Loan', 'MPOD', 0],
            ['Retail', 'Hire Purchase Loan', 'HPL', 0],
            ['Retail', 'Housing Loan', 'HSL', 0],
            ['Retail', 'Professional Loan', 'PROFL', 0],
            ['Retail', 'Loan Against Fixed Deposit Receipt', 'LAFDR', 0],
            
            // Corporate Segment
            ['Corporate', 'Working Term Loan', 'WTL', 0],
            ['Corporate', 'Mortgage Term Loan', 'MTL', 0],
            ['Corporate', 'Deprived Sector Lending', 'DSL', 0],
            ['Corporate', 'Micro Loan', 'MCOR', 0],
            
            // SME / MSME
            ['SME/MSME', 'SME Business Loan', 'SME', 0],
            ['SME/MSME', 'MSME Loan', 'MSME', 0]
        ];

        for (const config of capConfigs) {
            await db.query(
                'INSERT INTO cap_id_config (loan_segment, loan_type, prefix, counter) VALUES ($1, $2, $3, $4) ON CONFLICT (loan_segment, loan_type) DO UPDATE SET prefix = EXCLUDED.prefix',
                config
            );
        }

        console.log('Seeding escalation matrix...');
        const segments = ['Retail', 'Corporate', 'SME/MSME'];
        const escalationMatrix = [];
        
        // Populate matrix for all defined cap configs
        for (const config of capConfigs) {
            escalationMatrix.push([config[0], config[1], 'Staff', 'Branch Manager', 'Central Head']);
        }

        for (const entry of escalationMatrix) {
            await db.query(
                'INSERT INTO escalation_matrix (loan_segment, loan_type, initiator_designation, reviewer_designation, approver_designation) VALUES ($1, $2, $3, $4, $5) ON CONFLICT (loan_segment, loan_type) DO NOTHING',
                entry
            );
        }

        console.log('SUCCESS: Appraisal configurations (Segments, Products, Prefixes) seeded.');
        process.exit(0);
    } catch (err) {
        console.error('Seeding failed:', err);
        process.exit(1);
    }
}

seedAppraisalConfig();
