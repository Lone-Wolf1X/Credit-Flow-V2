const express = require('express');
const router = express.Router();
const cicController = require('../controllers/cicController');
const { auth, authorize } = require('../middleware/auth');

router.post('/initiate', auth, cicController.initiateCICRequest);
router.put('/:id', auth, cicController.updateCICRequest);
router.post('/process', auth, cicController.processCICReport);
router.post('/:id/return', auth, cicController.returnCICRequest);
router.get('/lead/:lead_id', auth, cicController.getCICRequestsByLead);
router.get('/reconciliation', auth, authorize(['Admin', 'Province', 'HeadOffice', 'Branch Staff', 'Staff']), cicController.getReconciliationReport);
router.get('/profiles/:type/:id', auth, cicController.getSubjectProfile);
router.delete('/:id', auth, authorize(['Admin']), cicController.deleteCICRequest);

module.exports = router;
