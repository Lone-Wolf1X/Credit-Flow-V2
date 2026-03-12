const bcrypt = require('bcrypt');
const db = require('./src/db');

const seed = async () => {
    try {
        // 1. Seed Branches (10 dummy branches)
        const branches = [
            ['Kathmandu Main', 'KTM-MAIN', '001', 'Kathmandu', 'Bagmati'],
            ['Lalitpur Branch', 'LAL-BR', '002', 'Lalitpur', 'Bagmati'],
            ['Pokhara Lakeside', 'POK-LKS', '003', 'Pokhara', 'Gandaki'],
            ['Biratnagar City', 'BRT-CTY', '004', 'Biratnagar', 'Koshi'],
            ['Butwal Central', 'BUT-CEN', '005', 'Butwal', 'Lumbini'],
            ['Nepalgunj Gate', 'NPJ-GTE', '006', 'Nepalgunj', 'Lumbini'],
            ['Dhangadhi West', 'DHA-WST', '007', 'Dhangadhi', 'Sudurpashchim'],
            ['Janakpur Dham', 'JAN-DHM', '008', 'Janakpur', 'Madhesh'],
            ['Hetauda Industrial', 'HET-IND', '009', 'Hetauda', 'Bagmati'],
            ['Itahari Junction', 'ITA-JNC', '010', 'Itahari', 'Koshi']
        ];

        for (const b of branches) {
            await db.query(
                'INSERT INTO branches (name, short_name, sol_id, location, province) VALUES ($1, $2, $3, $4, $5) ON CONFLICT (sol_id) DO NOTHING',
                b
            );
        }
        console.log('Branches seeded.');

        // 2. Seed Users
        const salt = await bcrypt.genSalt(10);
        const defaultHash = await bcrypt.hash('admin', salt);

        const users = [
            ['Next Gen Innovation Nepal', 'nextgeninnovationsprivatelimit@gmail.com', 'ADMIN-001', defaultHash, 'Admin', 1, 'Systems Administrator', 1000000000],
            ['Abhi Pwn', 'abhi.pwn000@gmail.com', '100', defaultHash, 'User', 1, 'Branch Manager', 2500000],
            ['Abhi Pwn 2020', 'abhi.pwn2020@gmail.com', '101', defaultHash, 'User', 2, 'Credit Officer', 0],
            ['Nishu Mgr', 'nishu.mgr2021@gmail.com', '102', defaultHash, 'User', 3, 'Assistant', 0],
            ['Rekha Pwn', 'rekha.pwn123@gmail.com', '103', defaultHash, 'User', 4, 'Province Head', 100000000]
        ];

        for (const u of users) {
            await db.query(
                'INSERT INTO users (name, email, staff_id, password, role, branch_id, designation, limit_power, must_reset_password) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, TRUE) ON CONFLICT (email) DO NOTHING',
                u
            );
        }
        console.log('Users seeded.');

        process.exit(0);
    } catch (err) {
        console.error('Seeding failed:', err);
        process.exit(1);
    }
};

seed();
