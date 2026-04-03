-- ============================================================
-- SMART HOME PLATFORM — Données de test (seed)
-- Projet ING1 Dev Web 2025-2026
-- ============================================================

-- ============================================================
-- PIÈCES DE LA MAISON
-- ============================================================
INSERT INTO rooms (name, floor, description) VALUES
('Salon',           0, 'Pièce principale du rez-de-chaussée'),
('Cuisine',         0, 'Cuisine ouverte sur le salon'),
('Salle de bain',   0, 'Salle de bain au rez-de-chaussée'),
('Entrée',          0, 'Hall d entrée avec porte principale'),
('Chambre 1',       1, 'Chambre parentale'),
('Chambre 2',       1, 'Chambre enfant 1'),
('Chambre 3',       1, 'Chambre enfant 2'),
('Salle de bain 2', 1, 'Salle de bain à l étage'),
('Garage',          0, 'Garage double avec accès direct'),
('Jardin',         -1, 'Espace extérieur avec portail automatique');

-- ============================================================
-- CATÉGORIES D'OBJETS CONNECTÉS
-- ============================================================
INSERT INTO device_categories (name, icon, description) VALUES
('Thermostat',      'thermometer',  'Régulation de la température ambiante'),
('Éclairage',       'lightbulb',    'Ampoules et systèmes d éclairage intelligents'),
('Caméra',          'camera',       'Caméras de surveillance et de sécurité'),
('Serrure',         'lock',         'Serrures connectées et contrôle d accès'),
('Capteur',         'sensor',       'Capteurs de mouvement, fumée, CO2, humidité...'),
('Prise connectée', 'plug',         'Prises intelligentes avec mesure de consommation'),
('Volet roulant',   'window',       'Volets et stores motorisés'),
('Électroménager',  'appliances',   'Lave-linge, lave-vaisselle, réfrigérateur connectés'),
('Portail / Porte', 'door',         'Portails et portes automatiques'),
('Station météo',   'cloud',        'Stations météo et qualité de l air');

-- ============================================================
-- CATÉGORIES DE SERVICES
-- ============================================================
INSERT INTO service_categories (name, description) VALUES
('Énergie',         'Services de suivi et optimisation de la consommation énergétique'),
('Sécurité',        'Services de surveillance et d alertes de sécurité'),
('Confort',         'Services d automatisation et de confort quotidien'),
('Maintenance',     'Services de diagnostic et de maintenance des appareils');

-- ============================================================
-- SERVICES
-- ============================================================
INSERT INTO services (name, description, category_id, icon, min_level) VALUES
('Tableau de bord énergie',     'Suivi en temps réel de la consommation électrique de la maison',                      1, 'chart-bar',     'debutant'),
('Alertes consommation',        'Notifications si la consommation dépasse un seuil défini',                            1, 'bell',          'intermediaire'),
('Rapport hebdomadaire',        'Rapport automatique de consommation chaque semaine',                                  1, 'file-text',     'intermediaire'),
('Surveillance caméras',        'Accès aux flux vidéo en direct de toutes les caméras',                               2, 'video',         'debutant'),
('Historique alarmes',          'Consulter l historique des alertes et intrusions détectées',                         2, 'shield',        'intermediaire'),
('Scénarios automatiques',      'Créer des règles d automatisation (si X alors Y)',                                   3, 'zap',           'avance'),
('Mode vacances',               'Active la simulation de présence lors de vos absences',                              3, 'sun',           'intermediaire'),
('Planification horaires',      'Programmer les appareils selon des horaires prédéfinis',                             3, 'clock',         'debutant'),
('Diagnostic appareils',        'Détection des pannes et recommandations de maintenance',                             4, 'tool',          'avance'),
('Historique des données',      'Consulter l ensemble des données capteurs sur une période',                          4, 'database',      'intermediaire');

