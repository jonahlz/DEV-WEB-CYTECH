const bcrypt  = require('bcrypt');
const jwt     = require('jsonwebtoken');
const crypto  = require('crypto');
const db      = require('../config/db');

// ─── INSCRIPTION ──────────────────────────────────────────────────────────────
exports.register = async (req, res) => {
  const { login, email, password, first_name, last_name, age, gender, birth_date, member_type } = req.body;

  if (!login || !email || !password || !first_name || !last_name) {
    return res.status(400).json({ error: 'Champs obligatoires manquants.' });
  }

  try {
    // Vérifier si login ou email déjà pris
    const [existing] = await db.query(
      'SELECT id FROM users WHERE login = ? OR email = ?', [login, email]
    );
    if (existing.length > 0) {
      return res.status(409).json({ error: 'Ce login ou email est déjà utilisé.' });
    }

    const password_hash     = await bcrypt.hash(password, 10);
    const validation_token  = crypto.randomBytes(32).toString('hex');

    await db.query(
      `INSERT INTO users (login, email, password_hash, first_name, last_name, age, gender, birth_date, member_type, validation_token)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [login, email, password_hash, first_name, last_name, age || null, gender || null, birth_date || null, member_type || 'membre', validation_token]
    );

    // TODO: envoyer l'email de validation avec le lien contenant validation_token
    // sendValidationEmail(email, validation_token);

    res.status(201).json({ message: 'Inscription réussie. Vérifiez votre email pour valider votre compte.' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Erreur serveur lors de l\'inscription.' });
  }
};

// ─── VALIDATION EMAIL ─────────────────────────────────────────────────────────
exports.validateEmail = async (req, res) => {
  const { token } = req.params;

  try {
    const [rows] = await db.query('SELECT id FROM users WHERE validation_token = ?', [token]);
    if (rows.length === 0) {
      return res.status(400).json({ error: 'Token de validation invalide.' });
    }

    await db.query(
      'UPDATE users SET is_validated = TRUE, validation_token = NULL WHERE id = ?',
      [rows[0].id]
    );

    res.json({ message: 'Email validé. En attente d\'approbation par l\'administrateur.' });
  } catch (err) {
    res.status(500).json({ error: 'Erreur serveur.' });
  }
};

// ─── CONNEXION ────────────────────────────────────────────────────────────────
exports.login = async (req, res) => {
  const { login, password } = req.body;

  if (!login || !password) {
    return res.status(400).json({ error: 'Login et mot de passe requis.' });
  }

  try {
    const [rows] = await db.query('SELECT * FROM users WHERE login = ?', [login]);
    if (rows.length === 0) {
      return res.status(401).json({ error: 'Login ou mot de passe incorrect.' });
    }

    const user = rows[0];

    if (!user.is_validated) {
      return res.status(403).json({ error: 'Compte non validé. Vérifiez votre email.' });
    }
    if (!user.is_approved) {
      return res.status(403).json({ error: 'Compte en attente d\'approbation par l\'administrateur.' });
    }

    const passwordMatch = await bcrypt.compare(password, user.password_hash);
    if (!passwordMatch) {
      return res.status(401).json({ error: 'Login ou mot de passe incorrect.' });
    }

    // Mise à jour last_login + ajout de points de connexion
    await db.query('UPDATE users SET last_login = NOW() WHERE id = ?', [user.id]);

    // Log de connexion + points
    const pointsConnexion = 0.25;
    await db.query(
      'INSERT INTO action_logs (user_id, action_type, points_earned) VALUES (?, "connexion", ?)',
      [user.id, pointsConnexion]
    );
    await db.query(
      'UPDATE users SET points = points + ? WHERE id = ?',
      [pointsConnexion, user.id]
    );

    // Vérifier et mettre à jour le niveau
    await updateUserLevel(user.id);

    // Générer le token JWT
    const token = jwt.sign(
      { id: user.id, login: user.login, role: user.role, type_level: user.type_level },
      process.env.JWT_SECRET,
      { expiresIn: '24h' }
    );

    res.json({
      token,
      user: {
        id:         user.id,
        login:      user.login,
        first_name: user.first_name,
        role:       user.role,
        type_level: user.type_level,
        points:     parseFloat(user.points) + pointsConnexion,
        photo_url:  user.photo_url
      }
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Erreur serveur lors de la connexion.' });
  }
};

// ─── MISE À JOUR AUTOMATIQUE DU NIVEAU ───────────────────────────────────────
async function updateUserLevel(userId) {
  const [rows] = await db.query('SELECT points, type_level, role FROM users WHERE id = ?', [userId]);
  if (rows.length === 0) return;

  const { points, type_level, role } = rows[0];
  let newLevel = type_level;
  let newRole  = role;

  if (points >= 7)  { newLevel = 'expert';        newRole = 'administrateur'; }
  else if (points >= 5) { newLevel = 'avance';    newRole = 'complexe'; }
  else if (points >= 3) { newLevel = 'intermediaire'; newRole = 'simple'; }
  else                  { newLevel = 'debutant';   newRole = 'simple'; }

  if (newLevel !== type_level || newRole !== role) {
    await db.query(
      'UPDATE users SET type_level = ?, role = ? WHERE id = ?',
      [newLevel, newRole, userId]
    );
  }
}

exports.updateUserLevel = updateUserLevel;
