<?php
// ============================================================
// LumiHome — Fiche détaillée d'un membre
// Accessible via : /Lumihome/pages/profil_details.php?id=X
// ============================================================
require_once __DIR__ . '/../includes/config.php';
// db.php et functions.php sont fusionnés dans config.php
// session_start() est déjà appelé dans config.php

$user   = null;
$erreur = '';

if (isset($_GET['id'])) {
    $id_profil = (int) $_GET['id'];   // cast sécurisé, évite l'injection SQL

    $stmt = db()->prepare('SELECT * FROM utilisateurs WHERE id = ?');
    $stmt->execute([$id_profil]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $erreur = 'Profil introuvable.';
    }
} else {
    $erreur = 'Aucun identifiant fourni.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LumiHome — Profil membre</title>
    <link rel="stylesheet" href="/Lumihome/assets/css/style.css">
</head>
<body>

<nav>
    <a href="/Lumihome/index.php" class="logo">💡 LumiHome</a>
    <?php if (isLoggedIn()): ?>
    <div class="nav-right">
        <a href="/Lumihome/index.php?page=dashboard" class="btn btn-outline btn-sm">
            ← Dashboard
        </a>
    </div>
    <?php endif; ?>
</nav>

<div style="min-height:calc(100vh - 64px);display:flex;align-items:center;
            justify-content:center;padding:2rem">
<div style="width:100%;max-width:500px">

    <?php if ($erreur): ?>
    <div class="card" style="border-color:rgba(239,68,68,.35);color:#fca5a5;text-align:center">
        ❌ <?= escape($erreur) ?>
    </div>

    <?php else: ?>

    <!-- afficherProfilDetaille() est défini dans includes/config.php -->
    <?= afficherProfilDetaille($user) ?>

    <div style="text-align:center;margin-top:1.25rem">
        <a href="javascript:history.back()" class="btn btn-outline">← Retour</a>
    </div>

    <?php endif; ?>

</div>
</div>

</body>
</html>
