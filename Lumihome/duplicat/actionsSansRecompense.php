<?php
// ============================================================
// LumiHome — API REST interne
// Toutes les actions AJAX/fetch passent par ce fichier.
// ============================================================

require_once __DIR__ . '/../includes/config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================================
// AUTH
// ============================================================

// ---- INSCRIPTION ----
if ($action === 'register') {
    $login  = trim($_POST['login']       ?? '');
    $nom    = trim($_POST['nom']         ?? '');
    $prenom = trim($_POST['prenom']      ?? '');
    $email  = trim($_POST['email']       ?? '');
    $mdp    = $_POST['mdp']              ?? '';
    $mdp2   = $_POST['mdp2']             ?? '';
    $sexe   = $_POST['sexe']             ?? 'Autre';
    $dob    = $_POST['dob']              ?? '';
    $membre = $_POST['type_membre']      ?? 'habitant';

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
                       (login, nom, prenom, email, mot_de_passe, sexe, date_naissance, type_membre, role)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'membre\')')
            ->execute([$login, $nom, $prenom, $email, $hash, $sexe, $dob ?: null, $membre]);

        $uid = $pdo->lastInsertId();
        $_SESSION['user_id'] = $uid;
        $_SESSION['login']   = $login;
        $_SESSION['nom']     = $nom;
        $_SESSION['prenom']  = $prenom;
        $_SESSION['role']    = 'membre';
        $_SESSION['niveau']  = 'debutant';

        $pdo->prepare('INSERT INTO connexions (id_user) VALUES (?)')->execute([$uid]);

        // Gain de points : première connexion
        ajouterPoints($pdo, $uid, 'connexion', 0.25, 'Inscription & première connexion');

        jsonResponse(['ok' => true, 'msg' => 'Bienvenue ' . $prenom . ' !',
                      'redirect' => '/Lumihome/index.php?page=dashboard']);
    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'msg' => 'Erreur : ' . $e->getMessage()]);
    }
}

// ---- CONNEXION ----
if ($action === 'login') {
    $login = trim($_POST['login'] ?? '');
    $mdp   = $_POST['mdp'] ?? '';

    if (!$login || !$mdp)
        jsonResponse(['ok' => false, 'msg' => 'Login et mot de passe requis.']);

    try {
        $pdo = db();
        $st  = $pdo->prepare('SELECT * FROM utilisateurs WHERE (login = ? OR email = ?) LIMIT 1');
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

        // Gain de points : connexion (+0.25)
        ajouterPoints($pdo, $user['id'], 'connexion', 0.25, 'Connexion à la plateforme');

        jsonResponse(['ok' => true, 'msg' => 'Connexion réussie !',
                      'redirect' => '/Lumihome/index.php?page=dashboard']);
    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'msg' => 'Erreur serveur.']);
    }
}

// ---- DÉCONNEXION ----
if ($action === 'logout') {
    session_destroy();
    jsonResponse(['ok' => true, 'redirect' => '/Lumihome/index.php']);
}

// ============================================================
// LUMIÈRES (utilisateur connecté)
// ============================================================

