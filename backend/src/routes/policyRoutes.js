const express = require('express');
const router = express.Router();
const policyController = require('../controllers/policyController');
const { auth } = require('../middleware/auth');

router.get('/valuation', auth, policyController.getPolicies);
router.post('/valuation', auth, policyController.savePolicy);
router.get('/payments', auth, policyController.getPaymentRules);
router.post('/payments', auth, policyController.savePaymentRule);

module.exports = router;
