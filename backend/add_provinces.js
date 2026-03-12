const db = require('./src/db');

async function migrate() {
    try {
        console.log('Starting Province Migration...');

        // 1. Create provinces table
        await db.query(`
            CREATE TABLE IF NOT EXISTS provinces (
                id SERIAL PRIMARY KEY,
                name VARCHAR(100) UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        `);

        // 2. Insert provinces
        const provinces = [
            'Koshi Province', 'Madhesh Province', 'Bagmati Province', 
            'Gandaki Province', 'Lumbini Province', 'Karnali Province', 
            'Sudurpashchim Province'
        ];

        for (const p of provinces) {
            await db.query('INSERT INTO provinces (name) VALUES ($1) ON CONFLICT DO NOTHING', [p]);
        }

        // 3. Add province_id to branches and users
        await db.query('ALTER TABLE branches ADD COLUMN IF NOT EXISTS province_id INTEGER REFERENCES provinces(id)');
        await db.query('ALTER TABLE users ADD COLUMN IF NOT EXISTS province_id INTEGER REFERENCES provinces(id)');
        
        // 4. Update leads table to include province_id for easier filtering
        await db.query('ALTER TABLE leads ADD COLUMN IF NOT EXISTS province_id INTEGER REFERENCES provinces(id)');

        console.log('SUCCESS: Province migration completed.');
        process.exit(0);
    } catch (err) {
        console.error('Migration failed:', err);
        process.exit(1);
    }
}

migrate();
