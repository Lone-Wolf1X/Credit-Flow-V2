const db = require('../db');

exports.createMemo = async (req, res) => {
    const { lead_id, department, branch, category, content } = req.body;
    const creator_id = req.user.id;
    const file_url = req.file ? `/uploads/${req.file.filename}` : null;

    try {
        // Sequential ID generation: DEPT-BRANCH-CAT-SEQ
        const countResult = await db.query('SELECT COUNT(*) FROM memos WHERE department = $1 AND branch = $2 AND category = $3', [department, branch, category]);
        const seq = parseInt(countResult.rows[0].count) + 1;
        const memo_id = `${department}-${branch}-${category}-${String(seq).padStart(4, '0')}`;

        const result = await db.query(
            'INSERT INTO memos (memo_id, lead_id, department, branch, category, content, file_url, creator_id) VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING *',
            [memo_id, lead_id, department, branch, category, content, file_url, creator_id]
        );

        res.status(201).json(result.rows[0]);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.getMemosByLead = async (req, res) => {
    const { lead_id } = req.params;
    try {
        const result = await db.query('SELECT m.*, u.name as creator_name FROM memos m JOIN users u ON m.creator_id = u.id WHERE m.lead_id = $1', [lead_id]);
        res.json(result.rows);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.getAllMemos = async (req, res) => {
    try {
        const result = await db.query('SELECT m.*, u.name as creator_name, l.customer_name FROM memos m JOIN users u ON m.creator_id = u.id JOIN leads l ON m.lead_id = l.lead_id ORDER BY m.created_at DESC');
        res.json(result.rows);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};
