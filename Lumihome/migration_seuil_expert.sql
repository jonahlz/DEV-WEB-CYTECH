-- ============================================================
-- LumiHome — Mise à jour seuil niveau Expert : 7 → 20 pts
-- À exécuter dans phpMyAdmin sur la base lumihome
-- ============================================================

USE lumihome;

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