-- ============================================================
-- UTILISATEURS (mots de passe : "password123" hashé en bcrypt)
-- Hash bcrypt de "password123" : $2b$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lh9i
-- ============================================================
INSERT INTO users (login, email, password_hash, first_name, last_name, age, gender, birth_date, member_type, role, type_level, points, is_validated, is_approved) VALUES
('admin_martin',    'martin.dupont@smarthome.fr',   '$2b$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lh9i', 'Pierre',   'Dupont',   45, 'M', '1980-03-15', 'père',     'administrateur', 'expert',        9.00, TRUE, TRUE),
('sophie_dupont',   'sophie.dupont@smarthome.fr',   '$2b$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lh9i', 'Sophie',   'Dupont',   43, 'F', '1982-07-22', 'mère',     'complexe',       'avance',        5.75, TRUE, TRUE),
('lucas_dupont',    'lucas.dupont@smarthome.fr',    '$2b$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lh9i', 'Lucas',    'Dupont',   17, 'M', '2008-11-05', 'enfant',   'simple',         'intermediaire', 3.50, TRUE, TRUE),
('emma_dupont',     'emma.dupont@smarthome.fr',     '$2b$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lh9i', 'Emma',     'Dupont',   14, 'F', '2011-04-18', 'enfant',   'simple',         'debutant',      1.25, TRUE, TRUE),
('grandma_claire',  'claire.moreau@smarthome.fr',   '$2b$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lh9i', 'Claire',   'Moreau',   71, 'F', '1954-09-30', 'grand-mère','simple',         'debutant',      0.75, TRUE, TRUE);

-- ============================================================
-- OBJETS CONNECTÉS
-- ============================================================
INSERT INTO devices (unique_code, name, description, category_id, room_id, brand, model, connectivity, signal_strength, battery_level, power_source, energy_consumption, status, last_interaction, installation_date, parameters, added_by) VALUES

-- Thermostats
('THERMO_001', 'Thermostat Salon',      'Régulateur de température principal du salon',         1, 1, 'Nest',     'Learning Thermostat 3', 'Wi-Fi',   'fort',   NULL, 'secteur', 2.5,  'actif',   NOW() - INTERVAL 1 HOUR,  '2024-01-15', '{"temp_actuelle": 21, "temp_cible": 22, "mode": "automatique"}', 1),
('THERMO_002', 'Thermostat Chambre 1',  'Thermostat chambre parentale',                         1, 5, 'Nest',     'Learning Thermostat 3', 'Wi-Fi',   'fort',   NULL, 'secteur', 2.5,  'actif',   NOW() - INTERVAL 2 HOUR,  '2024-01-15', '{"temp_actuelle": 19, "temp_cible": 20, "mode": "nuit"}',        1),
('THERMO_003', 'Thermostat Chambre 2',  'Thermostat chambre Lucas',                             1, 6, 'Tado',     'Tado Smart Thermostat', 'Wi-Fi',   'moyen',  NULL, 'secteur', 2.0,  'actif',   NOW() - INTERVAL 3 HOUR,  '2024-02-01', '{"temp_actuelle": 20, "temp_cible": 21, "mode": "manuel"}',      1),

-- Éclairages
('LIGHT_001',  'Ampoule Salon Principal','Ampoule connectée au-dessus du canapé',               2, 1, 'Philips',  'Hue White Ambiance',    'Zigbee',  'fort',   NULL, 'secteur', 8.5,  'actif',   NOW() - INTERVAL 30 MINUTE,'2024-01-20', '{"brightness": 80, "color_temp": 3000, "state": "on"}',          1),
('LIGHT_002',  'Ampoule Cuisine',        'Éclairage principal cuisine',                         2, 2, 'Philips',  'Hue White',             'Zigbee',  'fort',   NULL, 'secteur', 9.0,  'actif',   NOW() - INTERVAL 10 MINUTE,'2024-01-20', '{"brightness": 100, "color_temp": 4000, "state": "on"}',         1),
('LIGHT_003',  'Ampoule Chambre 1',      'Ampoule chambre parentale, lumière douce',            2, 5, 'IKEA',     'Tradfri Bulb',          'Zigbee',  'moyen',  NULL, 'secteur', 7.0,  'inactif', NOW() - INTERVAL 8 HOUR,  '2024-03-05', '{"brightness": 0, "color_temp": 2700, "state": "off"}',          1),
('LIGHT_004',  'Veilleuse Chambre 2',    'Veilleuse connectée chambre de Lucas',                2, 6, 'IKEA',     'Tradfri Night Light',   'Zigbee',  'faible', 72,   'batterie',0.5,  'actif',   NOW() - INTERVAL 5 HOUR,  '2024-03-10', '{"brightness": 20, "state": "on"}',                              1),

