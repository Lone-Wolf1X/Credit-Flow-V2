const express = require('express');
const router = express.Router();
const { getAllUsers, getUserByStaffIdOrEmail, updateUser, deleteUser, getAllDesignations, updateDesignationLimit, getAllProvinces } = require('../controllers/userController');
const { auth, authorize } = require('../middleware/auth');

router.get('/provinces', auth, getAllProvinces);
router.post('/transfer-request', auth, require('../controllers/userController').requestTransfer);
router.get('/', auth, authorize(['Admin']), getAllUsers);
router.get('/designations', auth, getAllDesignations);
router.put('/designations/:id', auth, authorize(['Admin']), updateDesignationLimit);
router.get('/search/:identifier', auth, getUserByStaffIdOrEmail);
router.put('/:id', auth, authorize(['Admin']), updateUser);
router.post('/:id/transfer', auth, authorize(['Admin']), require('../controllers/userController').transferUser);
router.post('/:id/reset-password-manual', auth, authorize(['Admin']), require('../controllers/userController').adminResetPasswordManual);
router.post('/:id/reset-password-email', auth, authorize(['Admin']), require('../controllers/userController').adminResetPasswordEmail);
router.delete('/:id', auth, authorize(['Admin']), deleteUser);

module.exports = router;
