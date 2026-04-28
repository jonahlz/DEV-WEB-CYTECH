<?php
// ============================================================
// LumiHome — API REST interne
// ============================================================
require_once __DIR__ . '/../includes/config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================================
// CONFIGURATION DES NIVEAUX — source de vérité unique
// ============================================================
const NIVEAUX_CONFIG = [
    'debutant'      => ['limite_lumieres' => 3,  'peut_intensite' => false, 'peut_couleur' => false],
    'intermediaire' => ['limite_lumieres' => 5,  'peut_intensite' => true,  'peut_couleur' => false],
    'avance'        => ['limite_lumieres' => 8,  'peut_intensite' => true,  'peut_couleur' => true],
    'expert'        => ['limite_lumieres' => 99, 'peut_intensite' => true,  'peut_couleur' => true],
];

// ============================================================
// ANTI-SPAM — cooldown 3 minutes entre deux gains pour la même action.
// Cherche le keyword dans la colonne `detail` de points_log.
// ============================================================
const COOLDOWN_MINUTES = 3;

const ANTI_SPAM = [
    'connexion' => 'connexion',
    'toggle'    => 'lumage',     // "Allumage" / "Extinction"
    'intensite' => 'ntensit',    // "Intensité"
    'couleur'   => 'ouleur',     // "Couleur"
    'add'       => 'Ajout',
    'delete'    => 'uppression',
];

// ============================================================
// AUTH
// ============================================================

if ($action === 'register') {
    $login  = trim($_POST['login']  ?? '');
    $nom    = trim($_POST['nom']    ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email  = trim($_POST['email']  ?? '');
    $mdp    = $_POST['mdp']  ?? '';
    $mdp2   = $_POST['mdp2'] ?? '';
    $sexe   = $_POST['sexe'] ?? 'Autre';
    $dob    = $_POST['dob']  ?? '';
    $membre = $_POST['type_membre'] ?? 'habitant';

    if (!$login || !$nom || !$prenom || !$email || !$mdp)
        jsonResponse(['ok' => false, 'msg' => 'Tous les champs obligatoires doivent être remplis.']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        jsonResponse(['ok' => false, 'msg' => 'Email invalide.']);
    if (strlen($mdp) < 6)
        jsonResponse(['ok' => false, 'msg' => 'Mot de passe trop court (6 caractères minimum).']);
    if ($mdp !== $mdp2)
        jsonResponse(['ok' => false, 'msg' => 'Les mots de passe ne correspondent pas.']);

    try {
        $pdo = db();
        $st  = $pdo->prepare('SELECT id FROM utilisateurs WHERE login = ? OR email = ?');
        $st->execute([$login, $email]);
        if ($st->fetch())
            jsonResponse(['ok' => false, 'msg' => 'Login ou email déjà utilisé.']);

        $hash = password_hash($mdp, PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO utilisateurs
                       (login, nom, prenom, email, mot_de_passe, sexe, date_naissance, type_membre, role, niveau)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'membre\', \'debutant\')')
            ->execute([$login, $nom, $prenom, $email, $hash, $sexe, $dob ?: null, $membre]);

        $uid = $pdo->lastInsertId();
        $_SESSION['user_id'] = $uid;
        $_SESSION['login']   = $login;
        $_SESSION['nom']     = $nom;
        $_SESSION['prenom']  = $prenom;
        $_SESSION['role']    = 'membre';
        $_SESSION['niveau']  = 'debutant';

        $pdo->prepare('INSERT INTO connexions (id_user) VALUES (?)')->execute([$uid]);
        ajouterPoints($pdo, $uid, 'connexion', 0.25, 'Inscription & première connexion');

        jsonResponse(['ok' => true, 'msg' => 'Bienvenue ' . $prenom . ' !',
                      'redirect' => '/Lumihome/index.php?page=dashboard']);
    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'msg' => 'Erreur : ' . $e->getMessage()]);
    }
}

