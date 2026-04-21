const express = require('express');
const router  = express.Router();
const bcrypt  = require('bcrypt');
const { authenticate } = require('../middleware/authMiddleware');
const db = require('../config/db');

// GET /api/users/me — Profil de l'utilisateur connecté (privé + public)
router.get('/me', authenticate, async (req, res) => {
  const [rows] = await db.query(
    'SELECT id, login, email, first_name, last_name, age, gender, birth_date, member_type, photo_url, role, type_level, points, last_login, created_at FROM users WHERE id = ?',
    [req.user.id]
  );
  if (rows.length === 0) return res.status(404).json({ error: 'Utilisateur non trouvé.' });
  res.json(rows[0]);
});

// GET /api/users/:id — Profil public d'un utilisateur
router.get('/:id', authenticate, async (req, res) => {
  const [rows] = await db.query(
    'SELECT id, login, age, gender, birth_date, member_type, photo_url, role, type_level FROM users WHERE id = ?',
    [req.params.id]
  );
  if (rows.length === 0) return res.status(404).json({ error: 'Utilisateur non trouvé.' });
  res.json(rows[0]);
});

// GET /api/users — Liste de tous les membres (profils publics)
router.get('/', authenticate, async (req, res) => {
  const [rows] = await db.query(
    'SELECT id, login, age, gender, member_type, photo_url, role, type_level FROM users WHERE is_approved = TRUE ORDER BY login'
  );
  res.json(rows);
});

// PUT /api/users/me — Modifier son propre profil
router.put('/me', authenticate, async (req, res) => {
  const { first_name, last_name, age, gender, birth_date, member_type, photo_url, password } = req.body;

  try {
    let password_hash = undefined;
    if (password) {
      password_hash = await bcrypt.hash(password, 10);
    }

    await db.query(
      `UPDATE users SET
        first_name  = COALESCE(?, first_name),
        last_name   = COALESCE(?, last_name),
        age         = COALESCE(?, age),
        gender      = COALESCE(?, gender),
        birth_date  = COALESCE(?, birth_date),
        member_type = COALESCE(?, member_type),
        photo_url   = COALESCE(?, photo_url)
        ${password_hash ? ', password_hash = ?' : ''}
       WHERE id = ?`,
      [
        first_name || null, last_name || null, age || null, gender || null,
        birth_date || null, member_type || null, photo_url || null,
        ...(password_hash ? [password_hash] : []),
        req.user.id
      ]
    );

    res.json({ message: 'Profil mis à jour avec succès.' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Erreur serveur.' });
  }
});

// GET /api/users/me/stats — Points et historique de l'utilisateur connecté
router.get('/me/stats', authenticate, async (req, res) => {
  const [user]  = await db.query('SELECT points, type_level, role FROM users WHERE id = ?', [req.user.id]);
  const [logs]  = await db.query(
    'SELECT action_type, points_earned, created_at FROM action_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 50',
    [req.user.id]
  );
  res.json({ ...user[0], history: logs });
});

module.exports = router;
