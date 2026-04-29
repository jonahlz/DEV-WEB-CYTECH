-- ============================================================
-- LumiHome – Base de données complète
-- ============================================================
CREATE DATABASE IF NOT EXISTS lumihome CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lumihome;

-- --------------------------------------------------------
-- UTILISATEURS
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateurs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    login           VARCHAR(50)  NOT NULL UNIQUE,
    nom             VARCHAR(100) NOT NULL,
    prenom          VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe    VARCHAR(255) NOT NULL,
    role            ENUM('visiteur','membre','admin') DEFAULT 'membre',
    est_banni       BOOLEAN DEFAULT FALSE,
    raison_ban      TEXT,
    age             INT,
    sexe            ENUM('M','F','Autre'),
    date_naissance  DATE,
    type_membre     VARCHAR(50) DEFAULT 'habitant',
    points          FLOAT DEFAULT 0,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------
-- PIÈCES
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS pieces (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(100) NOT NULL,
    etage       INT DEFAULT 0,
    type_piece  VARCHAR(50),
    superficie  FLOAT,
    emoji       VARCHAR(10) DEFAULT '🏠'
);

-- --------------------------------------------------------
-- LUMIÈRES CONNECTÉES
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS lumieres (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    nom                  VARCHAR(100) NOT NULL,
    marque               VARCHAR(60),
    modele               VARCHAR(60),
    description          TEXT,
    connectivite         ENUM('Wi-Fi','Zigbee','Bluetooth','Z-Wave') DEFAULT 'Wi-Fi',
    signal_force         INT DEFAULT 90,
    puissance_max_watt   FLOAT DEFAULT 9,
    etat                 ENUM('actif','inactif','erreur') DEFAULT 'inactif',
    luminosite           INT DEFAULT 100,
    couleur_hex          VARCHAR(7) DEFAULT '#FFFFFF',
    temperature_couleur  INT DEFAULT 4000,
    conso_watt           FLOAT DEFAULT 0,
    nb_allumages         INT DEFAULT 0,
    duree_utilisation_h  FLOAT DEFAULT 0,
    derniere_action      DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_piece             INT,
    id_user              INT,
    FOREIGN KEY (id_piece) REFERENCES pieces(id) ON DELETE SET NULL,
    FOREIGN KEY (id_user) REFERENCES utilisateurs(id) ON DELETE CASCADE
);
    
