<?php
// ============================================================
// LumiHome — Page connexion autonome
// Accessible via : /Lumihome/pages/connection.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
// db.php et functions.php sont fusionnés dans config.php
// session_start() est déjà appelé dans config.php

$erreur  = '';
$info    = isset($_GET['inscription']) ? 'Inscription réussie ! Vérifiez votre email pour activer votre compte.' : '';

if (isset($_POST['login_ou_email']) && isset($_POST['mot_de_passe'])) {

    $login = $_POST['login_ou_email'];
    $mdp   = $_POST['mot_de_passe'];

    $stmt = db()->prepare('SELECT * FROM utilisateurs WHERE login = ? OR email = ?');
    $stmt->execute([$login, $login]);
    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($utilisateur && password_verify($mdp, $utilisateur['mot_de_passe'])) {
        // Stocker en session comme le reste du projet
        $_SESSION['user_id'] = $utilisateur['id'];
        $_SESSION['login']   = $utilisateur['login'];
        $_SESSION['nom']     = $utilisateur['nom'];
        $_SESSION['prenom']  = $utilisateur['prenom'];
        $_SESSION['role']    = $utilisateur['role'] ?? 'membre';

        // Rediriger vers le dashboard du projet principal
        header('Location: /Lumihome/index.php?page=dashboard');
        exit;
    } else {
        $erreur = 'Utilisateur non trouvé ou mot de passe incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LumiHome — Connexion</title>
    <link rel="stylesheet" href="/Lumihome/assets/css/style.css">
</head>
<body>

<nav>
    <a href="/Lumihome/index.php" class="logo">💡 LumiHome</a>
</nav>

<div style="min-height:calc(100vh - 64px);display:flex;align-items:center;justify-content:center;padding:2rem">
<div style="width:100%;max-width:420px">

    <h1 style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:.25rem">
        Se connecter
    </h1>
    <p style="color:var(--muted);font-size:.9rem;margin-bottom:1.75rem">
        Accédez à votre tableau de bord LumiHome.
    </p>

    <?php if ($info): ?>
    <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);
                border-radius:8px;padding:.85rem 1rem;margin-bottom:1.25rem;
                color:#86efac;font-size:.88rem">
        ✅ <?= escape($info) ?>
    </div>
    <?php endif; ?>

    <?php if ($erreur): ?>
    <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);
                border-radius:8px;padding:.85rem 1rem;margin-bottom:1.25rem;
                color:#fca5a5;font-size:.88rem">
        ❌ <?= escape($erreur) ?>
    </div>
    <?php endif; ?>

    <form action="#" method="POST" class="card">

        <div class="form-group">
            <label class="form-label">Nom utilisateur ou Email *</label>
            <input class="form-input" type="text" name="login_ou_email"
                   value="<?= escape($_POST['login_ou_email'] ?? '') ?>"
                   required autocomplete="username">
        </div>

        <div class="form-group">
            <label class="form-label">Mot de passe *</label>
            <input class="form-input" type="password" name="mot_de_passe"
                   required autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-primary w-full" style="width:100%;justify-content:center">
            Se connecter →
        </button>

        <p style="margin-top:1rem;text-align:center;font-size:.85rem;color:var(--muted)">
            Vous n'avez pas de compte ?
            <a href="/Lumihome/pages/inscription.php" style="color:var(--accent)">Inscrivez-vous ici</a>
        </p>

    </form>

</div>
</div>

</body>
</html>
