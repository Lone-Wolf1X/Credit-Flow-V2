const db = require('./src/db');

async function seedNepalData() {
    try {
        console.log('Starting enhanced seeding...');

        // 1. Seed Provinces
        const provinces = [
            'Koshi Province', 
            'Madhesh Province', 
            'Bagmati Province', 
            'Gandaki Province', 
            'Lumbini Province', 
            'Karnali Province', 
            'Sudurpashchim Province'
        ];

        console.log('Seeding provinces...');
        for (const p of provinces) {
            await db.query('INSERT INTO provinces (name) VALUES ($1) ON CONFLICT (name) DO NOTHING', [p]);
        }
        
        // 2. Seed Designations
        const designations = [
            { name: 'Assistant', limit: 500000 },
            { name: 'Credit Officer', limit: 1000000 },
            { name: 'Branch Manager', limit: 2500000 },
            { name: 'Province Head', limit: 10000000 },
            { name: 'Central Head', limit: 50000000 },
            { name: 'Relationship Manager', limit: 0 },
            { name: 'Supervisor', limit: 0 },
            { name: 'Admin', limit: 999999999 }
        ];

        console.log('Seeding designations...');
        for (const d of designations) {
            await db.query(
                'INSERT INTO designations (name, default_power_limit) VALUES ($1, $2) ON CONFLICT (name) DO NOTHING',
                [d.name, d.limit]
            );
        }

        // Get all provinces with IDs
        const { rows: provinceRows } = await db.query('SELECT id, name FROM provinces');
        const provinceMap = {};
        provinceRows.forEach(row => {
            provinceMap[row.name.split(' ')[0]] = row.id;
        });

        // 3. Seed Hub Hierarchy (HO -> Province -> Branch)
        
        // A. Head Office (Central Department Hub)
        const hoRes = await db.query(
            'INSERT INTO branches (name, short_name, sol_id, location, province, hub_type) VALUES ($1, $2, $3, $4, $5, $6) ON CONFLICT (sol_id) DO UPDATE SET hub_type = $6 RETURNING id',
            ['Central Credit Department', 'HO-CREDIT', 'HO001', 'Kathmandu', 'Bagmati', 'Department']
        );
        const hoId = hoRes.rows[0].id;

        // B. Province Offices (Province Hubs)
        const provinceHubs = [
            ['Koshi Province Office', 'KOSHI-PO', 'P01', 'Biratnagar', 'Koshi', provinceMap['Koshi']],
            ['Madhesh Province Office', 'MADHESH-PO', 'P02', 'Janakpur', 'Madhesh', provinceMap['Madhesh']],
            ['Bagmati Province Office', 'BAGMATI-PO', 'P03', 'Hetauda', 'Bagmati', provinceMap['Bagmati']],
            ['Gandaki Province Office', 'GANDAKI-PO', 'P04', 'Pokhara', 'Gandaki', provinceMap['Gandaki']],
            ['Lumbini Province Office', 'LUMBINI-PO', 'P05', 'Butwal', 'Lumbini', provinceMap['Lumbini']],
            ['Karnali Province Office', 'KARNALI-PO', 'P06', 'Surkhet', 'Karnali', provinceMap['Karnali']],
            ['Sudurpashchim Province Office', 'SUDUR-PO', 'P07', 'Dhangadhi', 'Sudurpashchim', provinceMap['Sudurpashchim']]
        ];

        const provinceIdMap = {};
        for (const p of provinceHubs) {
            const res = await db.query(
                'INSERT INTO branches (name, short_name, sol_id, location, province, province_id, hub_type, parent_hub_id) VALUES ($1, $2, $3, $4, $5, $6, $7, $8) ON CONFLICT (sol_id) DO UPDATE SET hub_type = $7, parent_hub_id = $8 RETURNING id',
                [...p, 'Province', hoId]
            );
            provinceIdMap[p[4]] = res.rows[0].id;
        }

        // C. Standard Branches (Branch Hubs)
        const branches = [
            ['Kathmandu Main', 'KTM-MAIN', '001', 'Kathmandu', 'Bagmati', provinceMap['Bagmati']],
            ['Lalitpur Branch', 'LAL-BR', '002', 'Lalitpur', 'Bagmati', provinceMap['Bagmati']],
            ['Pokhara Lakeside', 'POK-LKS', '003', 'Pokhara', 'Gandaki', provinceMap['Gandaki']],
            ['Biratnagar City', 'BRT-CTY', '004', 'Biratnagar', 'Koshi', provinceMap['Koshi']],
            ['Butwal Central', 'BUT-CEN', '005', 'Butwal', 'Lumbini', provinceMap['Lumbini']],
            ['Nepalgunj Gate', 'NPJ-GTE', '006', 'Nepalgunj', 'Lumbini', provinceMap['Lumbini']],
            ['Dhangadhi West', 'DHA-WST', '007', 'Dhangadhi', 'Sudurpashchim', provinceMap['Sudurpashchim']],
            ['Janakpur Dham', 'JAN-DHM', '008', 'Janakpur', 'Madhesh', provinceMap['Madhesh']],
            ['Hetauda Industrial', 'HET-IND', '009', 'Hetauda', 'Bagmati', provinceMap['Bagmati']],
            ['Itahari Junction', 'ITA-JNC', '010', 'Itahari', 'Koshi', provinceMap['Koshi']]
        ];

        console.log('Seeding standard branches...');
        for (const b of branches) {
            const parentId = provinceIdMap[b[4]];
            await db.query(
                'INSERT INTO branches (name, short_name, sol_id, location, province, province_id, hub_type, parent_hub_id) VALUES ($1, $2, $3, $4, $5, $6, $7, $8) ON CONFLICT (sol_id) DO UPDATE SET hub_type = $7, parent_hub_id = $8',
                [...b, 'Branch', parentId]
            );
        }

        console.log('SUCCESS: All hubs (HO -> Province -> Branch) seeded.');
        process.exit(0);
    } catch (err) {
        console.error('Seeding failed:', err);
        process.exit(1);
    }
}

seedNepalData();
