const express = require('express');
const router = express.Router();
const { 
    createLead, getLeads, getPerformanceScores,
} = require('../controllers/leadController');
const { auth } = require('../middleware/auth');
const multer = require('multer');
const upload = multer({ dest: 'uploads/' });

// Core Lead Routes
router.post('/', auth, createLead);
router.get('/', auth, getLeads);
router.get('/scores', auth, getPerformanceScores);

// Specialized Scoring & Detail Routes
router.get('/:id/details', auth, (req, res) => require('../controllers/leadController').getLeadDetails(req, res));
router.post('/:id/verify', auth, (req, res) => require('../controllers/leadController').submitVerification(req, res));

module.exports = router;
