const express = require('express');
const router = express.Router();
const { submitReview, getLeadWorkflow, reappealReview, getAllWorkflows } = require('../controllers/workflowController');
const { auth } = require('../middleware/auth');

router.get('/', auth, getAllWorkflows);
router.post('/review', auth, submitReview);
router.post('/reappeal', auth, reappealReview);
router.get('/:lead_id', auth, getLeadWorkflow);

module.exports = router;
