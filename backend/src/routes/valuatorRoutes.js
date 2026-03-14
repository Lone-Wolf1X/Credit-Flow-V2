const express = require('express');
const router = express.Router();
const valuatorController = require('../controllers/valuatorController');
const { auth } = require('../middleware/auth');

router.get('/', auth, valuatorController.getValuators);
router.post('/onboard', auth, valuatorController.onboardValuator);
router.put('/:id/status', auth, valuatorController.updateValuatorStatus);
router.post('/assign', auth, valuatorController.assignRandomValuator);
router.get('/assignments', auth, valuatorController.getAssignments);

module.exports = router;
