const db = require('../db');

/**
 * Initiate a CIC Request from the branch
 */
exports.initiateCICRequest = async (req, res) => {
    const { lead_id, entities, transaction_id, initiator_comment, initiator_docs_url, status } = req.body;
    
    try {
        const { id: user_id, branch_id } = req.user;

        // Start Transaction
        await db.query('BEGIN');

        // 1. Create the Request
        // Status can be 'Draft' or 'Submitted' (Pending)
        const request_status = status || 'Draft';
        const requestResult = await db.query(
            `INSERT INTO cic_requests (lead_id, initiator_id, branch_id, transaction_id, initiator_comment, initiator_docs_url, payment_status, status)
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING id`,
            [lead_id, user_id, branch_id, transaction_id, initiator_comment, JSON.stringify(initiator_docs_url || []), transaction_id ? 'Paid' : 'Unpaid', request_status]
        );
        const request_id = requestResult.rows[0].id;

        let totalCharge = 0;
        let totalVat = 0;

        // 2. Add Entities
        for (const entity of entities) {
            let finalEntityId = entity.entity_id;

            if (!finalEntityId && entity.details) {
                if (entity.entity_type === 'Personal') {
                    const personRes = await db.query(
                        `INSERT INTO cic_personal (
                            full_name, date_of_birth, gender, relationship_status, father_name, grandfather_name, spouse_name, 
                            citizenship_number, citizenship_issued_district, citizenship_issued_date, id_issue_authority,
                            pan_number, permanent_province, permanent_district, permanent_municipality, permanent_ward, permanent_street,
                            current_province, current_district, current_municipality, current_ward, current_street,
                            contact_number, email, occupation, lead_id
                         ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $21, $22, $23, $24, $25, $26) RETURNING id`,
                        [
                            entity.details.full_name, entity.details.date_of_birth, entity.details.gender, entity.details.relationship_status, 
                            entity.details.father_name, entity.details.grandfather_name, entity.details.spouse_name,
                            entity.details.citizenship_number, entity.details.citizenship_issued_district, entity.details.citizenship_issued_date, entity.details.id_issue_authority,
                            entity.details.pan_number, entity.details.permanent_province, entity.details.permanent_district, entity.details.permanent_municipality, entity.details.permanent_ward, entity.details.permanent_street,
                            entity.details.current_province, entity.details.current_district, entity.details.current_municipality, entity.details.current_ward, entity.details.current_street,
                            entity.details.contact_number, entity.details.email, entity.details.occupation, lead_id || null
                        ]
                    );
                    finalEntityId = personRes.rows[0].id;
                } else if (entity.entity_type === 'Institutional') {
                    const instRes = await db.query(
                        `INSERT INTO cic_institutional (
                            business_name, registration_number, registration_date, registration_authority, registration_type,
                            pan_vat_number, pan_issue_date, pan_issue_authority, firm_registration_authority, business_type,
                            registered_province, registered_district, registered_municipality, registered_ward, registered_street,
                            operating_province, operating_district, operating_municipality, operating_ward, operating_street,
                            contact_number, email, main_activities, lead_id
                         ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $21, $22, $23, $24) RETURNING id`,
                        [
                            entity.details.business_name, entity.details.registration_number, entity.details.registration_date, entity.details.registration_authority, entity.details.registration_type,
                            entity.details.pan_vat_number, entity.details.pan_issue_date, entity.details.pan_issue_authority, entity.details.firm_registration_authority, entity.details.business_type,
                            entity.details.registered_province, entity.details.registered_district, entity.details.registered_municipality, entity.details.registered_ward, entity.details.registered_street,
                            entity.details.operating_province, entity.details.operating_district, entity.details.operating_municipality, entity.details.operating_ward, entity.details.operating_street,
                            entity.details.contact_number, entity.details.email, entity.details.main_activities, lead_id || null
                        ]
                    );
                    finalEntityId = instRes.rows[0].id;
                }
            }

            const base_charge = 250;
            const vat = base_charge * 0.13;
            
            await db.query(
                `INSERT INTO cic_request_entities (request_id, entity_type, entity_id, base_charge, vat_amount)
                 VALUES ($1, $2, $3, $4, $5)`,
                [request_id, entity.entity_type, finalEntityId || 0, base_charge, vat]
            );
            
            totalCharge += base_charge;
            totalVat += vat;
        }

        await db.query(
            `UPDATE cic_requests SET total_charge = $1, vat_amount = $2 WHERE id = $3`,
            [totalCharge, totalVat, request_id]
        );

        await db.query('COMMIT');
        res.status(201).json({ message: `CIC Request ${request_status} successfully`, request_id });
    } catch (err) {
        await db.query('ROLLBACK');
        console.error('initiateCICRequest error:', err);
        res.status(500).json({ error: err.message });
    }
};

