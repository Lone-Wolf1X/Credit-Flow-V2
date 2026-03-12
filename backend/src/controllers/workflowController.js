const db = require('../db');

/**
 * Initialize a workflow for a new lead
 */
exports.initializeWorkflow = async (lead_id, proposed_limit, initiator_id) => {
    try {
        // Find the BM of the branch
        const branchRes = await db.query('SELECT branch_id FROM leads WHERE lead_id = $1', [lead_id]);
        const branch_id = branchRes.rows[0].branch_id;
        
        const bmRes = await db.query(
            "SELECT id FROM users WHERE branch_id = $1 AND designation = 'Branch Manager' LIMIT 1",
            [branch_id]
        );
        const bm_id = bmRes.rows[0]?.id;

        await db.query(
            `INSERT INTO workflows (lead_id, file_status, current_step, current_handler_id, power_level_required)
             VALUES ($1, 'Pending', 'Branch Review', $2, $3)`,
            [lead_id, bm_id || initiator_id, proposed_limit]
        );
    } catch (err) {
        console.error('initializeWorkflow error:', err);
    }
};

/**
 * Submit a Review (Branch/Province/HO)
 */
exports.submitReview = async (req, res) => {
    const { lead_id, status, confidence_level, feedback, conditions } = req.body;
    const reviewer_id = req.user.id;

    try {
        const workflowRes = await db.query('SELECT * FROM workflows WHERE lead_id = $1', [lead_id]);
        if (workflowRes.rows.length === 0) return res.status(404).json({ error: 'Workflow not found' });
        const workflow = workflowRes.rows[0];

        const userRes = await db.query(
            'SELECT u.*, d.default_power_limit FROM users u JOIN designations d ON u.designation = d.name WHERE u.id = $1',
            [reviewer_id]
        );
        const user = userRes.rows[0];

        // 1. Log the Review
        await db.query(
            `INSERT INTO lead_reviews (lead_id, reviewer_id, level, review_status, confidence_level, feedback, conditions)
             VALUES ($1, $2, $3, $4, $5, $6, $7)`,
            [lead_id, reviewer_id, user.designation, status, confidence_level, feedback, conditions]
        );

        // 2. Decision Logic
        let next_status = workflow.file_status;
        let next_handler = workflow.current_handler_id;
        let next_step = workflow.current_step;

        if (status === 'Approved') {
            if (user.default_power_limit >= workflow.power_level_required) {
                // Within power - Push for Appraisal
                next_status = 'Approved';
                next_step = 'Appraisal Ready';
                // Update Lead Status
                await db.query("UPDATE leads SET status = 'Appraisal' WHERE lead_id = $1", [lead_id]);
            } else {
                // Beyond power - Escalate to Province or HO
                next_status = 'Review';
                if (user.designation === 'Branch Manager') {
                    // Escalate to Province Head
                    const phRes = await db.query(
                        "SELECT id FROM users WHERE province_id = $1 AND designation = 'Province Head' LIMIT 1",
                        [user.province_id]
                    );
                    next_handler = phRes.rows[0]?.id || workflow.current_handler_id;
                    next_step = 'Provincial Review';
                } else if (user.designation === 'Province Head') {
                    // Escalate to Credit Head / HO
                    const hoRes = await db.query(
                        "SELECT id FROM users WHERE designation = 'Credit Head' LIMIT 1"
                    );
                    next_handler = hoRes.rows[0]?.id || workflow.current_handler_id;
                    next_step = 'HO Review';
                }
            }
        } else if (status === 'Defended') {
             // File stays at current level for further discussion/defending
             next_status = 'Defended';
        } else if (status === 'Further Discussion') {
             next_status = 'Analysis';
             next_step = 'Re-Analysis';
        }

        await db.query(
            `UPDATE workflows SET file_status = $1, current_step = $2, current_handler_id = $3, updated_at = CURRENT_TIMESTAMP
             WHERE lead_id = $4`,
            [next_status, next_step, next_handler, lead_id]
        );

        res.json({ message: 'Review submitted successfully', next_status, next_step });
    } catch (err) {
        console.error('submitReview error:', err);
        res.status(500).json({ error: err.message });
    }
};

/**
 * Get Workflow & Reviews for a lead
 */
exports.getLeadWorkflow = async (req, res) => {
    const { lead_id } = req.params;
    try {
        const workflow = await db.query(
            `SELECT w.*, u.name as handler_name, u.designation as handler_designation 
             FROM workflows w 
             LEFT JOIN users u ON w.current_handler_id = u.id 
             WHERE w.lead_id = $1`,
            [lead_id]
        );
        
        const reviews = await db.query(
            `SELECT r.*, u.name as reviewer_name 
             FROM lead_reviews r 
             JOIN users u ON r.reviewer_id = u.id 
             WHERE r.lead_id = $1 ORDER BY r.review_date ASC`,
            [lead_id]
        );

        res.json({ workflow: workflow.rows[0], reviews: reviews.rows });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};
