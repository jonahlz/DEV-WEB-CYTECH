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
