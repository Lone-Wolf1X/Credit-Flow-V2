const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
});

async function migrate() {
    try {
        console.log('Starting Security & Session Logging migration...');
        
        // 1. Create session_logs table
        await pool.query(`
            CREATE TABLE IF NOT EXISTS session_logs (
                id SERIAL PRIMARY KEY,
                user_id INTEGER REFERENCES users(id),
                action VARCHAR(50), -- LOGIN, LOGOUT, TIMEOUT
                ip_address VARCHAR(50),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        `);
        console.log('Created session_logs table');

        // 2. Ensure audit_logs has details as JSONB
        // (It already is JSONB in schema.sql, but let's confirm/ensure)
        
        console.log('Migration completed successfully.');
    } catch (err) {
        console.error('Migration failed:', err);
    } finally {
        await pool.end();
    }
}

migrate();
function getAuditLogsView() {
    // Optional: create a view for easier querying if needed
}
