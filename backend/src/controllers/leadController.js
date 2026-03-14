const db = require('../db');
const scoringService = require('../services/scoringService');
const workflowController = require('./workflowController');

/**
 * PHASE 1: Create a new lead with initial LQS (Lead Qualification Score)
 */
exports.createLead = async (req, res) => {
    const {
        customer_name, contact_number, customer_type, address,
        loan_type, loan_scheme, income_source, proposed_limit,
        is_individual, is_existing_customer,
        collateral_type, estimated_collateral_value, undivided_family_members,
        is_pep, has_legal_dispute, primary_income, secondary_income,
        other_income_amount, other_income_source, loan_segment,
        is_direct_appraisal
    } = req.body;

    try {
        // Generate Lead ID
        const leadCount = await db.query('SELECT count(*) FROM leads');
        const seq = (parseInt(leadCount.rows[0].count || 0) + 1).toString().padStart(3, '0');
        const dateStr = new Date().toISOString().split('T')[0].replace(/-/g, '');
        const lead_id = `L-${dateStr}-${seq}`;

        // Calculate Initial LQS
        const lqs_score = is_direct_appraisal ? 100 : scoringService.calculateLQS({
            customer_name, customer_type, income_source, 
            proposed_limit, is_individual, is_existing_customer,
            undivided_family_members, is_pep, has_legal_dispute,
            primary_income, secondary_income, other_income_amount
        });

        let risk_category = 'Moderate';
        if (lqs_score > 80) risk_category = 'Low';
        else if (lqs_score < 40) risk_category = 'High';

        // Insert Lead
        const result = await db.query(
            `INSERT INTO leads (
                lead_id, customer_name, customer_type, contact_number, address,
                loan_type, loan_scheme, income_source, proposed_limit, 
                is_individual, initiator_id, current_owner_id, status, branch_id,
                collateral_type, estimated_collateral_value, undivided_family_members,
                is_pep, has_legal_dispute, primary_income, secondary_income,
                other_income_amount, other_income_source, loan_segment
            ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, (SELECT branch_id FROM users WHERE id = $11),
                $14, $15, $16, $17, $18, $19, $20, $21, $22, $23) RETURNING *`,
            [
                lead_id, customer_name, customer_type || 'Individual', contact_number, address,
                loan_type || 'New', loan_scheme, income_source, proposed_limit || 0,
                is_individual === 'true' || is_individual === true, req.user.id, req.user.id, 'Analysis',
                collateral_type, estimated_collateral_value || 0, undivided_family_members || 1,
                is_pep === 'true' || is_pep === true, has_legal_dispute === 'true' || has_legal_dispute === true,
                primary_income || 0, secondary_income || 0, other_income_amount || 0, other_income_source,
                loan_segment
            ]
        );

        const lead = result.rows[0];

        // Save Initial Score (and Phase 2 dummy scores if direct appraisal)
        await db.query(
            "INSERT INTO lead_scoring (lead_id, lqs_score, risk_category, sv_score, fcs_score, deviation_percentage, deviation_alerts) VALUES ($1, $2, $3, $4, $5, $6, $7)",
            [lead_id, lqs_score, risk_category, is_direct_appraisal ? 100 : null, is_direct_appraisal ? 100 : null, is_direct_appraisal ? 0 : null, is_direct_appraisal ? '[]' : null]
        );

        if (is_direct_appraisal) {
            // Bypass Phase 2 Verification
            await db.query(
                `INSERT INTO lead_verified_data (lead_id, verified_income, verified_collateral_value, cib_report_status, kyc_status, verification_notes, staff_id)
                 VALUES ($1, $2, $3, $4, $5, $6, $7)`,
                [lead_id, proposed_limit, estimated_collateral_value || 0, 'Clear', 'Completed', 'System Auto-Bypass for Existing Bank File', req.user.id]
            );
        }

        // Initialize Workflow
        await workflowController.initializeWorkflow(lead_id, parseFloat(proposed_limit || 0), req.user.id, is_direct_appraisal);

        // Log LP Points
        await db.query(
            "INSERT INTO point_logs (user_id, lead_id, points, type, description) VALUES ($1, $2, $3, $4, $5)",
            [req.user.id, lead_id, 10, 'Initiation', is_direct_appraisal ? 'Direct Appraisal Creation' : 'Points for creating a new lead']
        );

        res.status(201).json({ ...lead, lqs_score, risk_category });
    } catch (err) {
        console.error('createLead error:', err);
        res.status(500).json({ error: err.message });
    }
};

/**
 * Get leads with role-based visibility
 */
exports.getLeads = async (req, res) => {
    try {
        const { role, id, branch_id } = req.user;
        let query = `
            SELECT l.*, s.lqs_score, s.sv_score, s.fcs_score, s.risk_category, u.name as initiator_name 
            FROM leads l
            LEFT JOIN lead_scoring s ON l.lead_id = s.lead_id
            LEFT JOIN users u ON l.initiator_id = u.id
        `;
        let params = [];

        if (role !== 'Admin') {
            let activeBranch = branch_id;
            if (!activeBranch) {
                const uRes = await db.query('SELECT branch_id FROM users WHERE id = $1', [id]);
                activeBranch = uRes.rows[0]?.branch_id;
            }
            query += " WHERE l.branch_id = $1";
            params.push(activeBranch);
        }

        query += " ORDER BY l.created_at DESC";
        const result = await db.query(query, params);
        res.json(result.rows);
    } catch (err) {
        console.error('getLeads error:', err);
        res.status(500).json({ error: err.message });
    }
};

