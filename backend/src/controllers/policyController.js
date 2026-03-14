const db = require('../db');

/**
 * Get all valuation financing policies
 */
exports.getPolicies = async (req, res) => {
    try {
        const result = await db.query('SELECT * FROM valuation_policies ORDER BY loan_segment');
        res.json(result.rows);
    } catch (err) {
        console.error('getPolicies error:', err);
        res.status(500).json({ error: err.message });
    }
};

/**
 * Add or Update policy (Upsert)
 */
exports.savePolicy = async (req, res) => {
    const { loan_segment, collateral_type, max_financing_percentage } = req.body;
    try {
        if (req.user.role !== 'Admin') return res.status(403).json({ error: 'Admin only' });

        const result = await db.query(
            `INSERT INTO valuation_policies (loan_segment, collateral_type, max_financing_percentage)
             VALUES ($1, $2, $3)
             ON CONFLICT (loan_segment, collateral_type) 
             DO UPDATE SET max_financing_percentage = $3, updated_at = CURRENT_TIMESTAMP
             RETURNING *`,
            [loan_segment, collateral_type, max_financing_percentage]
        );

        res.json(result.rows[0]);
    } catch (err) {
        console.error('savePolicy error:', err);
        res.status(500).json({ error: err.message });
    }
};

/**
 * Get valuation payment rules (Fixed vs Percentage)
 */
exports.getPaymentRules = async (req, res) => {
    try {
        const result = await db.query('SELECT * FROM valuation_payment_rules ORDER BY min_loan_amount');
        res.json(result.rows);
    } catch (err) {
        console.error('getPaymentRules error:', err);
        res.status(500).json({ error: err.message });
    }
};

/**
 * Save payment rule
 */
exports.savePaymentRule = async (req, res) => {
    const { rule_type, min_loan_amount, max_loan_amount, field_charge, final_charge } = req.body;
    try {
        if (req.user.role !== 'Admin') return res.status(403).json({ error: 'Admin only' });

        const result = await db.query(
            `INSERT INTO valuation_payment_rules (rule_type, min_loan_amount, max_loan_amount, field_charge, final_charge)
             VALUES ($1, $2, $3, $4, $5) RETURNING *`,
            [rule_type, min_loan_amount, max_loan_amount, field_charge, final_charge]
        );

        res.status(201).json(result.rows[0]);
    } catch (err) {
        console.error('savePaymentRule error:', err);
        res.status(500).json({ error: err.message });
    }
};
