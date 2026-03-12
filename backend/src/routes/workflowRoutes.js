const express = require('express');
const router = express.Router();
const { submitReview, getLeadWorkflow } = require('../controllers/workflowController');
const { auth } = require('../middleware/auth');

router.post('/review', auth, submitReview);
router.get('/:lead_id', auth, getLeadWorkflow);

module.exports = router;