-- Caméras
('CAM_001',    'Caméra Entrée',          'Caméra surveillance entrée principale',                3, 4, 'Arlo',     'Pro 4',                 'Wi-Fi',   'fort',   NULL, 'secteur', 15.0, 'actif',   NOW() - INTERVAL 5 MINUTE, '2024-01-10', '{"resolution": "2K", "night_vision": true, "motion_detect": true}',1),
('CAM_002',    'Caméra Garage',          'Surveillance intérieur garage',                       3, 9, 'Arlo',     'Pro 4',                 'Wi-Fi',   'moyen',  NULL, 'secteur', 15.0, 'actif',   NOW() - INTERVAL 20 MINUTE,'2024-01-10', '{"resolution": "2K", "night_vision": true, "motion_detect": true}',1),
('CAM_003',    'Caméra Jardin',          'Caméra extérieure jardin et portail',                 3, 10,'Ring',     'Outdoor Camera',        'Wi-Fi',   'faible', NULL, 'secteur', 12.0, 'actif',   NOW() - INTERVAL 15 MINUTE,'2024-02-20', '{"resolution": "1080p", "night_vision": true, "weatherproof": true}',1),

-- Serrures
('LOCK_001',   'Serrure Porte Principale','Serrure connectée entrée principale',               4, 4, 'Yale',     'Linus Smart Lock',      'Bluetooth','fort',  60,   'batterie',0.1,  'actif',   NOW() - INTERVAL 6 HOUR,  '2024-01-05', '{"locked": true, "auto_lock": true, "auto_lock_delay": 30}',      1),
('LOCK_002',   'Serrure Garage',          'Serrure portail garage',                            4, 9, 'Nuki',     'Smart Lock 3.0',        'Wi-Fi',   'moyen',  45,   'batterie',0.1,  'actif',   NOW() - INTERVAL 12 HOUR, '2024-01-05', '{"locked": false, "auto_lock": false}',                          1),

-- Capteurs
('SENS_001',   'Capteur Fumée Cuisine',  'Détecteur de fumée et CO connecté cuisine',          5, 2, 'Nest',     'Protect 2nd Gen',       'Wi-Fi',   'fort',   NULL, 'secteur', 1.0,  'actif',   NOW() - INTERVAL 1 DAY,   '2024-01-01', '{"smoke_level": 0, "co_level": 0, "alarm": false}',               1),
('SENS_002',   'Capteur Mouvement Salon','Détecteur de présence salon',                        5, 1, 'Philips',  'Hue Motion Sensor',     'Zigbee',  'fort',   85,   'batterie',0.05, 'actif',   NOW() - INTERVAL 2 MINUTE, '2024-01-20', '{"motion": false, "lux": 120}',                                  1),
('SENS_003',   'Capteur CO2 Chambre 1',  'Capteur qualité de l air chambre parentale',         5, 5, 'Aranet',   'Aranet4',               'Bluetooth','fort',  78,   'batterie',0.1,  'actif',   NOW() - INTERVAL 30 MINUTE,'2024-03-01', '{"co2_ppm": 650, "temp": 19.5, "humidity": 55, "pressure": 1013}',1),

-- Prises connectées
('PLUG_001',   'Prise Machine à Laver',  'Prise connectée mesurant conso machine à laver',     6, 2, 'TP-Link',  'Kasa EP25',             'Wi-Fi',   'fort',   NULL, 'secteur', 0.0,  'inactif', NOW() - INTERVAL 4 HOUR,  '2024-02-10', '{"state": "off", "energy_today": 1.2, "energy_month": 18.5}',    1),
('PLUG_002',   'Prise Lave-vaisselle',   'Prise connectée lave-vaisselle cuisine',             6, 2, 'TP-Link',  'Kasa EP25',             'Wi-Fi',   'fort',   NULL, 'secteur', 220.0,'actif',   NOW() - INTERVAL 45 MINUTE,'2024-02-10', '{"state": "on", "energy_today": 0.8, "energy_month": 12.3}',    1),
('PLUG_003',   'Prise TV Salon',         'Prise connectée télévision salon',                   6, 1, 'Shelly',   'Plug S',                'Wi-Fi',   'fort',   NULL, 'secteur', 120.0,'actif',   NOW() - INTERVAL 1 HOUR,  '2024-02-15', '{"state": "on", "energy_today": 0.5, "energy_month": 8.2}',     1),