// ---- AJOUTER UNE LUMIÈRE ----
if ($action === 'add_light') {
    if (!isLoggedIn()) jsonResponse(['ok' => false, 'msg' => 'Non connecté'], 401);

    $nom     = trim($_POST['nom']    ?? '');
    $watt    = (float) ($_POST['watt']   ?? 9);
    $marque  = trim($_POST['marque'] ?? '');
    $modele  = trim($_POST['modele'] ?? '');
    $piece   = (int) ($_POST['piece']  ?? 0) ?: null;
    $conn    = $_POST['connect']     ?? 'Wi-Fi';
    $signal  = min(100, max(0, (int) ($_POST['signal'] ?? 90)));
    $temp    = min(6500, max(2700, (int) ($_POST['temp'] ?? 4000)));
    $couleur = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['couleur'] ?? '')
                ? $_POST['couleur'] : '#FFFFFF';
    $desc    = trim($_POST['desc'] ?? '');

    if (!$nom)    jsonResponse(['ok' => false, 'msg' => 'Le nom est obligatoire.']);
    if ($watt < 1) jsonResponse(['ok' => false, 'msg' => 'La puissance doit être supérieure à 0.']);

    try {
        $pdo = db();
        $pdo->prepare('INSERT INTO lumieres
                       (nom, marque, modele, description, connectivite, signal_force,
                        puissance_max_watt, temperature_couleur, couleur_hex, id_piece, id_user)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$nom, $marque, $modele, $desc, $conn, $signal,
                       $watt, $temp, $couleur, $piece, $_SESSION['user_id']]);

        $lid = $pdo->lastInsertId();
        $st  = $pdo->prepare('SELECT l.*, p.nom AS piece_nom, p.emoji
                               FROM lumieres l LEFT JOIN pieces p ON l.id_piece = p.id
                               WHERE l.id = ?');
        $st->execute([$lid]);

        // Gain de points : ajout d'un objet (+1.00)
        ajouterPoints($pdo, $_SESSION['user_id'], 'action', 1.00, 'Ajout lumière : ' . $nom);

        jsonResponse(['ok' => true, 'light' => $st->fetch(),
                      'stats' => getStats($pdo, $_SESSION['user_id'])]);
    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'msg' => $e->getMessage()]);
    }
}

// ---- SUPPRIMER UNE LUMIÈRE ----
if ($action === 'delete_light') {
    if (!isLoggedIn()) jsonResponse(['ok' => false, 'msg' => 'Non connecté'], 401);
    $id = (int) ($_POST['id'] ?? 0);

    try {
        $pdo = db();
        // Un utilisateur ne peut supprimer que ses propres lumières
        $st = $pdo->prepare('SELECT id_user FROM lumieres WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row || $row['id_user'] != $_SESSION['user_id'])
            jsonResponse(['ok' => false, 'msg' => 'Non autorisé.']);

        $pdo->prepare('DELETE FROM lumieres WHERE id = ?')->execute([$id]);

        // Gain de points : suppression (+0.25)
        ajouterPoints($pdo, $_SESSION['user_id'], 'action', 0.25, 'Suppression lumière #'.$id);

        jsonResponse(['ok' => true, 'stats' => getStats($pdo, $_SESSION['user_id'])]);
    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'msg' => $e->getMessage()]);
    }
}

// ---- ALLUMER / ÉTEINDRE (toggle) ----
if ($action === 'toggle_light') {
    if (!isLoggedIn()) jsonResponse(['ok' => false, 'msg' => 'Non connecté'], 401);
    $id    = (int) ($_POST['id'] ?? 0);
    $etat  = ($_POST['etat'] ?? '') === 'actif' ? 'actif' : 'inactif';
    $conso = $etat === 'actif' ? (float) ($_POST['conso'] ?? 0) : 0.0;

    try {
        $pdo = db();
        $old = $pdo->prepare('SELECT etat FROM lumieres WHERE id = ?');
        $old->execute([$id]);
        $oldVal = $old->fetchColumn();

        $pdo->prepare('UPDATE lumieres
                       SET etat = ?, conso_watt = ?, derniere_action = NOW(),
                           nb_allumages = nb_allumages + IF(?, 1, 0)
                       WHERE id = ?')
            ->execute([$etat, $conso, $etat === 'actif' ? 1 : 0, $id]);

        $pdo->prepare('INSERT INTO historique (id_lumiere, id_user, action, val_avant, val_apres)
                       VALUES (?, ?, ?, ?, ?)')
            ->execute([$id, $_SESSION['user_id'], 'toggle', $oldVal, $etat]);

        // Gain de points : action sur objet (+0.50)
        ajouterPoints($pdo, $_SESSION['user_id'], 'action', 0.50,
            ($etat === 'actif' ? 'Allumage' : 'Extinction') . ' lumière #'.$id);

        jsonResponse(['ok' => true, 'etat' => $etat,
                      'stats' => getStats($pdo, $_SESSION['user_id'])]);
    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'msg' => $e->getMessage()]);
    }
}

