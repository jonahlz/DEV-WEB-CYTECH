-- ============================================================
-- LumiHome — Migration : Système de niveaux
-- À exécuter UNE FOIS sur la base lumihome existante
-- ============================================================

USE lumihome;

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
