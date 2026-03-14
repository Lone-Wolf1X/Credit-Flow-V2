const db = require('./src/db');

const valNames = [
    "Sushil Poudel", "Deepak Bhusal", "Ramesh Shrestha", "Binod Kumar Jha", 
    "Anil Sharma", "Prabin Adhikari", "Sunil Karki", "Manoj Thapa", 
    "Suman Ghimire", "Krishna Pandey", "Rajesh Hamal", "Sanjay Gurung",
    "Pramod Yadav", "Ganesh Mahat", "Niraj Chhetri", "Ashish Lamichhane",
    "Santosh Rai", "Bibek Basnet", "Arjun Magar", "Prakash Tamang"
];

const firmNames = [
    "Kathmandu Engineering Consultancy", "Himalayan Builders & Valuators", 
    "Valley Associates", "Lumbini Technical Services", "Sagarmatha Valuators",
    "Mount Everest Engineering", "Nepal Technical Hub", "Pioneer Engineering",
    "Quality Valuation Services", "Expert Engineering & PMC"
];

const seed = async () => {
    try {
        console.log('Seeding valuators to ensure 2-3 per branch...');
        
        // 1. Get all branches
        const branchesRes = await db.query('SELECT id, name FROM branches');
        const branches = branchesRes.data || branchesRes.rows; // check both for compatibility

        for (const branch of branches) {
            // Count existing valuators for this branch
            const existingRes = await db.query('SELECT count(*) FROM valuators WHERE branch_id = $1', [branch.id]);
            const count = parseInt(existingRes.rows[0].count);
            
            const needed = 3 - count;
            if (needed <= 0) continue;

            console.log(`Adding ${needed} valuators to branch: ${branch.name}`);

            for (let i = 0; i < needed; i++) {
                const name = valNames[Math.floor(Math.random() * valNames.length)];
                const firm = firmNames[Math.floor(Math.random() * firmNames.length)];
                const license = 'VAL-' + Math.floor(10000 + Math.random() * 90000);
                const exp = 5 + Math.floor(Math.random() * 15);
                const phone = '980' + Math.floor(1000000 + Math.random() * 8999999);
                const email = name.toLowerCase().replace(' ', '.') + '@gmail.com';
                
                // Dates: Start 1 year ago, expire in 2 years
                const startDate = new Date();
                startDate.setFullYear(startDate.getFullYear() - 1);
                const expiryDate = new Date();
                expiryDate.setFullYear(expiryDate.getFullYear() + 2);

                await db.query(
                    `INSERT INTO valuators (name, firm_name, license_number, experience_years, contact_number, email, branch_id, status, start_date, expiry_date)
                     VALUES ($1, $2, $3, $4, $5, $6, $7, 'Active', $8, $9)`,
                    [name, firm, license, exp, phone, email, branch.id, startDate, expiryDate]
                );
            }
        }

        console.log('Seeding completed.');
        process.exit(0);
    } catch (err) {
        console.error('Seeding failed:', err);
        process.exit(1);
    }
};

seed();