if ($action === 'login') {
    $login = trim($_POST['login'] ?? '');
    $mdp   = $_POST['mdp'] ?? '';

    if (!$login || !$mdp)
        jsonResponse(['ok' => false, 'msg' => 'Login et mot de passe requis.']);

    try {
        $pdo  = db();
        $st   = $pdo->prepare('SELECT * FROM utilisateurs WHERE (login = ? OR email = ?) LIMIT 1');
        $st->execute([$login, $login]);
        $user = $st->fetch();

        if (!$user || !password_verify($mdp, $user['mot_de_passe']))
            jsonResponse(['ok' => false, 'msg' => 'Identifiants incorrects.']);
        if ($user['est_banni'])
            jsonResponse(['ok' => false, 'msg' => 'Compte suspendu : ' . ($user['raison_ban'] ?: 'raison non précisée')]);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login']   = $user['login'];
        $_SESSION['nom']     = $user['nom'];
        $_SESSION['prenom']  = $user['prenom'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['niveau']  = $user['niveau'] ?? 'debutant';

        $pdo->prepare('INSERT INTO connexions (id_user) VALUES (?)')->execute([$user['id']]);

        // Connexion récompensée 1x toutes les 3 min (anti-spam)
        if (!enCooldown($pdo, $user['id'], 'connexion')) {
            ajouterPoints($pdo, $user['id'], 'connexion', 0.25, 'Connexion à la plateforme');
        }

        jsonResponse(['ok' => true, 'msg' => 'Connexion réussie !',
                      'redirect' => '/Lumihome/index.php?page=dashboard']);
    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'msg' => 'Erreur serveur.']);
    }
}

if ($action === 'logout') {
    session_destroy();
    jsonResponse(['ok' => true, 'redirect' => '/Lumihome/index.php']);
}

// ============================================================
// À partir d'ici : utilisateur connecté obligatoire
// ============================================================
if (!isLoggedIn()) jsonResponse(['ok' => false, 'msg' => 'Non connecté'], 401);

$uid    = (int)$_SESSION['user_id'];
$niveau = $_SESSION['niveau'] ?? 'debutant';
$cfg    = NIVEAUX_CONFIG[$niveau] ?? NIVEAUX_CONFIG['debutant'];

// ---- AJOUTER UNE LUMIÈRE ----
if ($action === 'add_light') {
    $nom    = trim($_POST['nom']    ?? '');
    $watt   = (float)($_POST['watt']   ?? 9);
    $marque = trim($_POST['marque'] ?? '');
    $modele = trim($_POST['modele'] ?? '');
    $piece  = (int)($_POST['piece']  ?? 0) ?: null;
    $conn   = $_POST['connect'] ?? 'Wi-Fi';
    $signal = min(100, max(0, (int)($_POST['signal'] ?? 90)));
    $temp   = min(6500, max(2700, (int)($_POST['temp'] ?? 4000)));
    $couleur = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['couleur'] ?? '')
               ? $_POST['couleur'] : '#FFFFFF';
    $desc   = trim($_POST['desc'] ?? '');

    if (!$nom)    jsonResponse(['ok' => false, 'msg' => 'Le nom est obligatoire.']);
    if ($watt < 1) jsonResponse(['ok' => false, 'msg' => 'La puissance doit être > 0.']);

    try {
        $pdo  = db();
        $stNb = $pdo->prepare('SELECT COUNT(*) FROM lumieres WHERE id_user = ?');
        $stNb->execute([$uid]);
        $nb = (int)$stNb->fetchColumn();

        if ($nb >= $cfg['limite_lumieres']) {
            $labels = ['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','avance'=>'Avancé','expert'=>'Expert'];
            jsonResponse(['ok' => false, 'limite' => true,
                'msg' => '🔒 Limite atteinte pour le niveau ' . ($labels[$niveau] ?? $niveau) .
                         ' (' . $cfg['limite_lumieres'] . ' lumières max). Montez de niveau pour continuer !']);
        }

        $pdo->prepare('INSERT INTO lumieres
                       (nom, marque, modele, description, connectivite, signal_force,
                        puissance_max_watt, temperature_couleur, couleur_hex, id_piece, id_user)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$nom, $marque, $modele, $desc, $conn, $signal, $watt, $temp, $couleur, $piece, $uid]);

        $lid = $pdo->lastInsertId();
        $st  = $pdo->prepare('SELECT l.*, p.nom AS piece_nom, p.emoji
                               FROM lumieres l LEFT JOIN pieces p ON l.id_piece = p.id WHERE l.id = ?');
        $st->execute([$lid]);

        ajouterPoints($pdo, $uid, 'action', 1.00, 'Ajout lumière : ' . $nom);

        jsonResponse(['ok' => true, 'light' => $st->fetch(),
                      'stats' => getStats($pdo, $uid),
                      'nb_lumieres' => $nb + 1,
                      'limite_lumieres' => $cfg['limite_lumieres']]);
    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'msg' => $e->getMessage()]);
    }
}

