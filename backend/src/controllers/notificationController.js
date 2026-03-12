const db = require('../db');

exports.getNotifications = async (req, res) => {
    const user_id = req.user.id;
    try {
        const result = await db.query('SELECT * FROM notifications WHERE user_id = $1 ORDER BY created_at DESC', [user_id]);
        res.json(result.rows);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.markAsRead = async (req, res) => {
    const { id } = req.params;
    try {
        await db.query('UPDATE notifications SET is_read = TRUE WHERE id = $1', [id]);
        res.json({ message: 'Notification marked as read' });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};