-- Volets
('VOLT_001',   'Volets Salon',           'Volets roulants salon automatisés',                  7, 1, 'Somfy',    'TaHoma Switch',         'Wi-Fi',   'fort',   NULL, 'secteur', 5.0,  'actif',   NOW() - INTERVAL 7 HOUR,  '2024-01-25', '{"position": 100, "tilt": 0, "auto_schedule": true}',            1),
('VOLT_002',   'Volets Chambre 1',       'Volets chambre parentale',                           7, 5, 'Somfy',    'TaHoma Switch',         'Wi-Fi',   'fort',   NULL, 'secteur', 5.0,  'actif',   NOW() - INTERVAL 8 HOUR,  '2024-01-25', '{"position": 0, "tilt": 45, "auto_schedule": true}',             1),

-- Électroménager
('APPLI_001',  'Réfrigérateur Connecté', 'Réfrigérateur Samsung avec caméra interne',          8, 2, 'Samsung',  'Family Hub RF23BB',     'Wi-Fi',   'fort',   NULL, 'secteur', 150.0,'actif',   NOW() - INTERVAL 10 MINUTE,'2024-01-01', '{"temp_frigo": 4, "temp_freezer": -18, "door_open": false}',     1),
('APPLI_002',  'Lave-linge Connecté',    'Machine à laver avec démarrage à distance',          8, 2, 'LG',       'F4T208WSE ThinQ',       'Wi-Fi',   'fort',   NULL, 'secteur', 0.0,  'inactif', NOW() - INTERVAL 4 HOUR,  '2024-01-01', '{"program": "coton 60", "remaining": 0, "state": "idle"}',        1),

-- Portail
('GATE_001',   'Portail Automatique',    'Portail coulissant motorisé avec interphone',        9, 10,'Somfy',    'Elixo 500 io',          'Wi-Fi',   'moyen',  NULL, 'secteur', 35.0, 'actif',   NOW() - INTERVAL 3 HOUR,  '2024-01-01', '{"position": "fermé", "interphone": true, "auto_close": true}',  1),

-- Station météo
('METEO_001',  'Station Météo Jardin',   'Station météo extérieure avec capteurs multiples',  10, 10,'Netatmo',  'Weather Station',       'Wi-Fi',   'faible', NULL, 'secteur', 3.0,  'actif',   NOW() - INTERVAL 10 MINUTE,'2024-01-01', '{"temp_ext": 12.5, "humidity": 68, "rain": 0, "wind": 14}',      1);

-- ============================================================
-- DONNÉES CAPTEURS (device_data) — 7 derniers jours
-- ============================================================

-- Thermostat Salon (THERMO_001, id=1)
INSERT INTO device_data (device_id, metric_name, value, unit, recorded_at) VALUES
(1, 'temperature', 21.2, '°C', NOW() - INTERVAL 1 HOUR),
(1, 'temperature', 21.0, '°C', NOW() - INTERVAL 3 HOUR),
(1, 'temperature', 20.5, '°C', NOW() - INTERVAL 6 HOUR),
(1, 'temperature', 19.8, '°C', NOW() - INTERVAL 12 HOUR),
(1, 'temperature', 18.5, '°C', NOW() - INTERVAL 1 DAY),
(1, 'temperature', 22.1, '°C', NOW() - INTERVAL 2 DAY),
(1, 'temperature', 21.8, '°C', NOW() - INTERVAL 3 DAY),
(1, 'humidity',    48.0, '%',  NOW() - INTERVAL 1 HOUR),
(1, 'humidity',    50.0, '%',  NOW() - INTERVAL 6 HOUR),
(1, 'humidity',    52.0, '%',  NOW() - INTERVAL 1 DAY);

