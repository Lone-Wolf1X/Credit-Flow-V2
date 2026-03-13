const db = require('./src/db');
const fs = require('fs');

async function checkData() {
    try {
        const designations = await db.query('SELECT * FROM designations ORDER BY default_power_limit ASC');
        const branches = await db.query('SELECT * FROM branches ORDER BY sol_id ASC');
        
        const output = {
            designations: designations.rows,
            branches: branches.rows
        };

        fs.writeFileSync('db_data_dump.json', JSON.stringify(output, null, 2));
        console.log('Data dumped to db_data_dump.json');
        process.exit(0);
    } catch (err) {
        console.error(err);
        process.exit(1);
    }
}

checkData();
