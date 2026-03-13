const express = require('express');
const router = express.Router();
const { submitAppraisal, getAppraisal, getAllAppraisals, createDirectAppraisal, exportAppraisalDocx, createBlankAppraisal } = require('../controllers/appraisalController');
const { auth } = require('../middleware/auth');

router.get('/', auth, getAllAppraisals);
router.post('/direct', auth, createDirectAppraisal);
router.post('/blank', auth, createBlankAppraisal);
router.get('/export/docx/:id', auth, exportAppraisalDocx);
router.get('/:id', auth, getAppraisal);
router.post('/:id', auth, submitAppraisal);

module.exports = router;
