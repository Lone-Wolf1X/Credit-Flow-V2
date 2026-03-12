const db = require('../db');
const bcrypt = require('bcrypt');

exports.getAllProvinces = async (req, res) => {
    try {
        const result = await db.query('SELECT * FROM provinces ORDER BY name ASC');
        res.json(result.rows);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.getAllDesignations = async (req, res) => {
    try {
        const result = await db.query('SELECT * FROM designations ORDER BY name ASC');
        res.json(result.rows);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.getAllUsers = async (req, res) => {
    try {
        const result = await db.query(`
            SELECT u.id, u.name, u.email, u.staff_id, u.role, u.branch_id, u.designation, u.limit_power, u.province_id,
                   b.name as branch_name, b.sol_id, p.name as province_name
            FROM users u
            LEFT JOIN branches b ON u.branch_id = b.id
            LEFT JOIN provinces p ON u.province_id = p.id
        `);
        res.json(result.rows);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.updateDesignationLimit = async (req, res) => {
    const { id } = req.params;
    const { default_power_limit } = req.body;
    try {
        const result = await db.query(
            'UPDATE designations SET default_power_limit = $1 WHERE id = $2 RETURNING *',
            [default_power_limit, id]
        );
        res.json(result.rows[0]);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.getUserByStaffIdOrEmail = async (req, res) => {
    const { identifier } = req.params;
    try {
        const result = await db.query(
            'SELECT id, name, email, staff_id, role, designation, limit_power FROM users WHERE staff_id = $1 OR email = $2',
            [identifier, identifier]
        );
        if (result.rows.length === 0) {
            return res.status(404).json({ error: 'User not found' });
        }
        res.json(result.rows[0]);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.updateUser = async (req, res) => {
    const { id } = req.params;
    const { name, email, staff_id, role, branch_id, designation, province_id } = req.body;
    try {
        const result = await db.query(
            'UPDATE users SET name = $1, email = $2, staff_id = $3, role = $4, branch_id = $5, designation = $6, province_id = $7 WHERE id = $8 RETURNING id, name, email, staff_id, role, branch_id, designation, province_id',
            [name, email, staff_id, role, branch_id, designation, province_id, id]
        );
        res.json(result.rows[0]);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.transferUser = async (req, res) => {
    const { id } = req.params;
    const { branch_id, type, end_date } = req.body;
    try {
        // 1. Update user's current branch
        const result = await db.query(
            'UPDATE users SET branch_id = $1 WHERE id = $2 RETURNING *',
            [branch_id, id]
        );

        // 2. Record the assignment
        await db.query(
            'INSERT INTO branch_assignments (user_id, branch_id, type, start_date, end_date) VALUES ($1, $2, $3, CURRENT_DATE, $4)',
            [id, branch_id, type, end_date || null]
        );

        res.json({ message: `User transferred successfully as ${type}`, user: result.rows[0] });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.deleteUser = async (req, res) => {
    const { id } = req.params;
    try {
        await db.query('DELETE FROM users WHERE id = $1', [id]);
        res.json({ message: 'User deleted successfully' });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.adminResetPasswordManual = async (req, res) => {
    const { id } = req.params;
    const { newPassword } = req.body;
    try {
        const salt = await bcrypt.genSalt(10);
        const hashedPassword = await bcrypt.hash(newPassword, salt);
        await db.query(
            'UPDATE users SET password = $1, must_reset_password = false WHERE id = $2',
            [hashedPassword, id]
        );
        res.json({ message: 'Password reset successfully (Manual)' });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.adminResetPasswordEmail = async (req, res) => {
    const { id } = req.params;
    try {
        const tempPassword = Math.random().toString(36).slice(-8); // Random 8-char password
        const salt = await bcrypt.genSalt(10);
        const hashedPassword = await bcrypt.hash(tempPassword, salt);
        
        const userRes = await db.query(
            'UPDATE users SET password = $1, must_reset_password = true WHERE id = $2 RETURNING email, name',
            [hashedPassword, id]
        );
        
        const user = userRes.rows[0];
        const { sendPasswordResetEmail } = require('../services/emailService');
        await sendPasswordResetEmail(user.email, tempPassword);

        res.json({ message: 'Temporary password sent to user email' });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.requestTransfer = async (req, res) => {
    const { target_branch_id, target_province_id, reason } = req.body;
    try {
        await db.query(
            'INSERT INTO transfer_requests (user_id, target_branch_id, target_province_id, reason) VALUES ($1, $2, $3, $4)',
            [req.user.id, target_branch_id, target_province_id, reason]
        );
        res.json({ message: 'Transfer request submitted successfully' });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};
