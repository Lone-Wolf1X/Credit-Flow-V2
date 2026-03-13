const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
const db = require('../db');
require('dotenv').config();

exports.register = async (req, res) => {
    const { name, email, staff_id, password, role, branch_id, designation, province_id, limit_power } = req.body;
    try {
        const userExists = await db.query('SELECT * FROM users WHERE email = $1 OR staff_id = $2', [email, staff_id]);
        if (userExists.rows.length > 0) return res.status(400).json({ message: 'User already exists' });

        const salt = await bcrypt.genSalt(10);
        const hashedPassword = await bcrypt.hash(password, salt);

        const newUserResult = await db.query(
            'INSERT INTO users (name, email, staff_id, password, role, branch_id, designation, province_id, limit_power) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9) RETURNING id',
            [name, email, staff_id, hashedPassword, role, branch_id, designation, province_id, limit_power || 0]
        );
        const user = newUserResult.rows[0];

        const token = jwt.sign({ id: user.id, role: newUserResult.rows[0].role || role, branch_id: branch_id }, process.env.JWT_SECRET, { expiresIn: '1d' });

        // Log session
        await db.query(
            'INSERT INTO session_logs (user_id, action, ip_address, user_agent) VALUES ($1, $2, $3, $4)',
            [user.id, 'LOGIN', req.ip, req.headers['user-agent']]
        );

        res.json({ token, user: { id: user.id, name: user.name, email: user.email, role: user.role, must_reset_password: user.must_reset_password } });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.login = async (req, res) => {
    const { identifier, password } = req.body; // identifier can be email or staff_id
    try {
        const user = await db.query('SELECT * FROM users WHERE email = $1 OR staff_id = $2', [identifier, identifier]);
        if (user.rows.length === 0) return res.status(400).json({ message: 'Invalid credentials' });

        const isMatch = await bcrypt.compare(password, user.rows[0].password);
        if (!isMatch) return res.status(400).json({ message: 'Invalid credentials' });

        const token = jwt.sign(
            { id: user.rows[0].id, role: user.rows[0].role, branch_id: user.rows[0].branch_id }, 
            process.env.JWT_SECRET, 
            { expiresIn: '1h' }
        );
        res.json({ 
            token, 
            user: { 
                id: user.rows[0].id, 
                name: user.rows[0].name, 
                email: user.rows[0].email, 
                role: user.rows[0].role,
                must_reset_password: user.rows[0].must_reset_password
            } 
        });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.logout = async (req, res) => {
    try {
        if (req.user) {
            await db.query(
                'INSERT INTO session_logs (user_id, action, ip_address, user_agent) VALUES ($1, $2, $3, $4)',
                [req.user.id, 'LOGOUT', req.ip, req.headers['user-agent']]
            );
        }
        res.json({ message: 'Logged out successfully' });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.getMe = async (req, res) => {
    try {
        const user = await db.query(`
            SELECT u.id, u.name, u.email, u.staff_id, u.role, u.branch_id, u.designation, u.province_id, u.must_reset_password, 
                   u.limit_power as custom_limit,
                   d.default_power_limit as designation_limit,
                   GREATEST(COALESCE(u.limit_power, 0), COALESCE(d.default_power_limit, 0)) as power_limit,
                   b.name as branch_name, b.sol_id, p.name as province_name
            FROM users u
            LEFT JOIN branches b ON u.branch_id = b.id
            LEFT JOIN provinces p ON u.province_id = p.id
            LEFT JOIN designations d ON u.designation = d.name
            WHERE u.id = $1
        `, [req.user.id]);
        res.json(user.rows[0]);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.resetPassword = async (req, res) => {
    const { newPassword } = req.body;
    try {
        const salt = await bcrypt.genSalt(10);
        const hashedPassword = await bcrypt.hash(newPassword, salt);
        await db.query('UPDATE users SET password = $1, must_reset_password = FALSE WHERE id = $2', [hashedPassword, req.user.id]);
        res.json({ message: 'Password reset successfully' });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};
