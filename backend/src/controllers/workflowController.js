const db = require('../db');
const workflowService = require('../services/workflowService');

/**
 * Initialize a workflow for a new lead
 */
exports.initializeWorkflow = async (lead_id, proposed_limit, initiator_id, is_direct_appraisal = false) => {
    try {
        const leadRes = await db.query('SELECT branch_id, loan_segment, loan_type FROM leads WHERE lead_id = $1', [lead_id]);
        const lead = leadRes.rows[0];
        
        const initiatorRes = await db.query('SELECT designation FROM users WHERE id = $1', [initiator_id]);
        const initiator_designation = initiatorRes.rows[0]?.designation || 'Staff';

        const escalation = await workflowService.getEscalationPath(
            lead.loan_segment, 
            lead.loan_type, 
            proposed_limit, 
            lead.branch_id,
            initiator_designation
        );

        // Find a handler user matching reviewer_designation
        // If it's BM, we need branch_id. If PH, we need parent_hub_id.
        let handler_id = null;
        
        if (escalation.reviewer_designation === 'Branch Manager') {
            const res = await db.query(
                "SELECT id FROM users WHERE branch_id = $1 AND designation = 'Branch Manager' LIMIT 1",
                [lead.branch_id]
            );
            handler_id = res.rows[0]?.id;
        } else if (escalation.reviewer_designation === 'Province Head') {
            const branchRes = await db.query('SELECT parent_hub_id FROM branches WHERE id = $1', [lead.branch_id]);
            const parent_hub_id = branchRes.rows[0]?.parent_hub_id;
            const res = await db.query(
                "SELECT id FROM users WHERE branch_id = $1 AND designation = 'Province Head' LIMIT 1",
                [parent_hub_id]
            );
            handler_id = res.rows[0]?.id;
        } else if (escalation.reviewer_designation === 'Credit Head') {
            const res = await db.query("SELECT id FROM users WHERE designation = 'Central Head' LIMIT 1");
            handler_id = res.rows[0]?.id;
        } else if (escalation.reviewer_designation === 'CEO') {
            const res = await db.query("SELECT id FROM users WHERE designation = 'CEO' LIMIT 1");
            handler_id = res.rows[0]?.id;
        }

        await db.query(
            `INSERT INTO workflows (lead_id, file_status, current_step, current_handler_id, power_level_required, assigned_role)
             VALUES ($1, 'Pending', $2, $3, $4, $5)`,
            [
                lead_id, 
                is_direct_appraisal ? 'Appraisal Ready' : `${escalation.reviewer_designation} Review`, 
                handler_id || initiator_id, 
                proposed_limit,
                escalation.reviewer_designation
            ]
        );
    } catch (err) {
        console.error('initializeWorkflow error:', err);
    }
};

/**
 * Submit a Review (Branch/Province/HO)
 */
