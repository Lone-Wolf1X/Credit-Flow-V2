const express = require('express');
const router = express.Router();
const { register, login, getMe } = require('../controllers/authController');
const { auth } = require('../middleware/auth');

router.post('/register', register);
router.post('/login', login);
router.post('/logout', auth, require('../controllers/authController').logout);
router.get('/me', auth, getMe);
router.post('/reset-password', auth, require('../controllers/authController').resetPassword);

module.exports = router;
