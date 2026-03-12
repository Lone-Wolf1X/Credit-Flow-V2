/**
 * Advanced Scoring Engine for Nepal Banking Credit Flow
 * Handles Phase 1 (Lead Qualification) and Phase 2 (Staff Verification)
 */

const calculateLQS = (data) => {
    let score = 50; // Base score to adjust from
    
    // 1. Income Source Stability (Max 20 points above/below base)
    const stabilityWeights = {
        'Government': 20,
        'Private': 10,
        'Foreign': 8,
        'Business': 15,
        'Agriculture': 5,
        'Self-Employed': 0
    };
    score += (stabilityWeights[data.income_source] || 0);

    // 2. Risk Flags (Deductions)
    if (data.is_pep) score -= 20;
    if (data.has_legal_dispute) score -= 30;

    // 3. Family Burden (Max 10 points)
    const familySize = parseInt(data.undivided_family_members || 1);
    if (familySize <= 4) score += 10;
    else if (familySize <= 7) score += 5;

    // 4. Repayment Capacity (Max 30 points)
    const totalMonthlyIncome = (parseFloat(data.primary_income || 0) + 
                                parseFloat(data.secondary_income || 0) + 
                                parseFloat(data.other_income_amount || 0));
    
    const proposedLimit = parseFloat(data.proposed_limit || 0);
    // Rough estimate: If monthly income > 5% of proposed limit, it's very safe
    const ratio = proposedLimit > 0 ? (totalMonthlyIncome / proposedLimit) : 0;
    
    if (ratio > 0.1) score += 30;
    else if (ratio > 0.05) score += 20;
    else if (ratio > 0.02) score += 10;

    // 5. Banking Relationship
    if (data.is_existing_customer) score += 10;

    return Math.max(0, Math.min(score, 100));
};

const calculateSVS = (verifiedData) => {
    let score = 0;
    
    // 1. Documentation Verification (Max 40 points)
    if (verifiedData.kyc_status === 'Verified') score += 20;
    if (verifiedData.income_proof === 'Verified') score += 20;

    // 2. CIB Analysis (Max 30 points)
    if (verifiedData.cib_report_status === 'Clean') score += 30;
    else if (verifiedData.cib_report_status === 'Minor Overdue') score += 15;

    // 3. Interview & Notes Quality (Max 30 points)
    if (verifiedData.verification_notes && verifiedData.verification_notes.length > 50) score += 30;
    else if (verifiedData.verification_notes) score += 15;

    return Math.min(score, 100);
};

const detectDeviations = (lead, verified) => {
    const alerts = [];
    let deviationSum = 0;
    let count = 0;

    // 1. Income Deviation
    const declaredTotalIncome = (parseFloat(lead.primary_income || 0) + 
                                 parseFloat(lead.secondary_income || 0) + 
                                 parseFloat(lead.other_income_amount || 0));
    
    if (declaredTotalIncome > 0 && verified.verified_income !== undefined) {
        const diff = Math.abs(declaredTotalIncome - verified.verified_income);
        const percent = (diff / declaredTotalIncome) * 100;
        if (percent > 15) {
            alerts.push(`Income Discrepancy: ${percent.toFixed(1)}%`);
            deviationSum += percent;
        }
        count++;
    }

    // 2. Collateral Deviation
    const declaredCollateral = parseFloat(lead.estimated_collateral_value || 0);
    if (declaredCollateral > 0 && verified.verified_collateral_value !== undefined) {
        const diff = Math.abs(declaredCollateral - verified.verified_collateral_value);
        const percent = (diff / declaredCollateral) * 100;
        if (percent > 25) {
            alerts.push(`Collateral Valuation Gap: ${percent.toFixed(1)}%`);
            deviationSum += percent;
        }
        count++;
    }

    const avgDeviation = count > 0 ? deviationSum / count : 0;
    
    let riskCategory = 'Low';
    if (avgDeviation > 50 || lead.has_legal_dispute) riskCategory = 'Critical';
    else if (avgDeviation > 25 || lead.is_pep) riskCategory = 'High';
    else if (avgDeviation > 10) riskCategory = 'Moderate';

    return {
        avgDeviation,
        alerts,
        riskCategory
    };
};

const calculateRetailScore = (appraisalData) => {
    let score = 0;
    
    // 1. Income / DTI (Max 30 points)
    const income = parseFloat(appraisalData.monthly_income || 0);
    const outflow = parseFloat(appraisalData.emi_outflow || 0) + parseFloat(appraisalData.monthly_expenses || 0);
    const dti = income > 0 ? (outflow / income) : 1;
    
    if (dti < 0.4) score += 30;
    else if (dti < 0.6) score += 20;
    else if (dti < 0.8) score += 10;

    // 2. Collateral / LTV (Max 30 points)
    const limit = parseFloat(appraisalData.recommended_limit || 0);
    const fmv = parseFloat(appraisalData.fair_market_value || 1);
    const ltv = (limit / fmv);
    
    if (ltv < 0.5) score += 30;
    else if (ltv < 0.7) score += 20;
    else if (ltv < 0.9) score += 10;

    // 3. Risk / CRA (Max 40 points)
    const cra = parseInt(appraisalData.cra_score || 0);
    score += (cra / 100) * 40;

    return Math.round(score);
};

module.exports = {
    calculateLQS,
    calculateSVS,
    detectDeviations,
    calculateRetailScore
};
