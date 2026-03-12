const express = require('express');
const router = express.Router();
const { submitAppraisal, getAppraisal } = require('../controllers/appraisalController');
const { auth } = require('../middleware/auth');

router.post('/:id', auth, submitAppraisal);
router.get('/:id', auth, getAppraisal);

module.exports = router;