// ---- MODIFIER LUMINOSITÉ / COULEUR ----
if ($action === 'update_light') {
    if (!isLoggedIn()) jsonResponse(['ok' => false, 'msg' => 'Non connecté'], 401);
    $id  = (int) ($_POST['id'] ?? 0);
    $lum = min(100, max(0, (int) ($_POST['luminosite'] ?? 100)));
    $col = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['couleur'] ?? '')
            ? $_POST['couleur'] : '#FFFFFF';

    try {
        $pdo = db();
        $st  = $pdo->prepare('SELECT puissance_max_watt, etat FROM lumieres WHERE id = ?');
        $st->execute([$id]);
        $l = $st->fetch();
        $conso = $l['etat'] === 'actif' ? round($l['puissance_max_watt'] * $lum / 100, 2) : 0;

        $pdo->prepare('UPDATE lumieres
                       SET luminosite = ?, couleur_hex = ?, conso_watt = ?, derniere_action = NOW()
                       WHERE id = ?')
            ->execute([$lum, $col, $conso, $id]);

        $pdo->prepare('INSERT INTO historique (id_lumiere, id_user, action, val_avant, val_apres)
                       VALUES (?, ?, ?, ?, ?)')
            ->execute([$id, $_SESSION['user_id'], 'update_lum', null, $lum . '% ' . $col]);

        // Gain de points : modification (+0.50)
        ajouterPoints($pdo, $_SESSION['user_id'], 'action', 0.50, 'Modification lumière #'.$id.' → '.$lum.'% '.$col);

        jsonResponse(['ok' => true, 'conso' => $conso,
                      'stats' => getStats($pdo, $_SESSION['user_id'])]);
    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'msg' => $e->getMessage()]);
    }
}

// ---- MODIFIER LE PROFIL ----
if ($action === 'update_profil') {
    if (!isLoggedIn()) jsonResponse(['ok' => false, 'msg' => 'Non connecté'], 401);

    $prenom = trim($_POST['prenom']     ?? '');
    $nom    = trim($_POST['nom']        ?? '');
    $email  = trim($_POST['email']      ?? '');
    $sexe   = $_POST['sexe']            ?? 'Autre';
    $dob    = $_POST['dob']             ?? '';
    $membre = $_POST['type_membre']     ?? 'habitant';
    $mdp    = $_POST['mdp']             ?? '';
    $mdp2   = $_POST['mdp2']            ?? '';

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
        $st->execute([$email, $_SESSION['user_id']]);
        if ($st->fetch())
            jsonResponse(['ok' => false, 'msg' => 'Cet email est déjà utilisé par un autre compte.']);

        if ($mdp) {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE utilisateurs
                           SET prenom = ?, nom = ?, email = ?, sexe = ?,
                               date_naissance = ?, type_membre = ?, mot_de_passe = ?
                           WHERE id = ?')
                ->execute([$prenom, $nom, $email, $sexe, $dob ?: null, $membre,
                           $hash, $_SESSION['user_id']]);
        } else {
            $pdo->prepare('UPDATE utilisateurs
                           SET prenom = ?, nom = ?, email = ?, sexe = ?,
                               date_naissance = ?, type_membre = ?
                           WHERE id = ?')
                ->execute([$prenom, $nom, $email, $sexe, $dob ?: null,
                           $membre, $_SESSION['user_id']]);
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
    if (!isLoggedIn()) jsonResponse(['ok' => false, 'msg' => 'Non connecté'], 401);

    $nouveau = $_POST['niveau'] ?? '';
    $niveaux_valides = ['debutant', 'intermediaire', 'avance', 'expert'];
    if (!in_array($nouveau, $niveaux_valides))
        jsonResponse(['ok' => false, 'msg' => 'Niveau invalide.']);

    try {
        $pdo = db();
        $uid = $_SESSION['user_id'];

        // Récupérer les points requis pour ce niveau
        $st = $pdo->prepare('SELECT pts_requis, libelle FROM niveaux_config WHERE niveau = ?');
        $st->execute([$nouveau]);
        $config = $st->fetch();
        if (!$config) jsonResponse(['ok' => false, 'msg' => 'Configuration introuvable.']);

        // Vérifier que l'utilisateur a assez de points
        $st2 = $pdo->prepare('SELECT points, niveau FROM utilisateurs WHERE id = ?');
        $st2->execute([$uid]);
        $user = $st2->fetch();

        if ((float)$user['points'] < (float)$config['pts_requis'])
            jsonResponse(['ok' => false, 'msg' => 'Points insuffisants pour ce niveau.']);

        if ($user['niveau'] === $nouveau)
            jsonResponse(['ok' => false, 'msg' => 'Vous êtes déjà à ce niveau.']);

        // Mise à jour
        $pdo->prepare('UPDATE utilisateurs SET niveau = ? WHERE id = ?')
            ->execute([$nouveau, $uid]);

        $_SESSION['niveau'] = $nouveau;

        jsonResponse(['ok' => true, 'msg' => 'Niveau ' . $config['libelle'] . ' atteint !',
                      'niveau' => $nouveau]);
    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'msg' => 'Erreur : ' . $e->getMessage()]);
    }
}

