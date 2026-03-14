const db = require('../db');

/**
 * Generate a unique CAP ID based on loan segment and type
 */
exports.generateCapId = async (loan_segment, loan_type) => {
    try {
        // 1. Get current config and lock for update
        const selectQuery = `
            SELECT id, prefix, counter 
            FROM cap_id_config 
            WHERE loan_segment = $1 AND loan_type = $2
            FOR UPDATE
        `;
        const { rows } = await db.query(selectQuery, [loan_segment, loan_type]);

        if (rows.length === 0) {
            throw new Error(`CAP ID Config not found for ${loan_segment} / ${loan_type}`);
        }

        const config = rows[0];
        const newCounter = config.counter + 1;

        // 2. Update counter
        await db.query(
            'UPDATE cap_id_config SET counter = $1 WHERE id = $2',
            [newCounter, config.id]
        );

        // 3. Format CAP ID: PREFIX-001
        const paddedCounter = String(newCounter).padStart(3, '0');
        return `${config.prefix}-${paddedCounter}`;
    } catch (err) {
        console.error('generateCapId error:', err);
        throw err;
    }
};

/**
 * Determine the approval hierarchy based on loan amount and Hub navigation
 * Path: Assistant -> Branch Manager -> Province Head -> Credit Head -> CEO
 */
exports.getEscalationPath = async (loan_segment, loan_type, amount, current_branch_id, initiator_designation) => {
    try {
        let reviewer = null;
        let approver = null;
        const workflowPath = [];
        
        // 1. Get Branch Info
        const { rows: branchRows } = await db.query(
            'SELECT id, name, hub_type, parent_hub_id FROM branches WHERE id = $1',
            [current_branch_id]
        );
        if (branchRows.length === 0) return { reviewer_designation: 'Branch Manager', approver_designation: 'CEO' };
        
        const branch = branchRows[0];
        
        // Build the potential path
        workflowPath.push({ designation: 'Assistant', hub_id: branch.id });
        workflowPath.push({ designation: 'Branch Manager', hub_id: branch.id });

        if (branch.parent_hub_id) {
            workflowPath.push({ designation: 'Province Head', hub_id: branch.parent_hub_id });
            
            // Get HO from Province
            const { rows: provinceRows } = await db.query('SELECT parent_hub_id FROM branches WHERE id = $1', [branch.parent_hub_id]);
            if (provinceRows.length > 0 && provinceRows[0].parent_hub_id) {
                workflowPath.push({ designation: 'Central Head', hub_id: provinceRows[0].parent_hub_id });
            }
        }

        workflowPath.push({ designation: 'CEO', hub_id: null });

        // Filter out initiator's level and levels below it for "Reviewer"
        const hierarchy = ['Assistant', 'Branch Manager', 'Province Head', 'Central Head', 'CEO'];
        const initiatorIndex = hierarchy.indexOf(initiator_designation || 'Assistant');
        
        // Approver must be at or above the initiator's level, or at least the BM if the initiator is Assistant
        // But the main constraint is the Limit.
        
        // Find Approver based on limits
        for (const step of workflowPath) {
            let limit = 0;
            if (step.designation === 'CEO') {
                limit = 999999999;
            } else {
                const { rows: limitRows } = await db.query(
                    'SELECT default_power_limit FROM designations WHERE name = $1',
                    [step.designation]
                );
                limit = parseFloat(limitRows[0]?.default_power_limit || 0);
            }

            if (limit >= amount) {
                approver = step.designation === 'Central Head' ? 'Credit Head' : step.designation;
                
                // Reviewer should be one step above the initiator, or the BM if it is Assistant.
                // If BM initiates, reviewer is Province Head.
                if (initiatorIndex < 1) { // Assistant
                    reviewer = 'Branch Manager';
                } else if (initiatorIndex < 2) { // Branch Manager
                    reviewer = 'Province Head';
                } else if (initiatorIndex < 3) { // Province Head
                    reviewer = 'Credit Head';
                } else {
                    reviewer = 'CEO';
                }

                // Approver cannot be below Reviewer
                const reviewerIndex = hierarchy.indexOf(reviewer === 'Credit Head' ? 'Central Head' : reviewer);
                const approverIndexInHierarchy = hierarchy.indexOf(step.designation);
                
                if (approverIndexInHierarchy < reviewerIndex) {
                    approver = (reviewer === 'Central Head') ? 'Credit Head' : reviewer;
                }
                break;
            }
        }

        return {
            reviewer_designation: reviewer || 'Branch Manager',
            approver_designation: approver || 'CEO'
        };
    } catch (err) {
        console.error('getEscalationPath error:', err);
        throw err;
    }
};