-- Capteur CO2 Chambre 1 (SENS_003, id=15)
INSERT INTO device_data (device_id, metric_name, value, unit, recorded_at) VALUES
(15, 'co2',         650, 'ppm', NOW() - INTERVAL 30 MINUTE),
(15, 'co2',         720, 'ppm', NOW() - INTERVAL 2 HOUR),
(15, 'co2',         980, 'ppm', NOW() - INTERVAL 8 HOUR),
(15, 'co2',         550, 'ppm', NOW() - INTERVAL 1 DAY),
(15, 'temperature', 19.5, '°C', NOW() - INTERVAL 30 MINUTE),
(15, 'humidity',    55.0, '%',  NOW() - INTERVAL 30 MINUTE);

-- Prise TV Salon (PLUG_003, id=19)
INSERT INTO device_data (device_id, metric_name, value, unit, recorded_at) VALUES
(19, 'power', 120.0, 'W',  NOW() - INTERVAL 1 HOUR),
(19, 'power', 118.5, 'W',  NOW() - INTERVAL 2 HOUR),
(19, 'power',   0.0, 'W',  NOW() - INTERVAL 10 HOUR),
(19, 'power',   0.0, 'W',  NOW() - INTERVAL 1 DAY),
(19, 'power', 125.0, 'W',  NOW() - INTERVAL 2 DAY);

-- Station Météo (METEO_001, id=24)
INSERT INTO device_data (device_id, metric_name, value, unit, recorded_at) VALUES
(24, 'temperature_ext', 12.5, '°C',   NOW() - INTERVAL 10 MINUTE),
(24, 'temperature_ext', 11.0, '°C',   NOW() - INTERVAL 3 HOUR),
(24, 'temperature_ext',  8.5, '°C',   NOW() - INTERVAL 8 HOUR),
(24, 'temperature_ext', 14.2, '°C',   NOW() - INTERVAL 1 DAY),
(24, 'humidity',        68.0, '%',    NOW() - INTERVAL 10 MINUTE),
(24, 'wind_speed',      14.0, 'km/h', NOW() - INTERVAL 10 MINUTE),
(24, 'rain',             0.0, 'mm',   NOW() - INTERVAL 10 MINUTE),
(24, 'rain',             3.2, 'mm',   NOW() - INTERVAL 1 DAY);

-- ============================================================
-- LOGS D'ACTIONS (action_logs)
-- ============================================================
INSERT INTO action_logs (user_id, action_type, target_id, target_type, points_earned, created_at) VALUES
-- Pierre (admin)
(1, 'connexion',           NULL, NULL,     0.25, NOW() - INTERVAL 1 HOUR),
(1, 'consultation_objet',  1,    'device', 0.50, NOW() - INTERVAL 1 HOUR),
(1, 'consultation_objet',  8,    'device', 0.50, NOW() - INTERVAL 1 HOUR),
-- Sophie (complexe)
(2, 'connexion',           NULL, NULL,     0.25, NOW() - INTERVAL 2 HOUR),
(2, 'consultation_objet',  1,    'device', 0.50, NOW() - INTERVAL 2 HOUR),
(2, 'modification_objet',  3,    'device', 0.25, NOW() - INTERVAL 2 HOUR),
-- Lucas (simple intermédiaire)
(3, 'connexion',           NULL, NULL,     0.25, NOW() - INTERVAL 3 HOUR),
(3, 'consultation_objet',  6,    'device', 0.50, NOW() - INTERVAL 3 HOUR),
(3, 'consultation_service',4,    'service',0.50, NOW() - INTERVAL 3 HOUR),
-- Emma (simple débutante)
(4, 'connexion',           NULL, NULL,     0.25, NOW() - INTERVAL 5 HOUR),
(4, 'consultation_objet',  4,    'device', 0.50, NOW() - INTERVAL 5 HOUR),
-- Claire (simple débutante)
(5, 'connexion',           NULL, NULL,     0.25, NOW() - INTERVAL 1 DAY),
(5, 'consultation_service',8,    'service',0.50, NOW() - INTERVAL 1 DAY);
