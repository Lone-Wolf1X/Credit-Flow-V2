const express = require('express');
const router = express.Router();
const { getAuditLogs, exportAuditLogsPDF } = require('../controllers/auditController');
const { auth, authorize } = require('../middleware/auth');

router.get('/', auth, authorize(['Admin']), getAuditLogs);
router.get('/sessions', auth, authorize(['Admin']), require('../controllers/auditController').getSessionLogs);
router.get('/export', auth, authorize(['Admin']), exportAuditLogsPDF);

module.exports = router;