// ---- SUPPRIMER UNE LUMIÈRE ----
if ($action === 'delete_light') {
    $id = (int)($_POST['id'] ?? 0);

    try {
        $pdo = db();
        $st  = $pdo->prepare('SELECT id_user FROM lumieres WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row || $row['id_user'] != $uid)
            jsonResponse(['ok' => false, 'msg' => 'Non autorisé.']);

        $pdo->prepare('DELETE FROM lumieres WHERE id = ?')->execute([$id]);

        if (!enCooldown($pdo, $uid, 'delete'))
            ajouterPoints($pdo, $uid, 'action', 0.25, 'Suppression lumière #' . $id);

        $stNb = $pdo->prepare('SELECT COUNT(*) FROM lumieres WHERE id_user = ?');
        $stNb->execute([$uid]);

        jsonResponse(['ok' => true, 'stats' => getStats($pdo, $uid),
                      'nb_lumieres' => (int)$stNb->fetchColumn(),
                      'limite_lumieres' => $cfg['limite_lumieres']]);
    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'msg' => $e->getMessage()]);
    }
}

// ---- TOGGLE ON/OFF — Débutant+ ----
if ($action === 'toggle_light') {
    $id    = (int)($_POST['id'] ?? 0);
    $etat  = ($_POST['etat'] ?? '') === 'actif' ? 'actif' : 'inactif';
    $conso = $etat === 'actif' ? (float)($_POST['conso'] ?? 0) : 0.0;

    try {
        $pdo  = db();
        $stOwn = $pdo->prepare('SELECT id_user, etat FROM lumieres WHERE id = ?');
        $stOwn->execute([$id]);
        $own = $stOwn->fetch();
        if (!$own || $own['id_user'] != $uid)
            jsonResponse(['ok' => false, 'msg' => 'Non autorisé.']);

        $oldVal = $own['etat'];

        $pdo->prepare('UPDATE lumieres
                       SET etat = ?, conso_watt = ?, derniere_action = NOW(),
                           nb_allumages = nb_allumages + IF(?, 1, 0)
                       WHERE id = ?')
            ->execute([$etat, $conso, $etat === 'actif' ? 1 : 0, $id]);

        $pdo->prepare('INSERT INTO historique (id_lumiere, id_user, action, val_avant, val_apres)
                       VALUES (?, ?, ?, ?, ?)')
            ->execute([$id, $uid, 'toggle', $oldVal, $etat]);

        // Points uniquement si l'état change réellement + anti-abus
        $ptsGagnes = 0;
        if ($oldVal !== $etat && !enCooldown($pdo, $uid, 'toggle')) {
            $label = $etat === 'actif' ? 'Allumage' : 'Extinction';
            ajouterPoints($pdo, $uid, 'action', 0.50, $label . ' lumière #' . $id);
            $ptsGagnes = 0.50;
        }

        jsonResponse(['ok' => true, 'etat' => $etat, 'pts_gagnes' => $ptsGagnes,
                      'stats' => getStats($pdo, $uid)]);
    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'msg' => $e->getMessage()]);
    }
}

// ---- MODIFIER INTENSITÉ — Intermédiaire+ ----
if ($action === 'update_intensite') {
    if (!$cfg['peut_intensite'])
        jsonResponse(['ok' => false, 'locked' => true,
            'msg' => '🔒 Réglage d\'intensité débloqué au niveau Intermédiaire (3 pts requis).']);

    $id  = (int)($_POST['id'] ?? 0);
    $lum = min(100, max(0, (int)($_POST['luminosite'] ?? 100)));

    try {
        $pdo = db();
        $st  = $pdo->prepare('SELECT puissance_max_watt, etat, id_user FROM lumieres WHERE id = ?');
        $st->execute([$id]);
        $l = $st->fetch();
        if (!$l || $l['id_user'] != $uid) jsonResponse(['ok' => false, 'msg' => 'Non autorisé.']);

        $conso = $l['etat'] === 'actif' ? round($l['puissance_max_watt'] * $lum / 100, 2) : 0;

        $pdo->prepare('UPDATE lumieres
                       SET luminosite = ?, conso_watt = ?, derniere_action = NOW() WHERE id = ?')
            ->execute([$lum, $conso, $id]);

        $pdo->prepare('INSERT INTO historique (id_lumiere, id_user, action, val_avant, val_apres)
                       VALUES (?, ?, ?, ?, ?)')
            ->execute([$id, $uid, 'intensite', null, $lum . '%']);

        $ptsGagnes = 0;
        if (!enCooldown($pdo, $uid, 'intensite')) {
            ajouterPoints($pdo, $uid, 'action', 0.50, 'Intensité lumière #' . $id . ' → ' . $lum . '%');
            $ptsGagnes = 0.50;
        }

        jsonResponse(['ok' => true, 'conso' => $conso, 'pts_gagnes' => $ptsGagnes,
                      'stats' => getStats($pdo, $uid)]);
    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'msg' => $e->getMessage()]);
    }
}

