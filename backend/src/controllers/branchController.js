const db = require('../db');

exports.createBranch = async (req, res) => {
    const { name, short_name, sol_id, location, province } = req.body;
    try {
        const result = await db.query(
            'INSERT INTO branches (name, short_name, sol_id, location, province) VALUES ($1, $2, $3, $4, $5) RETURNING *',
            [name, short_name, sol_id, location, province]
        );
        res.status(201).json(result.rows[0]);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.updateBranch = async (req, res) => {
    const { id } = req.params;
    const { name, short_name, sol_id, location, province } = req.body;
    try {
        const result = await db.query(
            'UPDATE branches SET name = $1, short_name = $2, sol_id = $3, location = $4, province = $5 WHERE id = $6 RETURNING *',
            [name, short_name, sol_id, location, province, id]
        );
        res.json(result.rows[0]);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.deleteBranch = async (req, res) => {
    const { id } = req.params;
    try {
        await db.query('DELETE FROM branches WHERE id = $1', [id]);
        res.json({ message: 'Branch deleted successfully' });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.getAllBranches = async (req, res) => {
    try {
        const result = await db.query('SELECT * FROM branches');
        res.json(result.rows);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};
