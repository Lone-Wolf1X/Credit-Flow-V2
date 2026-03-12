const express = require('express');
const router = express.Router();
const { createBranch, getAllBranches } = require('../controllers/branchController');
const { auth, authorize } = require('../middleware/auth');

router.post('/', auth, authorize(['Admin']), createBranch);
router.get('/', auth, getAllBranches);
router.put('/:id', auth, authorize(['Admin']), require('../controllers/branchController').updateBranch);
router.delete('/:id', auth, authorize(['Admin']), require('../controllers/branchController').deleteBranch);

module.exports = router;
