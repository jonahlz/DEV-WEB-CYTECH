const express = require('express');
const router  = express.Router();
const { authenticate, requireRole } = require('../middleware/authMiddleware');
const db = require('../config/db');

// Toutes les routes admin nécessitent d'être authentifié + rôle administrateur
router.use(authenticate);
router.use(requireRole('administrateur'));

// GET /api/admin/users — Liste complète des utilisateurs
router.get('/users', async (req, res) => {
  const [rows] = await db.query(
    'SELECT id, login, email, first_name, last_name, role, type_level, points, is_validated, is_approved, last_login, created_at FROM users ORDER BY created_at DESC'
  );
  res.json(rows);
});

// GET /api/admin/users/pending — Utilisateurs en attente d'approbation
router.get('/users/pending', async (req, res) => {
  const [rows] = await db.query(
    'SELECT id, login, email, first_name, last_name, member_type, created_at FROM users WHERE is_validated = TRUE AND is_approved = FALSE'
  );
  res.json(rows);
});

// PUT /api/admin/users/:id/approve — Approuver un utilisateur
router.put('/users/:id/approve', async (req, res) => {
  await db.query('UPDATE users SET is_approved = TRUE WHERE id = ?', [req.params.id]);
  res.json({ message: 'Utilisateur approuvé.' });
});

// PUT /api/admin/users/:id/level — Modifier manuellement le niveau
router.put('/users/:id/level', async (req, res) => {
  const { type_level, role } = req.body;
  const validLevels = ['debutant', 'intermediaire', 'avance', 'expert'];
  const validRoles  = ['simple', 'complexe', 'administrateur'];

  if (!validLevels.includes(type_level) || !validRoles.includes(role)) {
    return res.status(400).json({ error: 'Niveau ou rôle invalide.' });
  }

  await db.query('UPDATE users SET type_level = ?, role = ? WHERE id = ?', [type_level, role, req.params.id]);
  res.json({ message: 'Niveau mis à jour.' });
});

// DELETE /api/admin/users/:id — Supprimer un utilisateur
router.delete('/users/:id', async (req, res) => {
  if (req.params.id == req.user.id) {
    return res.status(400).json({ error: 'Impossible de supprimer son propre compte.' });
  }
  await db.query('DELETE FROM users WHERE id = ?', [req.params.id]);
  res.json({ message: 'Utilisateur supprimé.' });
});

// GET /api/admin/devices/deletion-requests — Demandes de suppression d'objets
router.get('/devices/deletion-requests', async (req, res) => {
  const [rows] = await db.query(
    `SELECT d.*, dc.name AS category_name, u.login AS added_by_login
     FROM devices d
     LEFT JOIN device_categories dc ON d.category_id = dc.id
     LEFT JOIN users u ON d.added_by = u.id
     WHERE d.deletion_requested = TRUE`
  );
  res.json(rows);
});

// GET /api/admin/stats — Statistiques globales de la plateforme
router.get('/stats', async (req, res) => {
  const [[userCount]]   = await db.query('SELECT COUNT(*) AS total FROM users WHERE is_approved = TRUE');
  const [[deviceCount]] = await db.query('SELECT COUNT(*) AS total FROM devices');
  const [[activeCount]] = await db.query('SELECT COUNT(*) AS total FROM devices WHERE status = "actif"');
  const [[logCount]]    = await db.query('SELECT COUNT(*) AS total FROM action_logs WHERE DATE(created_at) = CURDATE()');
  const [topDevices]    = await db.query(
    'SELECT target_id, COUNT(*) AS consultations FROM action_logs WHERE target_type = "device" GROUP BY target_id ORDER BY consultations DESC LIMIT 5'
  );

  res.json({
    users_total:        userCount.total,
    devices_total:      deviceCount.total,
    devices_active:     activeCount.total,
    actions_today:      logCount.total,
    top_devices:        topDevices
  });
});

// GET /api/admin/logs — Historique complet des actions
router.get('/logs', async (req, res) => {
  const { user_id, action_type, limit = 100 } = req.query;
  let query  = 'SELECT al.*, u.login FROM action_logs al JOIN users u ON al.user_id = u.id WHERE 1=1';
  const params = [];

  if (user_id)     { query += ' AND al.user_id = ?';      params.push(user_id); }
  if (action_type) { query += ' AND al.action_type = ?';  params.push(action_type); }

  query += ' ORDER BY al.created_at DESC LIMIT ?';
  params.push(parseInt(limit));

  const [logs] = await db.query(query, params);
  res.json(logs);
});

module.exports = router;
