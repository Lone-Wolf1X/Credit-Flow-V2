const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
});

async function migrate() {
    try {
        console.log('Starting role simplification migration (version 2)...');
        
        // 1. Drop existing role constraints using information_schema
        const constraintRes = await pool.query(`
            SELECT tc.constraint_name
            FROM information_schema.table_constraints AS tc
            JOIN information_schema.check_constraints AS cc
              ON tc.constraint_name = cc.constraint_name
            WHERE tc.table_name = 'users'
              AND (cc.check_clause LIKE '%role%' OR tc.constraint_name LIKE '%role%')
        `);
        
        if (constraintRes.rows.length > 0) {
            for (const row of constraintRes.rows) {
                console.log(`Dropping constraint: ${row.constraint_name}`);
                await pool.query(`ALTER TABLE users DROP CONSTRAINT IF EXISTS "${row.constraint_name}"`);
            }
        }

        // 2. Update existing roles to 'Staff' (except Admin)
        await pool.query("UPDATE users SET role = 'Staff' WHERE role != 'Admin'");
        console.log('Updated existing roles to Staff');

        // 3. Add new check constraint
        await pool.query("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('Admin', 'Staff'))");
        console.log('Added new Admin/Staff role check constraint');

        console.log('Migration completed successfully.');
    } catch (err) {
        console.error('Migration failed:', err);
    } finally {
        await pool.end();
    }
}

migrate();
