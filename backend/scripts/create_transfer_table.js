const db = require('../src/db');

async function createTransferTable() {
    try {
        await db.query(`
            CREATE TABLE IF NOT EXISTS transfer_requests (
                id SERIAL PRIMARY KEY,
                user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
                target_branch_id INTEGER REFERENCES branches(id),
                target_province_id INTEGER REFERENCES provinces(id),
                reason TEXT,
                status VARCHAR(20) DEFAULT 'Pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        `);
        console.log('Transfer requests table created successfully');
        process.exit(0);
    } catch (err) {
        console.error('Error creating transfer table:', err);
        process.exit(1);
    }
}

createTransferTable();
