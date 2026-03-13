const db = require('./src/db');

async function addDesignation() {
    try {
        await db.query(
            "INSERT INTO designations (name, default_power_limit) VALUES ('Deputy CEO', 1000000000) ON CONFLICT (name) DO NOTHING"
        );
        console.log('Deputy CEO designation added.');
        process.exit(0);
    } catch (err) {
        console.error(err);
        process.exit(1);
    }
}

addDesignation();
