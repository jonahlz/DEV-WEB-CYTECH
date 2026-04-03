# Smart Home Platform — ING1 Dev Web 2025-2026

Plateforme numérique intelligente pour maison connectée (IoT).

---

## Structure du projet

```
smart-home/
├── client/               → Front-end (React + Tailwind CSS)
│   └── src/
│       └── components/
│           └── modules/
│               ├── information/     → Module public (Visiteur)
│               ├── visualisation/   → Module connecté (Simple)
│               ├── gestion/         → Module avancé (Complexe)
│               └── administration/  → Module admin (Admin)
├── server/               → Back-end (Node.js + Express)
│   ├── routes/           → Définition des routes API
│   ├── controllers/      → Logique métier
│   ├── middleware/        → Auth JWT, gestion des rôles
│   └── config/           → Connexion BDD
└── database/
    ├── schema.sql        → Structure des tables
    └── seed.sql          → Données de test
```

---

## Prérequis

- [Node.js](https://nodejs.org/) v18+
- [WampServer](https://www.wampserver.com/) (ou XAMPP) pour MySQL
- Git

---

## Installation

### 1. Cloner le dépôt

```bash
git clone <URL_DU_REPO>
cd smart-home
```

### 2. Initialiser la base de données

1. Démarrer WampServer
2. Ouvrir phpMyAdmin → Créer une BDD nommée `smart_home`
3. Importer `database/schema.sql`
4. Importer `database/seed.sql`

### 3. Configurer le back-end

```bash
cd server
cp .env.example .env
# Remplir les valeurs dans .env (mot de passe BDD, JWT secret...)
npm install
npm run dev   # Démarre sur http://localhost:3001
```

### 4. Configurer le front-end (React)

```bash
cd client
npx create-react-app .   # Si pas encore initialisé
npm install tailwindcss  # Pour le CSS
npm start                # Démarre sur http://localhost:3000
```

---

## Comptes de test (mot de passe : `password123`)

| Login           | Rôle            | Niveau       | Accès modules          |
|-----------------|-----------------|--------------|------------------------|
| admin_martin    | administrateur  | expert       | Tous les modules       |
| sophie_dupont   | complexe        | avancé       | Info + Visu + Gestion  |
| lucas_dupont    | simple          | intermédiaire| Info + Visualisation   |
| emma_dupont     | simple          | débutant     | Info + Visualisation   |
| grandma_claire  | simple          | débutant     | Info + Visualisation   |

---

## Routes API disponibles

### Authentification
| Méthode | Route                        | Description                  |
|---------|------------------------------|------------------------------|
| POST    | /api/auth/register           | Inscription                  |
| POST    | /api/auth/login              | Connexion → retourne un JWT  |
| GET     | /api/auth/validate/:token    | Validation email             |

### Utilisateurs (authentifié requis)
| Méthode | Route              | Description                  |
|---------|--------------------|------------------------------|
| GET     | /api/users/me      | Mon profil complet           |
| PUT     | /api/users/me      | Modifier mon profil          |
| GET     | /api/users/me/stats| Mes points et historique     |
| GET     | /api/users         | Liste des membres (publique) |
| GET     | /api/users/:id     | Profil public d'un membre    |

### Objets connectés (authentifié requis)
| Méthode | Route                           | Description                    |
|---------|---------------------------------|--------------------------------|
| GET     | /api/devices                    | Liste avec filtres             |
| GET     | /api/devices/:id                | Détail + données capteurs      |
| POST    | /api/devices                    | Ajouter (complexe/admin)       |
| PUT     | /api/devices/:id                | Modifier (complexe/admin)      |
| DELETE  | /api/devices/:id                | Supprimer (admin)              |
| POST    | /api/devices/:id/request-deletion | Demander suppression (complexe)|
| GET     | /api/devices/meta/categories    | Catégories d'objets            |
| GET     | /api/devices/meta/rooms         | Pièces de la maison            |

### Administration (admin uniquement)
| Méthode | Route                            | Description                    |
|---------|----------------------------------|--------------------------------|
| GET     | /api/admin/users                 | Tous les utilisateurs          |
| GET     | /api/admin/users/pending         | En attente d'approbation       |
| PUT     | /api/admin/users/:id/approve     | Approuver un utilisateur       |
| PUT     | /api/admin/users/:id/level       | Changer niveau manuellement    |
| DELETE  | /api/admin/users/:id             | Supprimer un utilisateur       |
| GET     | /api/admin/devices/deletion-requests | Demandes de suppression    |
| GET     | /api/admin/stats                 | Statistiques globales          |
| GET     | /api/admin/logs                  | Historique des actions         |

---

## Système de points

| Action                   | Points gagnés |
|--------------------------|---------------|
| Connexion                | +0.25         |
| Consultation objet/service | +0.50       |

| Seuil       | Niveau        | Rôle           | Modules débloqués          |
|-------------|---------------|----------------|----------------------------|
| 0 — 2.99 pts | Débutant     | simple         | Information + Visualisation |
| 3 — 4.99 pts | Intermédiaire| simple         | Information + Visualisation |
| 5 — 6.99 pts | Avancé       | complexe       | + Gestion                  |
| 7+ pts       | Expert        | administrateur | + Administration           |

---

## Répartition suggérée à 5 membres

| Membre | Responsabilité principale          |
|--------|------------------------------------|
| 1      | Module Information (front)         |
| 2      | Module Visualisation (front)       |
| 3      | Module Gestion (front)             |
| 4      | Module Administration (front)      |
| 5      | Back-end API + BDD (server/)       |

---

## Frameworks utilisés

- **React** — Front-end
- **Express.js** — Back-end API REST
- **Tailwind CSS** — Styles CSS utilitaires
- **MySQL2** — Connexion à la BDD
- **bcrypt** — Hashage des mots de passe
- **jsonwebtoken** — Authentification JWT

---

## Git — Bonnes pratiques

```bash
# Avant chaque session de travail
git pull origin main

# Chaque membre travaille sur sa branche
git checkout -b feature/module-information

# Commiter régulièrement
git add .
git commit -m "feat: ajout de la page de recherche avec filtres"
git push origin feature/module-information

# Fusionner dans main via Pull Request
```
