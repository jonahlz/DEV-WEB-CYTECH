const jwt = require('jsonwebtoken');

// Vérifie que l'utilisateur est connecté (token JWT valide)
const authenticate = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1]; // Bearer <token>

  if (!token) {
    return res.status(401).json({ error: 'Accès refusé. Connectez-vous.' });
  }

  try {
    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    req.user = decoded; // { id, login, role, type_level }
    next();
  } catch (err) {
    return res.status(403).json({ error: 'Token invalide ou expiré.' });
  }
};

// Vérifie que l'utilisateur a le rôle requis
const requireRole = (...roles) => (req, res, next) => {
  if (!roles.includes(req.user.role)) {
    return res.status(403).json({ error: 'Accès interdit. Permissions insuffisantes.' });
  }
  next();
};

// Vérifie que l'utilisateur a le niveau requis
const requireLevel = (minLevel) => (req, res, next) => {
  const levels = { debutant: 0, intermediaire: 1, avance: 2, expert: 3 };
  const userLevel  = levels[req.user.type_level] ?? 0;
  const neededLevel = levels[minLevel] ?? 0;

  if (userLevel < neededLevel) {
    return res.status(403).json({ error: `Niveau ${minLevel} requis pour accéder à cette ressource.` });
  }
  next();
};

module.exports = { authenticate, requireRole, requireLevel };
