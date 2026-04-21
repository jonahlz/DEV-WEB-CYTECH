const express = require('express');
const router  = express.Router();
const { authenticate, requireRole } = require('../middleware/authMiddleware');
const db = require('../config/db');
const { updateUserLevel } = require('../controllers/authController');

// GET /api/devices — Liste des objets connectés avec filtres
// Accessible à tous les utilisateurs connectés
router.get('/', authenticate, async (req, res) => {
  try {
    const { search, category_id, status, room_id, brand } = req.query;

    let query = `
      SELECT d.*, dc.name AS category_name, r.name AS room_name
      FROM devices d
      LEFT JOIN device_categories dc ON d.category_id = dc.id
      LEFT JOIN rooms r ON d.room_id = r.id
      WHERE 1=1
    `;
    const params = [];

    // Filtre 1 : recherche par mot-clé (nom, description, code)
    if (search) {
      query += ' AND (d.name LIKE ? OR d.description LIKE ? OR d.unique_code LIKE ?)';
      params.push(`%${search}%`, `%${search}%`, `%${search}%`);
    }
    // Filtre 2 : catégorie
    if (category_id) { query += ' AND d.category_id = ?'; params.push(category_id); }
    // Filtre 3 : statut
    if (status)       { query += ' AND d.status = ?';      params.push(status); }
    // Filtre 4 : pièce
    if (room_id)      { query += ' AND d.room_id = ?';     params.push(room_id); }
    // Filtre 5 : marque
    if (brand)        { query += ' AND d.brand LIKE ?';    params.push(`%${brand}%`); }

    query += ' ORDER BY d.name ASC';

    const [devices] = await db.query(query, params);

    // Logguer la consultation et ajouter des points
    const pointsConsultation = 0.50;
    await db.query(
      'INSERT INTO action_logs (user_id, action_type, target_type, points_earned) VALUES (?, "consultation_objet", "device", ?)',
      [req.user.id, pointsConsultation]
    );
    await db.query(
      'UPDATE users SET points = points + ? WHERE id = ?',
      [pointsConsultation, req.user.id]
    );
    await updateUserLevel(req.user.id);

    res.json(devices);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Erreur serveur.' });
  }
});

// GET /api/devices/:id — Détail d'un objet connecté
router.get('/:id', authenticate, async (req, res) => {
  try {
    const [rows] = await db.query(
      `SELECT d.*, dc.name AS category_name, r.name AS room_name
       FROM devices d
       LEFT JOIN device_categories dc ON d.category_id = dc.id
       LEFT JOIN rooms r ON d.room_id = r.id
       WHERE d.id = ?`,
      [req.params.id]
    );
    if (rows.length === 0) return res.status(404).json({ error: 'Objet non trouvé.' });

    // Récupérer les dernières données capteurs
    const [data] = await db.query(
      'SELECT * FROM device_data WHERE device_id = ? ORDER BY recorded_at DESC LIMIT 20',
      [req.params.id]
    );

    res.json({ ...rows[0], sensor_data: data });
  } catch (err) {
    res.status(500).json({ error: 'Erreur serveur.' });
  }
});

// POST /api/devices — Ajouter un objet (complexe ou admin uniquement)
router.post('/', authenticate, requireRole('complexe', 'administrateur'), async (req, res) => {
  const { unique_code, name, description, category_id, room_id, brand, model, connectivity, power_source, status } = req.body;

  if (!unique_code || !name || !category_id) {
    return res.status(400).json({ error: 'Champs obligatoires manquants (unique_code, name, category_id).' });
  }

  try {
    const [result] = await db.query(
      `INSERT INTO devices (unique_code, name, description, category_id, room_id, brand, model, connectivity, power_source, status, added_by)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [unique_code, name, description || null, category_id, room_id || null, brand || null, model || null, connectivity || 'Wi-Fi', power_source || 'secteur', status || 'actif', req.user.id]
    );
    res.status(201).json({ message: 'Objet ajouté avec succès.', id: result.insertId });
  } catch (err) {
    if (err.code === 'ER_DUP_ENTRY') {
      return res.status(409).json({ error: 'Ce code unique est déjà utilisé.' });
    }
    res.status(500).json({ error: 'Erreur serveur.' });
  }
});

// PUT /api/devices/:id — Modifier un objet (complexe ou admin)
router.put('/:id', authenticate, requireRole('complexe', 'administrateur'), async (req, res) => {
  const { name, description, room_id, status, parameters, brand, model } = req.body;

  try {
    await db.query(
      `UPDATE devices SET name=?, description=?, room_id=?, status=?, parameters=?, brand=?, model=?, updated_at=NOW()
       WHERE id=?`,
      [name, description, room_id || null, status, parameters ? JSON.stringify(parameters) : null, brand, model, req.params.id]
    );
    res.json({ message: 'Objet mis à jour.' });
  } catch (err) {
    res.status(500).json({ error: 'Erreur serveur.' });
  }
});

// DELETE /api/devices/:id — Supprimer (admin uniquement)
router.delete('/:id', authenticate, requireRole('administrateur'), async (req, res) => {
  try {
    await db.query('DELETE FROM devices WHERE id = ?', [req.params.id]);
    res.json({ message: 'Objet supprimé.' });
  } catch (err) {
    res.status(500).json({ error: 'Erreur serveur.' });
  }
});

// POST /api/devices/:id/request-deletion — Demander suppression (complexe)
router.post('/:id/request-deletion', authenticate, requireRole('complexe', 'administrateur'), async (req, res) => {
  try {
    await db.query('UPDATE devices SET deletion_requested = TRUE WHERE id = ?', [req.params.id]);
    res.json({ message: 'Demande de suppression envoyée à l\'administrateur.' });
  } catch (err) {
    res.status(500).json({ error: 'Erreur serveur.' });
  }
});

// GET /api/devices/categories — Liste des catégories
router.get('/meta/categories', async (req, res) => {
  const [cats] = await db.query('SELECT * FROM device_categories ORDER BY name');
  res.json(cats);
});

// GET /api/devices/meta/rooms — Liste des pièces
router.get('/meta/rooms', async (req, res) => {
  const [rooms] = await db.query('SELECT * FROM rooms ORDER BY floor, name');
  res.json(rooms);
});

module.exports = router;