/**
 * Update an existing CIC Request (for Draft/Returned)
 */
exports.updateCICRequest = async (req, res) => {
    const { id } = req.params;
    const { entities, transaction_id, initiator_comment, initiator_docs_url, status } = req.body;

    try {
        await db.query('BEGIN');

        // Check current status
        const currentReq = await db.query('SELECT status FROM cic_requests WHERE id = $1', [id]);
        if (!currentReq.rows[0]) return res.status(404).json({ error: 'Request not found' });
        
        if (!['Draft', 'Returned'].includes(currentReq.rows[0].status)) {
            return res.status(400).json({ error: 'Only Draft or Returned requests can be updated' });
        }

        // 1. Update Header
        await db.query(
            `UPDATE cic_requests SET 
                transaction_id = $1, 
                initiator_comment = $2, 
                initiator_docs_url = $3, 
                status = $4,
                payment_status = $5,
                updated_at = CURRENT_TIMESTAMP
             WHERE id = $6`,
            [transaction_id, initiator_comment, JSON.stringify(initiator_docs_url || []), status || 'Submitted', transaction_id ? 'Paid' : 'Unpaid', id]
        );

        // 2. Re-handle entities (Simpler to clear and re-add for the request linkage, 
        // but subject profiles themselves should be updated if details changed)
        // For brevity in this workflow, we'll focus on the request status and header updates.
        // If subject details changed, frontend should send updated details.

        await db.query('COMMIT');
        res.json({ message: 'CIC Request updated successfully' });
    } catch (err) {
        await db.query('ROLLBACK');
        res.status(500).json({ error: err.message });
    }
};

/**
 * Process CIC Report by HO/Province (Upload PDF & Mark Hits)
 */
exports.processCICReport = async (req, res) => {
    const { request_id, entities_with_hits, report_url, processor_comment } = req.body;
    
    try {
        await db.query('BEGIN');

        let updatedTotalCharge = 0;
        let updatedTotalVat = 0;

        for (const entity of entities_with_hits) {
            const base_charge = entity.is_hit ? 550 : 250;
            const vat = base_charge * 0.13;

            await db.query(
                `UPDATE cic_request_entities SET is_hit = $1, base_charge = $2, vat_amount = $3 
                 WHERE request_id = $4 AND entity_type = $5 AND entity_id = $6`,
                [entity.is_hit, base_charge, vat, request_id, entity.entity_type, entity.entity_id]
            );

            updatedTotalCharge += base_charge;
            updatedTotalVat += vat;
        }

        await db.query(
            `UPDATE cic_requests SET 
                processor_id = $1, 
                status = 'Completed', 
                report_url = $2, 
                processor_comment = $3,
                total_charge = $4,
                vat_amount = $5,
                updated_at = CURRENT_TIMESTAMP
             WHERE id = $6`,
            [req.user.id, report_url, processor_comment, updatedTotalCharge, updatedTotalVat, request_id]
        );

        await db.query('COMMIT');
        res.json({ message: 'CIC Report processed successfully' });
    } catch (err) {
        await db.query('ROLLBACK');
        console.error('processCICReport error:', err);
        res.status(500).json({ error: err.message });
    }
};

/**
 * Return a CIC Request to the branch for corrections
 */