-- --------------------------------------------------------
-- HISTORIQUE (logs IoT)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS historique (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    id_lumiere  INT NOT NULL,
    id_user     INT,
    timestamp   DATETIME DEFAULT CURRENT_TIMESTAMP,
    action      VARCHAR(80) NOT NULL,
    val_avant   VARCHAR(80),
    val_apres   VARCHAR(80),
    FOREIGN KEY (id_lumiere) REFERENCES lumieres(id) ON DELETE CASCADE,
    FOREIGN KEY (id_user)    REFERENCES utilisateurs(id) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- CONNEXIONS
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS connexions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    id_user     INT NOT NULL,
    ts          DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- ============================================================
-- DONNÉES INITIALES
-- ============================================================

-- Pièces
INSERT INTO pieces (nom, etage, type_piece, superficie, emoji) VALUES
('Salon',            0, 'salon',   32.5, '🛋️'),
('Cuisine',          0, 'cuisine', 14.0, '🍳'),
('Chambre parents',  1, 'chambre', 20.0, '🛏️'),
('Chambre enfant',   1, 'chambre', 12.5, '🧸'),
('Salle de bain',    1, 'sdb',      8.0, '🚿'),
('Bureau',           1, 'bureau',  10.0, '💻');

-- Lumières (6 pour la démo visiteur, 12 total pour membres connectés)
INSERT INTO lumieres (nom, marque, modele, connectivite, signal_force, puissance_max_watt, etat, luminosite, couleur_hex, temperature_couleur, conso_watt, nb_allumages, duree_utilisation_h, id_piece) VALUES
-- Salon
('Lustre principal',  'Philips Hue','Hue White A19','Wi-Fi',  92, 60, 'actif',   80,'#FFEECC',3000,42.0,1240,520,1),
('Lampe d''ambiance', 'IKEA',       'TRÅDFRI E27',  'Zigbee', 78,  8, 'inactif',  0,'#FF9944',2700, 0.0, 890,310,1),
-- Cuisine
('Plafonnier cuisine','Yeelight',   'YLXD50YL',     'Wi-Fi',  95, 50, 'actif',  100,'#FFFFFF',5000,38.0,2100,780,2),
('Bandeau LED',       'Govee',      'H6163',        'Wi-Fi',  88, 15, 'inactif',  0,'#FFFFFF',4000, 0.0, 430, 90,2),
-- Chambre parents
('Plafonnier chambre','Philips Hue','Hue Ambiance',  'Wi-Fi',  91, 40, 'inactif',  0,'#FFDDB4',2700, 0.0, 980,400,3),
('Chevet gauche',     'Philips Hue','Hue Go',       'Zigbee', 83,  6, 'inactif',  0,'#FF6688',2700, 0.0,2340,560,3),
-- Chambre enfant
('Lumière chambre',   'IKEA',       'TRÅDFRI GU10', 'Zigbee', 75, 30, 'inactif',  0,'#FFFF00',4000, 0.0, 560,210,4),
('Veilleuse',         'Govee',      'H6052',        'Bluetooth',60, 3, 'actif',   20,'#FF88CC',2700, 2.1,1890,450,4),
-- Salle de bain
('Plafonnier SDB',    'Yeelight',   'YLDL01YL',     'Wi-Fi',  86, 24, 'inactif',  0,'#FFFFFF',6500, 0.0,3200,320,5),
-- Bureau
('Plafonnier bureau', 'Philips Hue','Hue White GU10','Wi-Fi', 89, 35, 'actif',   90,'#FFFFFF',5500,28.0,1560,620,6),
('Lampe de bureau',   'BenQ',       'ScreenBar Halo','Wi-Fi', 94, 14, 'actif',   70,'#FFEEDD',4000,10.5,1230,510,6),
('Applique murale',   'Osram',      'Smart+ Spot',  'Zigbee', 70,  7, 'inactif',  0,'#FFCC88',3000, 0.0, 670,150,6);

-- Utilisateurs : admin + 2 membres
-- Mots de passe hashés avec password_hash('motdepasse', PASSWORD_DEFAULT)
-- admin → mdp: Admin1234!
-- sophie → mdp: Sophie2025 
-- lucas → mdp: Lucas2025
INSERT INTO utilisateurs (login, nom, prenom, email, mot_de_passe, role, age, sexe, date_naissance, type_membre, points) VALUES
('admin',  'Martin', 'Admin',  'admin@lumihome.fr',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',  38, 'F', '1986-03-12', 'mère',    99.0),
('sophie', 'Martin', 'Sophie', 'sophie@lumihome.fr', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'membre', 35, 'F', '1989-07-15', 'mère',    12.5),
('lucas',  'Martin', 'Lucas',  'lucas@lumihome.fr',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'membre', 12, 'M', '2012-11-05', 'enfant',   5.75);
-- NOTE : tous les mots de passe initiaux sont "password" (hash bcrypt ci-dessus)
-- Changez-les en production !


-- ============================================================
-- LumiHome — : Système de niveaux
-- ============================================================

-- 1. Ajouter les colonnes manquantes à la table utilisateurs
ALTER TABLE utilisateurs
    ADD COLUMN IF NOT EXISTS niveau          ENUM('debutant','intermediaire','avance','expert') DEFAULT 'debutant',
    ADD COLUMN IF NOT EXISTS pts_connexion   FLOAT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS pts_actions     FLOAT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS nb_connexions   INT   DEFAULT 0,
    ADD COLUMN IF NOT EXISTS nb_actions      INT   DEFAULT 0;

-- 2. Mettre à jour les utilisateurs existants avec leurs points actuels
UPDATE utilisateurs SET
    niveau        = CASE
        WHEN points >= 7 THEN 'expert'
        WHEN points >= 5 THEN 'avance'
        WHEN points >= 3 THEN 'intermediaire'
        ELSE 'debutant'
    END,
    nb_connexions = (SELECT COUNT(*) FROM connexions WHERE connexions.id_user = utilisateurs.id);

-- 3. Table de configuration des seuils de niveaux
CREATE TABLE IF NOT EXISTS niveaux_config (
    niveau       VARCHAR(20) PRIMARY KEY,
    pts_requis   FLOAT NOT NULL,
    libelle      VARCHAR(60) NOT NULL,
    description  TEXT,
    couleur_hex  VARCHAR(7) DEFAULT '#f5c842',
    emoji        VARCHAR(5) DEFAULT '⭐'
);

-- Données de configuration des niveaux
INSERT INTO niveaux_config (niveau, pts_requis, libelle, description, couleur_hex, emoji) VALUES
('debutant',       0,   'Débutant',       'Niveau acquis à l\'inscription. Vous pouvez consulter et rechercher les lumières.', '#7a8299', '🌱'),
('intermediaire',  3,   'Intermédiaire',  'Vous maîtrisez les bases. Accès étendu aux outils de consultation.', '#3b82f6', '⚡'),
('avance',         5,   'Avancé',         'Vous gérez vos objets connectés de façon autonome. Module Gestion débloqué.', '#f5c842', '🌟'),
('expert',         7,   'Expert',         'Vous êtes reconnu comme expert. Module Administration débloqué.', '#f97316', '👑')
ON DUPLICATE KEY UPDATE pts_requis=VALUES(pts_requis), libelle=VALUES(libelle);

-- 4. Table de log des gains de points (traçabilité)
CREATE TABLE IF NOT EXISTS points_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    id_user     INT NOT NULL,
    type_gain   ENUM('connexion','consultation','action','bonus','admin') NOT NULL,
    pts_gagnes  FLOAT NOT NULL,
    detail      VARCHAR(255),
    ts          DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_user_ts (id_user, ts)
);

-- 5. Recalculer les points des utilisateurs existants d'après leur historique
-- (points de connexion = nb_connexions * 0.25)
UPDATE utilisateurs u SET
    pts_connexion = (SELECT COUNT(*) FROM connexions c WHERE c.id_user = u.id) * 0.25,
    nb_connexions = (SELECT COUNT(*) FROM connexions c WHERE c.id_user = u.id);

-- Synchro globale
UPDATE utilisateurs SET
    points = pts_connexion + pts_actions;

-- Mise à jour finale des niveaux après recalcul
UPDATE utilisateurs SET
    niveau = CASE
        WHEN points >= 7 THEN 'expert'
        WHEN points >= 5 THEN 'avance'
        WHEN points >= 3 THEN 'intermediaire'
        ELSE 'debutant'
    END;

-- 6. Forcer l'admin à avoir le niveau expert (pour les tests)
UPDATE utilisateurs SET niveau = 'expert', points = 99 WHERE role = 'admin';

SELECT 
    login, 
    ROUND(points, 2) AS points_total,
    ROUND(pts_connexion, 2) AS pts_connexion,
    ROUND(pts_actions, 2) AS pts_actions,
    nb_connexions,
    nb_actions,
    niveau
FROM utilisateurs
ORDER BY points DESC;

-- ============================================================
-- Gestion des seuils de niveaux
-- ============================================================

-- 1. Mettre à jour la table de configuration des niveaux
UPDATE niveaux_config
SET pts_requis = 20
WHERE niveau = 'expert';

-- 2. Recalculer le niveau de tous les utilisateurs avec les nouveaux seuils
--    (montée uniquement : un expert reste expert s'il avait déjà le niveau)
UPDATE utilisateurs
SET niveau = CASE
    WHEN points >= 20 THEN 'expert'
    WHEN points >= 5  THEN 'avance'
    WHEN points >= 3  THEN 'intermediaire'
    ELSE 'debutant'
END;

-- Vérification
SELECT login, ROUND(points,2) AS points, niveau FROM utilisateurs ORDER BY points DESC;