exports.submitReview = async (req, res) => {
    const { 
        lead_id, status, confidence_level, feedback, conditions,
        income_assessment, collateral_assessment, identity_assessment, other_assessment 
    } = req.body;
    const reviewer_id = req.user.id;

    try {
        const workflowRes = await db.query('SELECT * FROM workflows WHERE lead_id = $1', [lead_id]);
        if (workflowRes.rows.length === 0) return res.status(404).json({ error: 'Workflow not found' });
        const workflow = workflowRes.rows[0];

        const leadRes = await db.query('SELECT * FROM leads WHERE lead_id = $1', [lead_id]);
        const lead = leadRes.rows[0];

        const userRes = await db.query(
            'SELECT u.*, d.default_power_limit FROM users u JOIN designations d ON u.designation = d.name WHERE u.id = $1',
            [reviewer_id]
        );
        const user = userRes.rows[0];
        const effective_limit = parseFloat(user.limit_power) > 0 ? parseFloat(user.limit_power) : parseFloat(user.default_power_limit);

        // 1. Log the Review
        await db.query(
            `INSERT INTO lead_reviews (
                lead_id, reviewer_id, level, review_status, confidence_level, feedback, conditions,
                income_assessment, collateral_assessment, identity_assessment, other_assessment
            ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)`,
            [
                lead_id, reviewer_id, user.designation, status, confidence_level, feedback, conditions,
                income_assessment, collateral_assessment, identity_assessment, other_assessment
            ]
        );

        // 2. Decision Logic
        let next_status = workflow.file_status;
        let next_handler = workflow.current_handler_id;
        let next_step = workflow.current_step;
        let next_role = workflow.assigned_role;

        if (status === 'Approved') {
            const initiatorRes = await db.query('SELECT designation FROM users WHERE id = $1', [lead.initiator_id]);
            const initiator_designation = initiatorRes.rows[0]?.designation || 'Staff';

            const escalation = await workflowService.getEscalationPath(
                lead.loan_segment, 
                lead.loan_type, 
                lead.proposed_limit, 
                lead.branch_id,
                initiator_designation
            );
            
            // Check if current user has enough power to be the FINAL approver
            if (effective_limit >= workflow.power_level_required || user.designation === 'CEO' || user.designation === escalation.approver_designation) {
                next_status = 'Approved';
                next_step = 'Sanctioned';
                next_role = null;
                next_handler = null;
                await db.query("UPDATE leads SET status = 'Appraised' WHERE lead_id = $1", [lead_id]);
            } else {
                // Escalate to next Hub Level
                const branchRes = await db.query('SELECT hub_type, parent_hub_id FROM branches WHERE id = $1', [user.branch_id]);
                const branch = branchRes.rows[0];
                
                let nextHubId = branch.parent_hub_id;
                let nextRole = 'Province Head';
                
                if (user.designation === 'Province Head') {
                    nextRole = 'Credit Head';
                } else if (user.designation === 'Credit Head' || user.designation === 'Central Head') {
                    nextRole = 'CEO';
                }

                // Find a user in the next hub with that designation
                const nextUserRes = await db.query(
                    "SELECT id FROM users WHERE designation = $1 AND (branch_id = $2 OR branch_id IS NULL) LIMIT 1",
                    [nextRole === 'Credit Head' ? 'Central Head' : nextRole, nextHubId]
                );

                next_status = 'Review';
                next_role = nextRole;
                next_handler = nextUserRes.rows[0]?.id || null;
                next_step = `${next_role} Review`;
            }
        } else if (status === 'Further Discussion' || status === 'Returned') {
             next_status = 'Analysis';
             next_step = 'Re-Analysis';
             // Return to initiator
             next_handler = lead.initiator_id;
             next_role = 'Initiator';
        } else if (status === 'Declined') {
            next_status = 'Declined';
            next_step = 'Rejected';
            await db.query("UPDATE leads SET status = 'Rejected' WHERE lead_id = $1", [lead_id]);
        }

        await db.query(
            `UPDATE workflows SET file_status = $1, current_step = $2, current_handler_id = $3, assigned_role = $4, updated_at = CURRENT_TIMESTAMP
             WHERE lead_id = $5`,
            [next_status, next_step, next_handler, next_role, lead_id]
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
             JOIN leads l ON w.lead_id = l.lead_id
             LEFT JOIN users u ON w.current_handler_id = u.id 
             WHERE l.lead_id = $1 OR l.id::text = $1`,
            [lead_id]
        );
        
        const reviews = await db.query(
            `SELECT r.*, u.name as reviewer_name 
             FROM lead_reviews r 
             JOIN leads l ON r.lead_id = l.lead_id
             JOIN users u ON r.reviewer_id = u.id 
             WHERE l.lead_id = $1 OR l.id::text = $1 
             ORDER BY r.review_date ASC`,
            [lead_id]
        );

        res.json({ workflow: workflow.rows[0] || null, reviews: reviews.rows });
    } catch (err) {
        console.error('getLeadWorkflow error:', err);
        res.status(500).json({ error: err.message });
    }
};
/**
 * Reappeal a decision
 */
exports.reappealReview = async (req, res) => {
    const { lead_id, feedback } = req.body;
    const reappealer_id = req.user.id;

    try {
        const workflowRes = await db.query('SELECT * FROM workflows WHERE lead_id = $1', [lead_id]);
        if (workflowRes.rows.length === 0) return res.status(404).json({ error: 'Workflow not found' });
        const workflow = workflowRes.rows[0];

        // Find the last review that was NOT a reappeal
        const lastReviewRes = await db.query(
            `SELECT r.*, u.designation 
             FROM lead_reviews r 
             JOIN users u ON r.reviewer_id = u.id 
             WHERE r.lead_id = $1 AND r.review_status IN ('Declined', 'Approved') 
             ORDER BY r.review_date DESC LIMIT 1`,
            [lead_id]
        );

        if (lastReviewRes.rows.length === 0) {
            return res.status(400).json({ error: 'No decision found to reappeal' });
        }

        const lastReview = lastReviewRes.rows[0];
        const hierarchy = ['Branch Manager', 'Province Head', 'Credit Head', 'Deputy CEO', 'CEO'];
        const lastIndex = hierarchy.indexOf(lastReview.designation);
        
        // Next level above the person who gave the decision
        const nextDesignation = hierarchy[lastIndex + 1] || 'CEO';
        
        const nextHandlerRes = await db.query(
            "SELECT id FROM users WHERE designation = $1 LIMIT 1",
            [nextDesignation]
        );

        const next_handler = nextHandlerRes.rows[0]?.id || workflow.current_handler_id;
        const next_step = `${nextDesignation} Review (Reappealed)`;

        // 1. Log the Reappeal
        await db.query(
            `INSERT INTO lead_reviews (lead_id, reviewer_id, level, review_status, feedback) 
             VALUES ($1, $2, $3, $4, $5)`,
            [lead_id, reappealer_id, req.user.designation, 'Reappealed', feedback]
        );

        // 2. Update Workflow
        await db.query(
            `UPDATE workflows 
             SET file_status = 'Analysis', current_step = $1, current_handler_id = $2, updated_at = CURRENT_TIMESTAMP
             WHERE lead_id = $3`,
            [next_step, next_handler, lead_id]
        );

        // 3. Ensure lead status is Analysis
        await db.query("UPDATE leads SET status = 'Analysis' WHERE lead_id = $1", [lead_id]);

        // 4. Award Defense Points (+15 points for defending a file)
        await db.query(
            "INSERT INTO point_logs (user_id, lead_id, points, type, description) VALUES ($1, $2, $3, $4, $5)",
            [reappealer_id, lead_id, 15, 'Reappeal_Defense', `Defended position against ${lastReview.designation}'s decision`]
        );

        res.json({ message: 'Reappeal submitted successfully', next_handler, next_step });
    } catch (err) {
        console.error('reappealReview error:', err);
        res.status(500).json({ error: err.message });
    }
};
/**
 * Get all workflows (optionally filtered by branch_id)
 */
exports.getAllWorkflows = async (req, res) => {
    try {
        const { role, branch_id } = req.user;
        let query = `
            SELECT w.*, l.customer_name, l.proposed_limit, b.name as branch_name 
            FROM workflows w
            JOIN leads l ON w.lead_id = l.lead_id
            JOIN branches b ON l.branch_id = b.id
        `;
        let params = [];

        if (role !== 'Admin') {
            query += " WHERE l.branch_id = $1";
            params.push(branch_id);
        }

        const result = await db.query(query, params);
        res.json(result.rows);
    } catch (err) {
        console.error('getAllWorkflows error:', err);
        res.status(500).json({ error: err.message });
    }
};
