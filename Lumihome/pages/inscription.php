<?php
// ============================================================
// LumiHome — Page inscription autonome
// Accessible via : /Lumihome/pages/inscription.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';

$erreur = '';

if (
    isset($_POST['login'], $_POST['nom'], $_POST['prenom'],
          $_POST['email'], $_POST['mot_de_passe'],
          $_POST['confirm_mot_de_passe'], $_POST['sexe'],
          $_POST['date_naissance'])
) {
    $login  = trim($_POST['login']);
    $nom    = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email  = trim($_POST['email']);
    $mdp    = $_POST['mot_de_passe'];
    $mdp2   = $_POST['confirm_mot_de_passe'];
    $sexe   = $_POST['sexe'];
    $dob    = $_POST['date_naissance'];

    if ($mdp !== $mdp2) {
        $erreur = 'Les mots de passe ne sont pas identiques.';
    } elseif (strlen($mdp) < 6) {
        $erreur = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse email invalide.';
    } else {
        try {
            $pdo   = db();
            $check = $pdo->prepare('SELECT id FROM utilisateurs WHERE login = ? OR email = ?');
            $check->execute([$login, $email]);

            if ($check->fetch()) {
                $erreur = 'Ce login ou cet email est déjà utilisé.';
            } else {
                // Générer le token de vérification
                $token  = bin2hex(random_bytes(32));
                $expire = date('Y-m-d H:i:s', strtotime('+24 hours'));
                $hash   = password_hash($mdp, PASSWORD_DEFAULT);

                $pdo->prepare('
                    INSERT INTO utilisateurs
                        (login, nom, prenom, email, mot_de_passe, sexe,
                         date_naissance, token_verification, token_expire)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ')->execute([$login, $nom, $prenom, $email, $hash,
                             $sexe, $dob, $token, $expire]);

                // Envoyer l'email de vérification
                envoyerEmailDeVerification($email, $token);

                // Rediriger avec un message d'information
                header('Location: /Lumihome/pages/connection.php?inscription=ok');
                exit;
            }
        } catch (Exception $e) {
            $erreur = 'Erreur lors de l\'inscription. Veuillez réessayer.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LumiHome — Inscription</title>
    <link rel="stylesheet" href="/Lumihome/assets/css/style.css">
</head>
<body>

<nav>
    <a href="/Lumihome/index.php" class="logo">💡 LumiHome</a>
</nav>

<div style="min-height:calc(100vh - 64px);display:flex;align-items:center;
            justify-content:center;padding:2rem">
<div style="width:100%;max-width:480px">

    <h1 style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:.25rem">
        Créer un compte
    </h1>
    <p style="color:var(--muted);font-size:.9rem;margin-bottom:1.75rem">
        Rejoignez la maison intelligente LumiHome.
    </p>

    <?php if ($erreur): ?>
    <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);
                border-radius:8px;padding:.85rem 1rem;margin-bottom:1.25rem;
                color:#fca5a5;font-size:.88rem">
        ❌ <?= escape($erreur) ?>
    </div>
    <?php endif; ?>

    <form action="#" method="POST" class="card">

        <div class="form-group">
            <label class="form-label">Nom utilisateur *</label>
            <input class="form-input" type="text" name="login"
                   value="<?= escape($_POST['login'] ?? '') ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Nom *</label>
                <input class="form-input" type="text" name="nom"
                       value="<?= escape($_POST['nom'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Prénom *</label>
                <input class="form-input" type="text" name="prenom"
                       value="<?= escape($_POST['prenom'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Email *</label>
            <input class="form-input" type="email" name="email"
                   value="<?= escape($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Mot de passe *</label>
                <input class="form-input" type="password" name="mot_de_passe">
            </div>
            <div class="form-group">
                <label class="form-label">Confirmer *</label>
                <input class="form-input" type="password" name="confirm_mot_de_passe">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Sexe *</label>
                <select class="form-input" name="sexe">
                    <option value="">-- Choisir --</option>
                    <option value="M"     <?= ($_POST['sexe'] ?? '') === 'M'     ? 'selected' : '' ?>>Homme</option>
                    <option value="F"     <?= ($_POST['sexe'] ?? '') === 'F'     ? 'selected' : '' ?>>Femme</option>
                    <option value="Autre" <?= ($_POST['sexe'] ?? '') === 'Autre' ? 'selected' : '' ?>>Autre</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Date de naissance *</label>
                <input class="form-input" type="date" name="date_naissance"
                       value="<?= escape($_POST['date_naissance'] ?? '') ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-primary"
                style="width:100%;justify-content:center">
            S'inscrire →
        </button>

        <p style="margin-top:1rem;text-align:center;font-size:.85rem;color:var(--muted)">
            Vous avez déjà un compte ?
            <a href="/Lumihome/pages/connection.php" style="color:var(--accent)">
                Connectez-vous ici
            </a>
        </p>

    </form>
</div>
</div>

</body>
</html>