// ---- MODIFIER COULEUR — Avancé+ ----
if ($action === 'update_couleur') {
    if (!$cfg['peut_couleur'])
        jsonResponse(['ok' => false, 'locked' => true,
            'msg' => '🔒 Changement de couleur débloqué au niveau Avancé (5 pts requis).']);

    $id  = (int)($_POST['id'] ?? 0);
    $col = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['couleur'] ?? '')
           ? $_POST['couleur'] : '#FFFFFF';

    try {
        $pdo = db();
        $stO = $pdo->prepare('SELECT id_user, couleur_hex FROM lumieres WHERE id = ?');
        $stO->execute([$id]);
        $l = $stO->fetch();
        if (!$l || $l['id_user'] != $uid) jsonResponse(['ok' => false, 'msg' => 'Non autorisé.']);

        $ancienne = $l['couleur_hex'];
        $pdo->prepare('UPDATE lumieres SET couleur_hex = ?, derniere_action = NOW() WHERE id = ?')
            ->execute([$col, $id]);

        $pdo->prepare('INSERT INTO historique (id_lumiere, id_user, action, val_avant, val_apres)
                       VALUES (?, ?, ?, ?, ?)')
            ->execute([$id, $uid, 'couleur', $ancienne, $col]);

        $ptsGagnes = 0;
        if ($ancienne !== $col && !enCooldown($pdo, $uid, 'couleur')) {
            ajouterPoints($pdo, $uid, 'action', 0.50, 'Couleur lumière #' . $id . ' → ' . $col);
            $ptsGagnes = 0.50;
        }

        jsonResponse(['ok' => true, 'couleur' => $col, 'pts_gagnes' => $ptsGagnes,
                      'stats' => getStats($pdo, $uid)]);
    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'msg' => $e->getMessage()]);
    }
}

// ---- MODIFIER LE PROFIL ----
if ($action === 'update_profil') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom    = trim($_POST['nom']    ?? '');
    $email  = trim($_POST['email']  ?? '');
    $sexe   = $_POST['sexe'] ?? 'Autre';
    $dob    = $_POST['dob']  ?? '';
    $membre = $_POST['type_membre'] ?? 'habitant';
    $mdp    = $_POST['mdp']  ?? '';
    $mdp2   = $_POST['mdp2'] ?? '';

    if (!$prenom || !$nom || !$email)
        jsonResponse(['ok' => false, 'msg' => 'Prénom, nom et email sont obligatoires.']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        jsonResponse(['ok' => false, 'msg' => 'Email invalide.']);
    if ($mdp && strlen($mdp) < 6)
        jsonResponse(['ok' => false, 'msg' => 'Mot de passe trop court (6 caractères minimum).']);
    if ($mdp && $mdp !== $mdp2)
        jsonResponse(['ok' => false, 'msg' => 'Les mots de passe ne correspondent pas.']);

    try {
        $pdo = db();
        $st  = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ? AND id != ?');
        $st->execute([$email, $uid]);
        if ($st->fetch()) jsonResponse(['ok' => false, 'msg' => 'Cet email est déjà utilisé.']);

        if ($mdp) {
            $pdo->prepare('UPDATE utilisateurs
                           SET prenom=?,nom=?,email=?,sexe=?,date_naissance=?,type_membre=?,mot_de_passe=?
                           WHERE id=?')
                ->execute([$prenom, $nom, $email, $sexe, $dob ?: null, $membre,
                           password_hash($mdp, PASSWORD_DEFAULT), $uid]);
        } else {
            $pdo->prepare('UPDATE utilisateurs
                           SET prenom=?,nom=?,email=?,sexe=?,date_naissance=?,type_membre=?
                           WHERE id=?')
                ->execute([$prenom, $nom, $email, $sexe, $dob ?: null, $membre, $uid]);
        }

        $_SESSION['prenom'] = $prenom;
        $_SESSION['nom']    = $nom;
        jsonResponse(['ok' => true, 'msg' => 'Profil mis à jour avec succès !']);
    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'msg' => 'Erreur : ' . $e->getMessage()]);
    }
}