/**
 * Get detailed lead info including scores and logs
 */
exports.getLeadDetails = async (req, res) => {
    const { id } = req.params;
    try {
        const result = await db.query(`
            SELECT l.*, s.lqs_score, s.sv_score, s.fcs_score, s.risk_category, s.deviation_percentage, s.deviation_alerts,
                   v.verified_income, v.verified_collateral_value, v.cib_report_status, v.kyc_status, v.verification_notes,
                   u.name as initiator_name,
                   va.id as valuation_assignment_id, va.status as valuation_status, 
                   va.final_valuation_value, va.pre_valuation_value,
                   val.name as valuator_name, val.firm_name as valuator_firm
            FROM leads l
            LEFT JOIN lead_scoring s ON l.lead_id = s.lead_id
            LEFT JOIN lead_verified_data v ON l.lead_id = v.lead_id
            LEFT JOIN users u ON l.initiator_id = u.id
            LEFT JOIN valuation_assignments va ON l.lead_id = va.lead_id
            LEFT JOIN valuators val ON va.valuator_id = val.id
            WHERE l.lead_id = $1 OR l.id::text = $1
        `, [id]);

        if (result.rows.length === 0) {
            return res.status(404).json({ error: 'Lead not found' });
        }

        const lead = result.rows[0];

        // Fetch logs
        const logs = await db.query(
            "SELECT * FROM point_logs WHERE lead_id = $1 ORDER BY created_at DESC",
            [lead.lead_id]
        );

        res.json({ ...lead, logs: logs.rows });
    } catch (err) {
        console.error('getLeadDetails error:', err);
        res.status(500).json({ error: err.message });
    }
};

/**
 * PHASE 2: Verification (Staff appraisal data entry)
 */
exports.submitVerification = async (req, res) => {
    const { id } = req.params;
    const { verified_income, verified_collateral_value, cib_report_status, kyc_status, verification_notes } = req.body;

    try {
        const leadRes = await db.query('SELECT * FROM leads WHERE id = $1 OR lead_id = $1', [id]);
        if (leadRes.rows.length === 0) return res.status(404).json({ error: 'Lead not found' });
        const lead = leadRes.rows[0];

        // 1. Calculate SVS
        const sv_score = scoringService.calculateSVS({
            kyc_status, cib_report_status, verification_notes
        });

        // 2. Detective Deviations
        const { avgDeviation, alerts, riskCategory } = scoringService.detectDeviations(lead, { verified_income });

        // 3. Update Scoring Table
        const lqsRes = await db.query('SELECT lqs_score FROM lead_scoring WHERE lead_id = $1', [lead.lead_id]);
        const lqs = lqsRes.rows[0]?.lqs_score || 0;
        const fcs = Math.round((lqs * 0.4) + (sv_score * 0.6));

        await db.query(
            `UPDATE lead_scoring SET 
                sv_score = $1, fcs_score = $2, deviation_percentage = $3, 
                deviation_alerts = $4, risk_category = $5 
             WHERE lead_id = $6`,
            [sv_score, fcs, avgDeviation, JSON.stringify(alerts), riskCategory, lead.lead_id]
        );

        // 4. Record Verified Data
        await db.query(
            `INSERT INTO lead_verified_data (lead_id, verified_income, verified_collateral_value, cib_report_status, kyc_status, verification_notes, staff_id)
             VALUES ($1, $2, $3, $4, $5, $6, $7)`,
            [lead.lead_id, verified_income, verified_collateral_value, cib_report_status, kyc_status, verification_notes, req.user.id]
        );

        // 5. Award Points for Comprehensive Verification (+20 points)
        await db.query(
            "INSERT INTO point_logs (user_id, lead_id, points, type, description) VALUES ($1, $2, $3, $4, $5)",
            [req.user.id, lead.lead_id, 20, 'Verification', 'Points for detailed staff verification']
        );

        // Update Lead Status to Ongoing
        await db.query("UPDATE leads SET status = 'Ongoing', updated_at = CURRENT_TIMESTAMP WHERE lead_id = $1", [lead.lead_id]);

        res.json({ message: 'Verification submitted successfully', fcs_score: fcs });
    } catch (err) {
        console.error('submitVerification error:', err);
        res.status(500).json({ error: err.message });
    }
};

exports.getPerformanceScores = async (req, res) => {
    try {
        const result = await db.query(`
            SELECT u.name, u.staff_id, SUM(p.points) as total_points, COUNT(p.id) as interactions
            FROM users u
            JOIN point_logs p ON u.id = p.user_id
            GROUP BY u.id
            ORDER BY total_points DESC
        `);
        res.json(result.rows);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};
