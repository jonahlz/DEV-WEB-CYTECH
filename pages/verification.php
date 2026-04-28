<?php
// ============================================================
// LumiHome — Vérification de compte par token email
// Accessible via : /Lumihome/pages/verification.php?token=xxx
// ============================================================
require_once __DIR__ . '/../includes/config.php';

$message = '';
$succes  = false;

if (!empty($_GET['token'])) {
    $token = trim($_GET['token']);

    try {
        $pdo = db();
        $st  = $pdo->prepare('
            SELECT id FROM utilisateurs
            WHERE token_verification = ?
              AND token_expire > NOW()
            LIMIT 1
        ');
        $st->execute([$token]);
        $user = $st->fetch();

        if ($user) {
            $pdo->prepare('
                UPDATE utilisateurs
                SET est_verifie = 1,
                    token_verification = NULL,
                    token_expire = NULL
                WHERE id = ?
            ')->execute([$user['id']]);

            $succes  = true;
            $message = 'Votre compte a bien été confirmé. Vous pouvez maintenant vous connecter.';
        } else {
            $message = 'Ce lien de vérification est invalide ou a expiré.';
        }
    } catch (Exception $e) {
        $message = 'Une erreur est survenue. Veuillez réessayer.';
    }
} else {
    $message = 'Aucun token fourni.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LumiHome — Vérification du compte</title>
  <link rel="stylesheet" href="/Lumihome/assets/css/style.css">
  <style>
    .verify-wrap {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }
    .verify-box {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--r);
      padding: 2.5rem 2rem;
      max-width: 460px;
      width: 100%;
      text-align: center;
    }
    .verify-icon { font-size: 3rem; margin-bottom: 1rem; }
    .verify-box h1 {
      font-family: 'Syne', sans-serif;
      font-size: 1.5rem;
      font-weight: 800;
      margin-bottom: .75rem;
    }
    .verify-box p { color: var(--muted); font-size: .95rem; margin-bottom: 1.5rem; }
  </style>
</head>
<body>
  <div class="verify-wrap">
    <div class="verify-box">
      <div class="verify-icon"><?= $succes ? '✅' : '❌' ?></div>
      <h1><?= $succes ? 'Compte confirmé !' : 'Vérification échouée' ?></h1>
      <p><?= escape($message) ?></p>
      <a href="/Lumihome/index.php" class="btn btn-primary">
        <?= $succes ? 'Se connecter →' : 'Retour à l\'accueil' ?>
      </a>
    </div>
  </div>
</body>
</html>
