const db = require('./src/db');

async function updateDesignations() {
    const limits = [
        { name: 'Assistant', limit: 0 }, // Assist only
        { name: 'Relationship Manager', limit: 1000000 }, // 10 Lac
        { name: 'Branch Manager', limit: 2500000 }, // 25 Lac
        { name: 'Province Head', limit: 100000000 }, // 10 Crore
        { name: 'Credit Head', limit: 500000000 }, // 50 Crore
        { name: 'CEO', limit: 9999999999 } // Beyond
    ];

    try {
        for (const d of limits) {
            await db.query(
                'INSERT INTO designations (name, default_power_limit) VALUES ($1, $2) ON CONFLICT (name) DO UPDATE SET default_power_limit = $2',
                [d.name, d.limit]
            );
            console.log(`Updated ${d.name} to ${d.limit}`);
        }
    } catch (e) {
        console.error(e);
    } finally {
        process.exit();
    }
}

updateDesignations();
