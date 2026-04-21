const express = require('express');
const cors    = require('cors');
require('dotenv').config();

const authRoutes    = require('./routes/auth');
const userRoutes    = require('./routes/users');
const deviceRoutes  = require('./routes/devices');
const serviceRoutes = require('./routes/services');
const adminRoutes   = require('./routes/admin');

const app  = express();
const PORT = process.env.PORT || 3001;

// Middlewares globaux
app.use(cors({ origin: 'http://localhost:3000' }));
app.use(express.json());

// Routes
app.use('/api/auth',     authRoutes);
app.use('/api/users',    userRoutes);
app.use('/api/devices',  deviceRoutes);
app.use('/api/services', serviceRoutes);
app.use('/api/admin',    adminRoutes);

// Route de test
app.get('/api/health', (req, res) => {
  res.json({ status: 'ok', message: 'Smart Home API running' });
});

app.listen(PORT, () => {
  console.log(`Serveur démarré sur http://localhost:${PORT}`);
});
