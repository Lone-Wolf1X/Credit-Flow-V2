const db = require('../db');
const scoringService = require('../services/scoringService');
const documentService = require('../services/documentService');
const workflowService = require('../services/workflowService');

exports.submitAppraisal = async (req, res) => {
    const { id } = req.params; // lead_id
    const { 
        loan_type, borrower_details, income_details, collateral_details, 
        valuations, risk_assessment, pricing, final_recommendation 
    } = req.body;

    try {
        // 1. Save / Update Appraisal Data
        const check = await db.query('SELECT id FROM lead_appraisals WHERE lead_id = $1', [id]);
        
        const monthly_income = (income_details.salary_net || 0) + 
                              (income_details.agriculture_net || 0) + 
                              (income_details.remittance_net || 0) + 
                              (income_details.rental_net || 0);

        if (check.rows.length > 0) {
            await db.query(
                `UPDATE lead_appraisals SET 
                    monthly_income = $1, fair_market_value = $2, distress_value = $3,
                    recommended_limit = $4, interest_rate = $5,
                    borrower_details = $6, income_details = $7, collateral_details = $8,
                    risk_assessment = $9, pricing_details = $10, appraisal_status = 'Submitted'
                WHERE lead_id = $11`,
                [
                    monthly_income,
                    valuations.fmv, valuations.dv, valuations.recommended_limit, pricing.effective_rate,
                    borrower_details, income_details, collateral_details, risk_assessment, pricing, id
                ]
            );
        } else {
            await db.query(
                `INSERT INTO lead_appraisals (
                    lead_id, monthly_income, fair_market_value, distress_value, 
                    recommended_limit, interest_rate, borrower_details, 
                    income_details, collateral_details, risk_assessment, 
                    pricing_details, appraisal_status, appraiser_id
                ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, 'Submitted', $12)`,
                [
                    id, monthly_income,
                    valuations.fmv, valuations.dv, valuations.recommended_limit, pricing.effective_rate,
                    borrower_details, income_details, collateral_details, risk_assessment, pricing, req.user.id
                ]
            );
        }

        // 2. Fetch escalation path (authority check)
        const leadRes = await db.query('SELECT loan_segment, loan_type, proposed_limit, branch_id FROM leads WHERE lead_id = $1', [id]);
        const lead = leadRes.rows[0];
        const escalation = await workflowService.getEscalationPath(lead.loan_segment, lead.loan_type, lead.proposed_limit, lead.branch_id);

        // 3. Update Scoring Table
        const retail_score = risk_assessment.cra_score || 0;
        await db.query(
            "UPDATE lead_scoring SET fcs_score = $1 WHERE lead_id = $2",
            [retail_score, id]
        );

        // 4. Update Lead Status & Workflow
        await db.query("UPDATE leads SET status = 'Appraised' WHERE lead_id = $1", [id]);
        
        // Update workflow with escalation info if needed
        if (escalation) {
            await db.query(
                "UPDATE workflows SET assigned_role = $1, power_level_required = $2, updated_at = CURRENT_TIMESTAMP WHERE lead_id = $3",
                [escalation.approver_designation, lead.proposed_limit, id]
            );
        }

        res.json({ message: 'Appraisal submitted successfully', retail_score });
    } catch (err) {
        console.error('submitAppraisal error:', err);
        res.status(500).json({ error: err.message });
    }
};

exports.getAppraisal = async (req, res) => {
    const { id } = req.params;
    try {
        const result = await db.query(
            `SELECT a.*, l.customer_name, l.lead_id as lead_identifier, l.loan_segment, l.loan_type, l.loan_connection_type, l.proposed_limit, w.cap_id, w.assigned_role
             FROM lead_appraisals a 
             LEFT JOIN leads l ON a.lead_id = l.lead_id 
             LEFT JOIN workflows w ON l.lead_id = w.lead_id
             WHERE a.lead_id = $1 OR a.id::text = $1`, 
            [id]
        );
        const appraisal = result.rows[0];
        
        if (appraisal) {
            const userRes = await db.query('SELECT designation FROM users WHERE id = $1', [appraisal.initiator_id]);
            const initiator_designation = userRes.rows[0]?.designation;
            
            const escalation = await workflowService.getEscalationPath(
                appraisal.loan_segment, 
                appraisal.loan_type, 
                appraisal.proposed_limit, 
                appraisal.branch_id,
                initiator_designation
            );
            appraisal.escalation = escalation;
        }

        res.json(appraisal);
    } catch (err) {
        console.error('getAppraisal error:', err);
        res.status(500).json({ error: err.message });
    }
};

