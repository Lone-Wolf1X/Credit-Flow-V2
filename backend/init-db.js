const { Pool } = require('pg');
const fs = require('fs');
const path = require('path');
require('dotenv').config();

const pool = new Pool({
    connectionString: process.env.DATABASE_URL.replace('/credit_flow', '/postgres'), // Connect to 'postgres' first to create the DB
});

const initDb = async () => {
    try {
        // 1. Create the database if it doesn't exist
        console.log('Checking if database "credit_flow" exists...');
        const dbCheck = await pool.query("SELECT 1 FROM pg_database WHERE datname = 'credit_flow'");
        if (dbCheck.rows.length === 0) {
            await pool.query('CREATE DATABASE credit_flow');
            console.log('Database "credit_flow" created.');
        } else {
            console.log('Database "credit_flow" already exists.');
        }
        await pool.end();

        // 2. Connect to the new database to create tables
        const creditFlowPool = new Pool({
            connectionString: process.env.DATABASE_URL,
        });

        console.log('Reading schema.sql...');
        const schema = fs.readFileSync(path.join(__dirname, 'schema.sql'), 'utf8');

        console.log('Initializing database structure...');
        await creditFlowPool.query(schema);
        console.log('Database structure initialized successfully.');

        await creditFlowPool.end();
        process.exit(0);
    } catch (err) {
        console.error('Database initialization failed:', err);
        process.exit(1);
    }
};

initDb();
