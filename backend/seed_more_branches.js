const db = require('./src/db');

async function seedBranches() {
    const branches = [
        ['Pokhara Branch', 'POK-BR', '002', 'Pokhara', 4], // Gandaki
        ['Biratnagar Branch', 'BRT-BR', '003', 'Biratnagar', 1], // Koshi
        ['Butwal Branch', 'BUT-BR', '004', 'Butwal', 5], // Lumbini
        ['Nepalgunj Branch', 'NPJ-BR', '005', 'Nepalgunj', 5], // Lumbini
        ['Dhangadhi Branch', 'DHA-BR', '006', 'Dhangadhi', 7], // Sudurpashchim
        ['Birgunj Branch', 'BIR-BR', '007', 'Birgunj', 2], // Madhesh
        ['Janakpur Branch', 'JAN-BR', '008', 'Janakpur', 2], // Madhesh
        ['Hetauda Branch', 'HET-BR', '009', 'Hetauda', 3], // Bagmati
        ['Itahari Branch', 'ITA-BR', '010', 'Itahari', 1] // Koshi
    ];

    try {
        console.log('Seeding branches...');
        for (const b of branches) {
            await db.query(
                'INSERT INTO branches (name, short_name, sol_id, location, province_id) VALUES ($1, $2, $3, $4, $5) ON CONFLICT (sol_id) DO NOTHING',
                b
            );
        }
        console.log('Branches seeded successfully.');
        process.exit(0);
    } catch (err) {
        console.error('Seeding failed:', err);
        process.exit(1);
    }
}

seedBranches();