exports.getAllAppraisals = async (req, res) => {
    try {
        const { role, branch_id } = req.user;
        let query = `
            SELECT a.*, l.customer_name, l.lead_id as lead_identifier, b.name as branch_name 
            FROM lead_appraisals a
            LEFT JOIN leads l ON a.lead_id = l.lead_id
            LEFT JOIN branches b ON l.branch_id = b.id
        `;
        let params = [];

        if (role !== 'Admin') {
            query += " WHERE l.branch_id = $1";
            params.push(branch_id);
        }

        query += " ORDER BY a.created_at DESC";
        const result = await db.query(query, params);
        res.json(result.rows);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.createDirectAppraisal = async (req, res) => {
    const { lead_id, loan_segment, loan_type, loan_connection_type, proposed_limit } = req.body;
    try {
        // 1. Update Lead with selected options
        await db.query(
            `UPDATE leads SET 
                loan_segment = $1, 
                loan_type = $2, 
                loan_connection_type = $3, 
                proposed_limit = $4,
                status = 'Appraisal'
            WHERE lead_id = $5`,
            [loan_segment, loan_type, loan_connection_type, proposed_limit, lead_id]
        );

        // 2. Generate CAP ID
        const cap_id = await workflowService.generateCapId(loan_segment, loan_type);

        // 3. Upsert Workflow with CAP ID
        const workflowCheck = await db.query('SELECT id FROM workflows WHERE lead_id = $1', [lead_id]);
        if (workflowCheck.rows.length > 0) {
            await db.query(
                "UPDATE workflows SET cap_id = $1, power_level_required = $2, updated_at = CURRENT_TIMESTAMP WHERE lead_id = $3",
                [cap_id, proposed_limit, lead_id]
            );
        } else {
            await db.query(
                "INSERT INTO workflows (lead_id, cap_id, power_level_required, file_status) VALUES ($1, $2, $3, 'Draft')",
                [lead_id, cap_id, proposed_limit]
            );
        }

        // 4. Create blank appraisal record if not exists
        const appraisalCheck = await db.query('SELECT id FROM lead_appraisals WHERE lead_id = $1', [lead_id]);
        let appraisal;
        if (appraisalCheck.rows.length === 0) {
            const result = await db.query(
                "INSERT INTO lead_appraisals (lead_id, appraiser_id, appraisal_status) VALUES ($1, $2, 'Draft') RETURNING *",
                [lead_id, req.user.id]
            );
            appraisal = result.rows[0];
        } else {
            const result = await db.query('SELECT * FROM lead_appraisals WHERE lead_id = $1', [lead_id]);
            appraisal = result.rows[0];
        }

        res.status(201).json({ ...appraisal, cap_id });
    } catch (err) {
        console.error('createDirectAppraisal error:', err);
        res.status(500).json({ error: err.message });
    }
};

exports.createBlankAppraisal = async (req, res) => {
    // This might be deprecated in favor of createDirectAppraisal with options
    try {
        const result = await db.query(
            "INSERT INTO lead_appraisals (appraisal_status) VALUES ('Draft') RETURNING *"
        );
        res.status(201).json(result.rows[0]);
    } catch (err) {
        console.error('Create Blank Error:', err);
        res.status(500).json({ error: err.message });
    }
};

exports.exportAppraisalDocx = async (req, res) => {
    const { id } = req.params;
    try {
        const result = await db.query(
            `SELECT a.*, l.customer_name, l.lead_id as lead_identifier 
             FROM lead_appraisals a 
             JOIN leads l ON a.lead_id = l.lead_id 
             WHERE a.lead_id = $1 OR a.id::text = $1`, 
            [id]
        );
        
        if (result.rows.length === 0) {
            return res.status(404).json({ error: 'Appraisal not found' });
        }

        const buffer = await documentService.generateAppraisalDocx(result.rows[0]);
        
        res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        res.setHeader('Content-Disposition', `attachment; filename=Appraisal_${id}.docx`);
        res.send(buffer);
    } catch (err) {
        console.error('Export error:', err);
        res.status(500).json({ error: err.message });
    }
};
