const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
});

async function assignBranches() {
    try {
        console.log('Starting staff branch assignments...');
        
        // Fetch all staff
        const userRes = await pool.query('SELECT id, name, designation FROM users WHERE role = \'Staff\'');
        const users = userRes.rows;

        // Fetch branches
        const branchRes = await pool.query('SELECT id, name FROM branches ORDER BY id');
        const branches = branchRes.rows;

        if (branches.length < 3) {
            console.log('Not enough branches to perform requested separation. Need at least 3.');
            return;
        }

        // Branch 1: KTM Main (001) - id 1
        // Branch 2: Lalitpur (002) - id 2
        // Branch 3: Pokhara (003) - id 3

        for (const user of users) {
            let targetBranchId = null;
            const des = (user.designation || '').toUpperCase();

            if (des.includes('ASSISTANT') || des.includes('BM') || des.includes('BRANCH MANAGER')) {
                // Assistant and BM in Branch 1
                targetBranchId = 1;
            } else if (des.includes('PH') || des.includes('PROVINCIAL HEAD')) {
                // PH in Branch 2
                targetBranchId = 2;
            } else if (des.includes('CH') || des.includes('CENTRAL HEAD')) {
                // CH in Branch 3
                targetBranchId = 3;
            }

            if (targetBranchId) {
                console.log(`Assigning ${user.name} (${user.designation}) to Branch ID ${targetBranchId}`);
                await pool.query('UPDATE users SET branch_id = $1 WHERE id = $2', [targetBranchId, user.id]);
                
                // Also record in branch_assignments
                await pool.query(
                    'INSERT INTO branch_assignments (user_id, branch_id, type) VALUES ($1, $2, $3)',
                    [user.id, targetBranchId, 'permanent']
                );
            }
        }

        console.log('Staff assignments completed successfully.');
    } catch (err) {
        console.error('Assignment failed:', err);
    } finally {
        await pool.end();
    }
}

assignBranches();
