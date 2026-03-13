const db = require('../db');
const scoringService = require('../services/scoringService');
const documentService = require('../services/documentService');

exports.submitAppraisal = async (req, res) => {
    const { id } = req.params; // lead_id
    const { 
        loan_type, borrower_details, income_details, collateral_details, 
        valuations, risk_assessment, pricing, final_recommendation 
    } = req.body;

    try {
        // 1. Save / Update Appraisal Data
        // Using UPSERT style or checking exists
        const check = await db.query('SELECT id FROM lead_appraisals WHERE lead_id = $1', [id]);
        
        if (check.rows.length > 0) {
            await db.query(
                `UPDATE lead_appraisals SET 
                    monthly_income = $1, fair_market_value = $2, distress_value = $3,
                    recommended_limit = $4, interest_rate = $5,
                    borrower_details = $6, income_details = $7, collateral_details = $8,
                    risk_assessment = $9, pricing_details = $10, appraisal_status = 'Submitted'
                WHERE lead_id = $11`,
                [
                    income_details.salary_net + income_details.agriculture_net + income_details.remittance_net + income_details.rental_net,
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
                    id, 
                    income_details.salary_net + income_details.agriculture_net + income_details.remittance_net + income_details.rental_net,
                    valuations.fmv, valuations.dv, valuations.recommended_limit, pricing.effective_rate,
                    borrower_details, income_details, collateral_details, risk_assessment, pricing, req.user.id
                ]
            );
        }

        // 2. Calculate Final Retail Score (using the new logic or keeping service)
        // For now, let's just use the CRA score from risk_assessment
        const retail_score = risk_assessment.cra_score;

        // 3. Update Scoring Table
        await db.query(
            "UPDATE lead_scoring SET fcs_score = $1 WHERE lead_id = $2",
            [retail_score, id]
        );

        // 4. Update Lead Status
        await db.query("UPDATE leads SET status = 'Appraised' WHERE lead_id = $1", [id]);

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
            `SELECT a.*, l.customer_name, l.lead_id as lead_identifier 
             FROM lead_appraisals a 
             LEFT JOIN leads l ON a.lead_id = l.lead_id 
             WHERE a.lead_id = $1 OR a.id::text = $1`, 
            [id]
        );
        res.json(result.rows[0]);
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
    const { lead_id } = req.body;
    try {
        // Check if appraisal already exists
        const check = await db.query('SELECT id FROM lead_appraisals WHERE lead_id = $1', [lead_id]);
        if (check.rows.length > 0) {
            return res.status(400).json({ error: 'Appraisal already exists for this lead' });
        }

        // Create a blank appraisal record
        const result = await db.query(
            "INSERT INTO lead_appraisals (lead_id, appraiser_id, appraisal_status) VALUES ($1, $2, 'Draft') RETURNING *",
            [lead_id, req.user.id]
        );
        
        // Update Lead Status
        await db.query("UPDATE leads SET status = 'Appraisal' WHERE lead_id = $1", [lead_id]);

        res.status(201).json(result.rows[0]);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.createBlankAppraisal = async (req, res) => {
    try {
        const result = await db.query(
            "INSERT INTO lead_appraisals (appraisal_status) VALUES ('Draft') RETURNING *"
        );
        res.status(201).json(result.rows[0]);
    } catch (err) {
        console.error('MINIMAL Create Blank Error:', err);
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