// ---- Action inconnue ----
jsonResponse(['ok' => false, 'msg' => 'Action inconnue : ' . $action], 400);

// ============================================================
// Fonctions utilitaires
// ============================================================

/**
 * Ajoute des points à un utilisateur, met à jour nb_actions/nb_connexions
 * et recalcule son niveau.
 */
function ajouterPoints(PDO $pdo, int $uid, string $type, float $pts, string $detail = ''): void {
    // Log
    $pdo->prepare('INSERT INTO points_log (id_user, type_gain, pts_gagnes, detail)
                   VALUES (?, ?, ?, ?)')
        ->execute([$uid, $type, $pts, $detail]);

    // Mise à jour des compteurs selon le type
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

    // Recalcul du niveau automatique (on monte seulement, on ne descend pas)
    $pdo->prepare("UPDATE utilisateurs
                   SET niveau = CASE
                       WHEN points >= 7 THEN 'expert'
                       WHEN points >= 5 THEN 'avance'
                       WHEN points >= 3 THEN 'intermediaire'
                       ELSE 'debutant'
                   END
                   WHERE id = ?")
        ->execute([$uid]);

    // Mettre à jour la session
    $row = $pdo->prepare('SELECT niveau FROM utilisateurs WHERE id = ?');
    $row->execute([$uid]);
    $_SESSION['niveau'] = $row->fetchColumn() ?: 'debutant';
}

// ============================================================
// Fonctions utilitaires
// ============================================================

/**
 * Retourne les statistiques de consommation pour un utilisateur donné.
 */
function getStats(PDO $pdo, int $uid): array {
    $st = $pdo->prepare('SELECT COUNT(*) AS total,
                                SUM(etat = "actif") AS on_count,
                                COALESCE(SUM(conso_watt), 0) AS conso_w
                         FROM lumieres WHERE id_user = ?');
    $st->execute([$uid]);
    $r = $st->fetch();
    $w = (float) $r['conso_w'];
    $h = round($w / 1000 * PRIX_KWH, 4);
    return [
        'total'      => (int) $r['total'],
        'actives'    => (int) $r['on_count'],
        'conso_w'    => round($w, 1),
        'cout_heure' => $h,
        'cout_jour'  => round($h * 24, 3),
        'cout_mois'  => round($h * 24 * 30, 2),
    ];
}
