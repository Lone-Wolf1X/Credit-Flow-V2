const { Pool } = require('pg');
const bcrypt = require('bcrypt');
const fs = require('fs');
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '.env') });

const pool = new Pool({
    connectionString: process.env.DATABASE_URL
});

console.log('Connecting to database:', process.env.DATABASE_URL.replace(/:[^:]+@/, ':****@')); // Hide password

async function seed() {
    const schema = fs.readFileSync(path.join(__dirname, 'schema.sql'), 'utf8');
    const hashedPassword = await bcrypt.hash('admin', 10);

    try {
        console.log('Resetting database and applying advanced schema...');
        await pool.query(schema);

        console.log('Creating Provinces...');
        await pool.query("INSERT INTO provinces (name) VALUES ('Koshi'), ('Madhesh'), ('Bagmati'), ('Gandaki'), ('Lumbini'), ('Karnali'), ('Sudurpashchim')");
        const bagmatiRes = await pool.query("SELECT id FROM provinces WHERE name = 'Bagmati'");
        const bagmatiId = bagmatiRes.rows[0].id;

        console.log('Creating Designations...');
        await pool.query("INSERT INTO designations (name, default_power_limit) VALUES ('Assistant', 500000), ('Officer', 2000000), ('Manager', 10000000), ('Chief', 50000000)");

        console.log('Creating default branch...');
        const branchRes = await pool.query(
            "INSERT INTO branches (name, sol_id, location, province_id) VALUES ('Main Branch', '001', 'Kathmandu', $1) RETURNING id",
            [bagmatiId]
        );
        const branchId = branchRes.rows[0].id;

        console.log('Creating Admin user (ADMIN01)...');
        await pool.query(
            "INSERT INTO users (name, email, staff_id, password, role, branch_id, province_id, designation, must_reset_password) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)",
            ['Super Admin', 'admin@bank.com', 'ADMIN01', hashedPassword, 'Admin', branchId, bagmatiId, 'Chief', false]
        );

        console.log('Creating Staff users (100-104)...');
        const desigs = ['Assistant', 'Officer', 'Manager', 'Officer', 'Assistant'];
        for (let i = 100; i <= 104; i++) {
            await pool.query(
                "INSERT INTO users (name, email, staff_id, password, role, branch_id, province_id, designation) VALUES ($1, $2, $3, $4, $5, $6, $7, $8)",
                [`Staff Member ${i}`, `staff${i}@bank.com`, i.toString(), hashedPassword, 'Staff', branchId, bagmatiId, desigs[i-100]]
            );
        }

        console.log('Database seeded successfully with Advanced Credit System schema.');
        process.exit(0); // Exit successfully after seeding
    } catch (err) {
        console.error('Seeding error:', err);
        process.exit(1); // Exit with error code
    } finally {
        await pool.end();
    }
}

seed();
