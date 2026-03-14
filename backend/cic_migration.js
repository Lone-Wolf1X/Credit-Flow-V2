const db = require('./src/db');

const migrate = async () => {
    try {
        console.log('Starting migration: Creating CIC Tables...');

        // 1. Update Users table for Generator Role
        await db.query(`
            ALTER TABLE users 
            ADD COLUMN IF NOT EXISTS is_cic_generator BOOLEAN DEFAULT FALSE;
        `);

        // 2. Permission Requests Table
        await db.query(`
            CREATE TABLE IF NOT EXISTS permission_requests (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                permission_type VARCHAR(50) NOT NULL, -- 'CIC_Generator'
                reason TEXT,
                status VARCHAR(20) DEFAULT 'Pending', -- Pending, Approved, Rejected
                reviewed_by INTEGER REFERENCES users(id),
                reviewed_at TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        `);

        // 3. Personal CIC Table (Expanded)
        await db.query(`
            CREATE TABLE IF NOT EXISTS cic_personal (
                id SERIAL PRIMARY KEY,
                lead_id VARCHAR(50) REFERENCES leads(lead_id) ON DELETE CASCADE,
                ref_type VARCHAR(50) DEFAULT 'Borrower',
                full_name VARCHAR(255) NOT NULL,
                date_of_birth DATE,
                gender VARCHAR(20),
                relationship_status VARCHAR(50),
                father_name VARCHAR(255),
                grandfather_name VARCHAR(255),
                spouse_name VARCHAR(255),
                citizenship_number VARCHAR(100),
                citizenship_issued_district VARCHAR(100),
                citizenship_issued_date DATE,
                id_issue_authority VARCHAR(255),
                id_reissue_date DATE,
                reissue_count INTEGER DEFAULT 0,
                pan_number VARCHAR(100),
                permanent_province VARCHAR(100),
                permanent_district VARCHAR(100),
                permanent_municipality VARCHAR(100),
                permanent_ward VARCHAR(10),
                permanent_street VARCHAR(255),
                current_province VARCHAR(100),
                current_district VARCHAR(100),
                current_municipality VARCHAR(100),
                current_ward VARCHAR(10),
                current_street VARCHAR(255),
                contact_number VARCHAR(100),
                email VARCHAR(255),
                occupation VARCHAR(100),
                family_members_count INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        `);

        // 4. Institutional CIC Table (Expanded)
        await db.query(`
            CREATE TABLE IF NOT EXISTS cic_institutional (
                id SERIAL PRIMARY KEY,
                lead_id VARCHAR(50) REFERENCES leads(lead_id) ON DELETE CASCADE,
                business_name VARCHAR(255) NOT NULL,
                registration_number VARCHAR(100),
                registration_date DATE,
                registration_authority VARCHAR(255),
                registration_type VARCHAR(100),
                pan_vat_number VARCHAR(100),
                pan_issue_date DATE,
                pan_issue_authority VARCHAR(255),
                firm_registration_authority VARCHAR(255),
                business_type VARCHAR(100),
                registered_province VARCHAR(100),
                registered_district VARCHAR(100),
                registered_municipality VARCHAR(100),
                registered_ward VARCHAR(10),
                registered_street VARCHAR(255),
                operating_province VARCHAR(100),
                operating_district VARCHAR(100),
                operating_municipality VARCHAR(100),
                operating_ward VARCHAR(10),
                operating_street VARCHAR(255),
                contact_number VARCHAR(100),
                email VARCHAR(255),
                main_activities TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        `);

        // 5. Mapping Personal (Directors/Proprietors) to Institutional
        await db.query(`
            CREATE TABLE IF NOT EXISTS cic_directors (
                id SERIAL PRIMARY KEY,
                institutional_id INTEGER REFERENCES cic_institutional(id) ON DELETE CASCADE,
                personal_id INTEGER REFERENCES cic_personal(id) ON DELETE CASCADE,
                designation VARCHAR(100),
                share_percentage NUMERIC(5,2) DEFAULT 0
            );
        `);

        // 6. CIC Workflow Requests (Refined)
        await db.query(`
            CREATE TABLE IF NOT EXISTS cic_requests (
                id SERIAL PRIMARY KEY,
                lead_id VARCHAR(50) REFERENCES leads(lead_id) ON DELETE CASCADE,
                initiator_id INTEGER NOT NULL REFERENCES users(id),
                branch_id INTEGER NOT NULL REFERENCES branches(id),
                processor_id INTEGER REFERENCES users(id),
                status VARCHAR(50) DEFAULT 'Draft', -- Draft, Submitted, Processing, Completed, Returned
                report_url TEXT,
                initiator_docs_url JSONB DEFAULT '[]', -- Array of {name, url}
                initiator_comment TEXT,
                processor_comment TEXT,
                total_charge NUMERIC(18,2) DEFAULT 0,
                vat_amount NUMERIC(18,2) DEFAULT 0,
                transaction_id VARCHAR(100),
                payment_status VARCHAR(20) DEFAULT 'Unpaid', -- Unpaid, Paid
                remarks TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        `);

        // 7. Entities per CIC Request
        await db.query(`
            CREATE TABLE IF NOT EXISTS cic_request_entities (
                id SERIAL PRIMARY KEY,
                request_id INTEGER REFERENCES cic_requests(id) ON DELETE CASCADE,
                entity_type VARCHAR(20) NOT NULL, -- 'Personal', 'Institutional'
                entity_id INTEGER NOT NULL,
                is_hit BOOLEAN DEFAULT FALSE,
                base_charge NUMERIC(18,2) DEFAULT 250,
                vat_amount NUMERIC(18,2) DEFAULT 32.50
            );
        `);

        // 6. Credit Facilities (Other BFIs)
        await db.query(`
            CREATE TABLE IF NOT EXISTS cic_credit_facilities (
                id SERIAL PRIMARY KEY,
                lead_id VARCHAR(50) NOT NULL REFERENCES leads(lead_id) ON DELETE CASCADE,
                entity_id INTEGER NOT NULL,
                entity_type VARCHAR(20) NOT NULL,
                bfi_name VARCHAR(255) NOT NULL,
                facility_type VARCHAR(100),
                sanctioned_amount NUMERIC(18,2),
                outstanding_amount NUMERIC(18,2),
                repayment_status VARCHAR(50),
                is_funded BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        `);

        console.log('CIC Tables created successfully.');
        process.exit(0);
    } catch (err) {
        console.error('Migration failed:', err);
        process.exit(1);
    }
};

migrate();