// ---- CHANGER DE NIVEAU ----
if ($action === 'changer_niveau') {
    $nouveau = $_POST['niveau'] ?? '';
    if (!array_key_exists($nouveau, NIVEAUX_CONFIG))
        jsonResponse(['ok' => false, 'msg' => 'Niveau invalide.']);

    try {
        $pdo = db();
        $st  = $pdo->prepare('SELECT pts_requis, libelle FROM niveaux_config WHERE niveau = ?');
        $st->execute([$nouveau]);
        $config = $st->fetch();
        if (!$config) jsonResponse(['ok' => false, 'msg' => 'Configuration introuvable.']);

        $st2 = $pdo->prepare('SELECT points, niveau FROM utilisateurs WHERE id = ?');
        $st2->execute([$uid]);
        $user = $st2->fetch();

        if ((float)$user['points'] < (float)$config['pts_requis'])
            jsonResponse(['ok' => false, 'msg' => 'Points insuffisants pour ce niveau.']);
        if ($user['niveau'] === $nouveau)
            jsonResponse(['ok' => false, 'msg' => 'Vous êtes déjà à ce niveau.']);

        $pdo->prepare('UPDATE utilisateurs SET niveau = ? WHERE id = ?')->execute([$nouveau, $uid]);
        $_SESSION['niveau'] = $nouveau;

        jsonResponse(['ok' => true, 'msg' => '🎉 Niveau ' . $config['libelle'] . ' atteint !',
                      'niveau' => $nouveau, 'droits' => NIVEAUX_CONFIG[$nouveau]]);
    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'msg' => 'Erreur : ' . $e->getMessage()]);
    }
}

// ---- Action inconnue ----
jsonResponse(['ok' => false, 'msg' => 'Action inconnue : ' . $action], 400);

// ============================================================
// FONCTIONS UTILITAIRES
// ============================================================

/**
 * Vérifie si l'utilisateur est en cooldown pour ce type d'action.
 * Retourne true = en cooldown (pas de points accordés).
 * La règle : si un gain de points de cette catégorie existe dans les 3 dernières minutes → bloqué.
 */
function enCooldown(PDO $pdo, int $uid, string $typeAction): bool {
    $keyword = ANTI_SPAM[$typeAction] ?? null;
    if ($keyword === null) return false;

    $st = $pdo->prepare("
        SELECT COUNT(*) FROM points_log
        WHERE id_user = ?
          AND ts >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
          AND detail LIKE ?
    ");
    $st->execute([$uid, COOLDOWN_MINUTES, '%' . $keyword . '%']);
    return (int)$st->fetchColumn() > 0;
}

/**
 * Ajoute des points, met à jour les compteurs, recalcule le niveau.
 */
function ajouterPoints(PDO $pdo, int $uid, string $type, float $pts, string $detail = ''): void {
    $pdo->prepare('INSERT INTO points_log (id_user, type_gain, pts_gagnes, detail)
                   VALUES (?, ?, ?, ?)')
        ->execute([$uid, $type, $pts, $detail]);

    if ($type === 'connexion') {
        $pdo->prepare('UPDATE utilisateurs
                       SET pts_connexion = pts_connexion + ?,
                           points        = points + ?,
                           nb_connexions = nb_connexions + 1
                       WHERE id = ?')
            ->execute([$pts, $pts, $uid]);
    } else {
        $pdo->prepare('UPDATE utilisateurs
                       SET pts_actions = pts_actions + ?,
                           points      = points + ?,
                           nb_actions  = nb_actions + 1
                       WHERE id = ?')
            ->execute([$pts, $pts, $uid]);
    }

    // Recalcul niveau — montée uniquement, jamais descente
    $pdo->prepare("UPDATE utilisateurs
                   SET niveau = CASE
                       WHEN points >= 7 THEN 'expert'
                       WHEN points >= 5 THEN 'avance'
                       WHEN points >= 3 THEN 'intermediaire'
                       ELSE 'debutant'
                   END
                   WHERE id = ?")
        ->execute([$uid]);

    $row = $pdo->prepare('SELECT niveau FROM utilisateurs WHERE id = ?');
    $row->execute([$uid]);
    $_SESSION['niveau'] = $row->fetchColumn() ?: 'debutant';
}

/**
 * Statistiques de consommation.
 */
function getStats(PDO $pdo, int $uid): array {
    $st = $pdo->prepare('SELECT COUNT(*) AS total,
                                SUM(etat = "actif") AS on_count,
                                COALESCE(SUM(conso_watt), 0) AS conso_w
                         FROM lumieres WHERE id_user = ?');
    $st->execute([$uid]);
    $r = $st->fetch();
    $w = (float)$r['conso_w'];
    $h = round($w / 1000 * PRIX_KWH, 4);
    return [
        'total'      => (int)$r['total'],
        'actives'    => (int)$r['on_count'],
        'conso_w'    => round($w, 1),
        'cout_heure' => $h,
        'cout_jour'  => round($h * 24, 3),
        'cout_mois'  => round($h * 24 * 30, 2),
    ];
}
