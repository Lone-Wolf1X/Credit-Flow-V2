const db = require('../db');

/**
 * Get all registered valuators
 * Admin see all, Branch see linked to them
 */
exports.getValuators = async (req, res) => {
    try {
        const { role } = req.user;
        const { status, branch_id: filterBranchId } = req.query;
        
        let query = 'SELECT v.*, b.name as branch_name FROM valuators v LEFT JOIN branches b ON v.branch_id = b.id WHERE 1=1';
        let params = [];
        let paramIndex = 1;

        // Role based restriction
        if (role !== 'Admin') {
            query += ` AND v.branch_id = $${paramIndex++}`;
            params.push(req.user.branch_id);
        } else if (filterBranchId) {
            // Admin filtering by branch
            query += ` AND v.branch_id = $${paramIndex++}`;
            params.push(filterBranchId);
        }

        // Status filtering
        if (status) {
            query += ` AND v.status = $${paramIndex++}`;
            params.push(status);
        }

        query += ' ORDER BY v.created_at DESC';

        const result = await db.query(query, params);
        res.json(result.rows);
    } catch (err) {
        console.error('getValuators error:', err);
        res.status(500).json({ error: err.message });
    }
};

/**
 * Branch initiates valuator onboarding
 */
exports.onboardValuator = async (req, res) => {
    const { name, firm_name, license_number, experience_years, contact_number, email, start_date, expiry_date } = req.body;
    try {
        if (experience_years < 3) {
            return res.status(400).json({ error: 'Valuator must have at least 3 years of experience' });
        }

        const result = await db.query(
            `INSERT INTO valuators (name, firm_name, license_number, experience_years, contact_number, email, branch_id, status, start_date, expiry_date)
             VALUES ($1, $2, $3, $4, $5, $6, $7, 'Pending', $8, $9) RETURNING *`,
            [name, firm_name, license_number, experience_years, contact_number, email, req.user.branch_id, start_date, expiry_date]
        );

        res.status(201).json(result.rows[0]);
    } catch (err) {
        console.error('onboardValuator error:', err);
        res.status(500).json({ error: err.message });
    }
};

/**
 * Admin approves/rejects valuator
 */
exports.updateValuatorStatus = async (req, res) => {
    const { id } = req.params;
    const { status } = req.body; // Active, Suspended, Blacklisted
    try {
        if (req.user.role !== 'Admin') return res.status(403).json({ error: 'Admin only action' });

        const result = await db.query(
            "UPDATE valuators SET status = $1, updated_at = CURRENT_TIMESTAMP WHERE id = $2 RETURNING *",
            [status, id]
        );

        res.json(result.rows[0]);
    } catch (err) {
        console.error('updateValuatorStatus error:', err);
        res.status(500).json({ error: err.message });
    }
};

/**
 * Randomly assign an active valuator to a lead
 */
exports.assignRandomValuator = async (req, res) => {
    const { lead_id } = req.body;
    try {
        // 1. Check if lead exists and doesn't already have an assignment
        const existing = await db.query("SELECT * FROM valuation_assignments WHERE lead_id = $1", [lead_id]);
        if (existing.rows.length > 0) return res.status(400).json({ error: 'Valuator already assigned or requested' });

        // 2. Pick a random active valuator
        const valRes = await db.query("SELECT id FROM valuators WHERE status = 'Active' ORDER BY RANDOM() LIMIT 1");
        if (valRes.rows.length === 0) return res.status(404).json({ error: 'No active valuators found' });

        const valuator_id = valRes.rows[0].id;

        // 3. Create assignment
        const result = await db.query(
            "INSERT INTO valuation_assignments (lead_id, valuator_id, status) VALUES ($1, $2, 'Requested') RETURNING *",
            [lead_id, valuator_id]
        );

        res.status(201).json(result.rows[0]);
    } catch (err) {
        console.error('assignRandomValuator error:', err);
        res.status(500).json({ error: err.message });
    }
};

/**
 * Get valuation assignments (Lead Profile or Branch List)
 */
exports.getAssignments = async (req, res) => {
    try {
        const { role, branch_id } = req.user;
        let query = `
            SELECT a.*, v.name as valuator_name, v.firm_name, l.customer_name, l.proposed_limit 
            FROM valuation_assignments a
            JOIN valuators v ON a.valuator_id = v.id
            JOIN leads l ON a.lead_id = l.lead_id
        `;
        let params = [];

        if (role !== 'Admin') {
            query += ' WHERE l.branch_id = $1';
            params.push(branch_id);
        }

        const result = await db.query(query, params);
        res.json(result.rows);
    } catch (err) {
        console.error('getAssignments error:', err);
        res.status(500).json({ error: err.message });
    }
};
