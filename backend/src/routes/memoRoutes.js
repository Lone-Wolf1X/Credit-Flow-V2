const express = require('express');
const router = express.Router();
const multer = require('multer');
const path = require('path');
const { createMemo, getMemosByLead, getAllMemos } = require('../controllers/memoController');
const { auth } = require('../middleware/auth');

const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        cb(null, 'uploads/');
    },
    filename: (req, file, cb) => {
        cb(null, `${Date.now()}-${file.originalname}`);
    }
});

const upload = multer({ storage });

router.post('/', auth, upload.single('file'), createMemo);
router.get('/lead/:lead_id', auth, getMemosByLead);

module.exports = router;
