-- ============================================================
-- SMART HOME PLATFORM — Schéma de Base de Données
-- Projet ING1 Dev Web 2025-2026
-- ============================================================

-- Suppression des tables dans l'ordre (clés étrangères d'abord)
DROP TABLE IF EXISTS action_logs;
DROP TABLE IF EXISTS device_data;
DROP TABLE IF EXISTS devices;
DROP TABLE IF EXISTS device_categories;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS service_categories;
DROP TABLE IF EXISTS users;

-- ============================================================
-- TABLE : users
-- Contient tous les utilisateurs de la plateforme
-- type_level : 'debutant' | 'intermediaire' | 'avance' | 'expert'
-- role : 'visiteur' | 'simple' | 'complexe' | 'administrateur'
-- ============================================================
CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    login           VARCHAR(50)  NOT NULL UNIQUE,
    email           VARCHAR(100) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    first_name      VARCHAR(50)  NOT NULL,
    last_name       VARCHAR(50)  NOT NULL,
    age             INT,
    gender          VARCHAR(20),
    birth_date      DATE,
    member_type     VARCHAR(50)  NOT NULL DEFAULT 'membre',  -- ex: père, mère, enfant
    photo_url       VARCHAR(255),
    role            ENUM('simple','complexe','administrateur') NOT NULL DEFAULT 'simple',
    type_level      ENUM('debutant','intermediaire','avance','expert') NOT NULL DEFAULT 'debutant',
    points          DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    is_validated    BOOLEAN NOT NULL DEFAULT FALSE,          -- validation par email
    is_approved     BOOLEAN NOT NULL DEFAULT FALSE,          -- approbation par admin
    validation_token VARCHAR(255),
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login      DATETIME
);

-- ============================================================
-- TABLE : rooms
-- Pièces de la maison auxquelles les objets sont rattachés
-- ============================================================
CREATE TABLE rooms (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,   -- ex: Salon, Cuisine, Chambre 1
    floor       INT NOT NULL DEFAULT 0,  -- étage
    description TEXT
);

-- ============================================================
-- TABLE : device_categories
-- Catégories d'objets connectés (créées/gérées par l'admin)
-- ============================================================
CREATE TABLE device_categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,   -- ex: Thermostat, Caméra, Éclairage
    icon        VARCHAR(100),
    description TEXT
);

-- ============================================================
-- TABLE : devices
-- Objets connectés enregistrés sur la plateforme
-- ============================================================
CREATE TABLE devices (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    unique_code     VARCHAR(50) NOT NULL UNIQUE,        -- ex: THERMO_001
    name            VARCHAR(100) NOT NULL,              -- ex: Thermostat Salon
    description     TEXT,
    category_id     INT NOT NULL,
    room_id         INT,
    brand           VARCHAR(100),
    model           VARCHAR(100),
    -- Attributs connectivité
    connectivity    VARCHAR(50) DEFAULT 'Wi-Fi',        -- Wi-Fi, Zigbee, Z-Wave...
    signal_strength VARCHAR(20) DEFAULT 'fort',
    ip_address      VARCHAR(45),
    -- Attributs énergie
    battery_level   INT,                                -- % de batterie (null si branché)
    power_source    ENUM('batterie','secteur','solaire') DEFAULT 'secteur',
    energy_consumption DECIMAL(6,2),                   -- Watts
    -- Attributs état
    status          ENUM('actif','inactif','maintenance','deconnecte') NOT NULL DEFAULT 'actif',
    -- Attributs usage
    last_interaction DATETIME,
    installation_date DATE,
    -- Paramètres spécifiques (JSON pour flexibilité selon catégorie)
    parameters      JSON,
    -- Gestion
    added_by        INT,                                -- user_id qui a ajouté l'objet
    deletion_requested BOOLEAN DEFAULT FALSE,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES device_categories(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE : device_data
-- Historique des données capteurs pour chaque objet
-- ============================================================
CREATE TABLE device_data (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    device_id   INT NOT NULL,
    metric_name VARCHAR(100) NOT NULL,      -- ex: temperature, humidity, motion
    value       DECIMAL(10,2) NOT NULL,
    unit        VARCHAR(20),                -- ex: °C, %, W, lux
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE : service_categories
-- ============================================================
CREATE TABLE service_categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT
);

-- ============================================================
-- TABLE : services
-- Outils/services proposés par la plateforme
-- ============================================================
CREATE TABLE services (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    description     TEXT,
    category_id     INT,
    icon            VARCHAR(100),
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,
    min_level       ENUM('debutant','intermediaire','avance','expert') DEFAULT 'debutant',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES service_categories(id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE : action_logs
-- Historique de toutes les actions des utilisateurs
-- Sert à calculer les points et générer des rapports
-- ============================================================
CREATE TABLE action_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    action_type ENUM('connexion','consultation_objet','consultation_service','modification_objet','modification_profil','ajout_objet','suppression_objet') NOT NULL,
    target_id   INT,                        -- id de l'objet/service consulté
    target_type VARCHAR(50),                -- 'device' ou 'service'
    points_earned DECIMAL(5,2) DEFAULT 0,
    ip_address  VARCHAR(45),
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- SYSTÈME DE POINTS (référence)
-- connexion : +0.25 points
-- consultation objet/service : +0.50 points par action
-- Seuils :
--   débutant      :  0 à 2.99 points
--   intermédiaire :  3 à 4.99 points
--   avancé        :  5 à 6.99 points  → débloque module Gestion
--   expert        :  7+ points         → débloque module Administration
-- ============================================================