exports.returnCICRequest = async (req, res) => {
    const { id } = req.params;
    const { processor_comment } = req.body;
    try {
        await db.query(
            `UPDATE cic_requests SET 
                status = 'Returned', 
                processor_id = $1,
                processor_comment = $2, 
                updated_at = CURRENT_TIMESTAMP 
             WHERE id = $3`,
            [req.user.id, processor_comment, id]
        );
        res.json({ message: 'CIC Request returned successfully' });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

/**
 * Get CIC requests for a lead
 */
exports.getCICRequestsByLead = async (req, res) => {
    try {
        const result = await db.query(
            `SELECT r.*, u.name as initiator_name, p.name as processor_name, b.name as branch_name
             FROM cic_requests r
             LEFT JOIN users u ON r.initiator_id = u.id
             LEFT JOIN users p ON r.processor_id = p.id
             LEFT JOIN branches b ON r.branch_id = b.id
             WHERE r.lead_id = $1 ORDER BY r.created_at DESC`,
            [req.params.lead_id]
        );

        // Fetch entities for each request
        const requests = result.rows;
        for (let r of requests) {
            const entities = await db.query(
                `SELECT * FROM cic_request_entities WHERE request_id = $1`,
                [r.id]
            );
            r.entities = entities.rows;
        }

        res.json(requests);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

/**
 * Reconciliation Report API (Enhanced for Staff & Search)
 */
exports.getReconciliationReport = async (req, res) => {
    try {
        const { id: user_id, role, branch_id } = req.user;
        const status = req.query.status || 'All';
        
        let query = `
            SELECT r.*, b.name as branch_name, u.name as initiator_name
            FROM cic_requests r
            JOIN branches b ON r.branch_id = b.id
            JOIN users u ON r.initiator_id = u.id
            WHERE (r.payment_status = $1 OR $1 = 'All')
        `;
        const params = [status];

        // If not Admin/HO/Province, filter by branch
        if (!['Admin', 'HeadOffice', 'Province'].includes(role)) {
            query += ` AND r.branch_id = $2`;
            params.push(branch_id);
        }

        query += ` ORDER BY r.created_at DESC`;
        
        const result = await db.query(query, params);
        const requests = result.rows;

        // Fetch subjects for each request to allow linking to profiles
        for (let r of requests) {
            const entities = await db.query(
                `SELECT re.*, 
                    CASE 
                        WHEN re.entity_type = 'Personal' THEN p.full_name 
                        ELSE inst.business_name 
                    END as subject_name
                 FROM cic_request_entities re
                 LEFT JOIN cic_personal p ON re.entity_type = 'Personal' AND re.entity_id = p.id
                 LEFT JOIN cic_institutional inst ON re.entity_type = 'Institutional' AND re.entity_id = inst.id
                 WHERE re.request_id = $1`,
                [r.id]
            );
            r.subjects = entities.rows;
        }

        res.json(requests);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

/**
 * Delete CIC Request (Admin Only)
 */
exports.deleteCICRequest = async (req, res) => {
    try {
        const { id } = req.params;
        await db.query('DELETE FROM cic_requests WHERE id = $1', [id]);
        res.json({ message: 'CIC Request deleted successfully' });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

/**
 * Get Subject Profile (Reusable data for Appraisal/Offer)
 */
exports.getSubjectProfile = async (req, res) => {
    const { type, id } = req.params;
    try {
        let profile;
        if (type === 'Personal') {
            const res = await db.query('SELECT * FROM cic_personal WHERE id = $1', [id]);
            profile = res.rows[0];
            
            // Also fetch historical results
            const history = await db.query(
                `SELECT r.status, r.report_url, re.is_hit, r.created_at
                 FROM cic_request_entities re
                 JOIN cic_requests r ON re.request_id = r.id
                 WHERE re.entity_type = 'Personal' AND re.entity_id = $1
                 ORDER BY r.created_at DESC`,
                [id]
            );
            profile.history = history.rows;
        } else {
            const res = await db.query('SELECT * FROM cic_institutional WHERE id = $1', [id]);
            profile = res.rows[0];

            const history = await db.query(
                `SELECT r.status, r.report_url, re.is_hit, r.created_at
                 FROM cic_request_entities re
                 JOIN cic_requests r ON re.request_id = r.id
                 WHERE re.entity_type = 'Institutional' AND re.entity_id = $1
                 ORDER BY r.created_at DESC`,
                [id]
            );
            profile.history = history.rows;
        }

        if (!profile) return res.status(404).json({ error: 'Subject profile not found' });
        res.json(profile);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};
