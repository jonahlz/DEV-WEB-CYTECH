const express = require('express');
const router  = express.Router();
const { register, login, validateEmail } = require('../controllers/authController');

router.post('/register',          register);
router.post('/login',             login);
router.get('/validate/:token',    validateEmail);

module.exports = router;
