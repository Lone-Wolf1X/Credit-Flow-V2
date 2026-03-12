const db = require('../db');
const PDFDocument = require('pdfkit');
const fs = require('fs');
const path = require('path');

exports.logAction = async (user_id, action, details) => {
    try {
        await db.query('INSERT INTO audit_logs (user_id, action, details) VALUES ($1, $2, $3)', [user_id, action, JSON.stringify(details)]);
    } catch (err) {
        console.error('Audit log failed:', err.message);
    }
};

exports.getAuditLogs = async (req, res) => {
    try {
        const result = await db.query('SELECT a.*, u.name as user_name FROM audit_logs a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC');
        res.json(result.rows);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.exportAuditLogsPDF = async (req, res) => {
    try {
        const result = await db.query('SELECT a.*, u.name as user_name FROM audit_logs a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC');
        const logs = result.rows;

        const doc = new PDFDocument();
        const filename = `audit_logs_${Date.now()}.pdf`;
        const filePath = path.join(__dirname, '../../uploads', filename);
        
        doc.pipe(fs.createWriteStream(filePath));
        doc.pipe(res); // Also stream to response

        doc.fontSize(20).text('Audit Logs Report', { align: 'center' });
        doc.moveDown();

        logs.forEach(log => {
            doc.fontSize(12).text(`User: ${log.user_name} | Action: ${log.action} | Time: ${log.created_at}`);
            doc.fontSize(10).text(`Details: ${JSON.stringify(log.details)}`);
            doc.moveDown();
        });

        doc.end();
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.getSessionLogs = async (req, res) => {
    try {
        const result = await db.query('SELECT s.*, u.name as user_name FROM session_logs s LEFT JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC');
        res.json(result.rows);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};
