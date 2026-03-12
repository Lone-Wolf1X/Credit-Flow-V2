const db = require('../db');
const scoringService = require('../services/scoringService');

exports.submitAppraisal = async (req, res) => {
    const { id } = req.params; // lead_id
    const { 
        monthly_income, monthly_expenses, emi_outflow, other_bfis_exposure,
        fair_market_value, distress_value, collateral_location,
        cra_score, mitigating_factors, unit_inspection_notes,
        recommended_limit, interest_rate, tenure_months
    } = req.body;

    try {
        // 1. Save Appraisal Data
        await db.query(
            `INSERT INTO lead_appraisals (
                lead_id, monthly_income, monthly_expenses, emi_outflow, other_bfis_exposure,
                fair_market_value, distress_value, collateral_location,
                cra_score, mitigating_factors, unit_inspection_notes,
                recommended_limit, interest_rate, tenure_months, appraiser_id
            ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15)`,
            [
                id, monthly_income, monthly_expenses, emi_outflow, other_bfis_exposure,
                fair_market_value, distress_value, collateral_location,
                cra_score, mitigating_factors, unit_inspection_notes,
                recommended_limit, interest_rate, tenure_months, req.user.id
            ]
        );

        // 2. Calculate Final Retail Score
        const retail_score = scoringService.calculateRetailScore(req.body);

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
        const result = await db.query('SELECT * FROM lead_appraisals WHERE lead_id = $1', [id]);
        res.json(result.rows[0]);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};
