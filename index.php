<?php
// ============================================================
// LumiHome — Routeur principal
// ============================================================
require_once __DIR__ . '/includes/config.php';

// ── Routing ──────────────────────────────────────────────────
$page    = $_GET['page'] ?? 'home';
$allowed = ['home', 'dashboard', 'profil'];
if (!in_array($page, $allowed)) $page = 'home';

// ── Protections d'accès ──────────────────────────────────────
if ($page === 'dashboard' && !isLoggedIn()) {
    header('Location: /Lumihome/index.php');
    exit;
}
if ($page === 'profil' && !isLoggedIn()) {
    header('Location: /Lumihome/index.php');
    exit;
}

// ── Variables communes injectées dans la vue ─────────────────
$user_prenom = escape($_SESSION['prenom'] ?? '');
$user_role   = $_SESSION['role']          ?? '';

?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LumiHome<?= $page !== 'home' ? ' – ' . ucfirst($page) : '' ?></title>
  <link rel="stylesheet" href="/Lumihome/assets/css/style.css">
</head>
<body>

<!-- ====== NAVBAR ====== -->
<nav>
  <a href="/Lumihome/index.php" class="logo">💡 LumiHome</a>

  <ul class="nav-links">
    <li>
      <a href="/Lumihome/index.php"
         class="<?= $page === 'home' ? 'active' : '' ?>">Accueil</a>
    </li>
    <?php if (isLoggedIn()): ?>
    <li>
      <a href="/Lumihome/index.php?page=dashboard"
         class="<?= $page === 'dashboard' ? 'active' : '' ?>">Tableau de bord</a>
    </li>
    <li>
      <a href="/Lumihome/index.php?page=profil"
         class="<?= $page === 'profil' ? 'active' : '' ?>">Mon profil</a>
    </li>
    <?php endif; ?>
  </ul>

  <div class="nav-right">
    <?php if (isLoggedIn()): ?>
      <span class="badge-role"><?= ucfirst($user_role) ?></span>
      <span style="font-size:.85rem;color:var(--muted)"><?= $user_prenom ?></span>
      <button class="btn btn-outline btn-sm" onclick="api('logout',{},'GET',true)">
        Déconnexion
      </button>
    <?php else: ?>
      <button class="btn btn-outline" onclick="openModal('login-modal')">Connexion</button>
      <button class="btn btn-primary" onclick="openModal('reg-modal')">S'inscrire</button>
    <?php endif; ?>
  </div>
</nav>

<!-- ====== CONTENEUR TOAST ====== -->
<div id="toast"></div>

<!-- ====== CONTENU DE LA PAGE ====== -->
<?php
if      ($page === 'home')      include __DIR__ . '/pages/home.php';
elseif  ($page === 'dashboard') include __DIR__ . '/pages/dashboard.php';
elseif  ($page === 'profil')    include __DIR__ . '/pages/profil.php';
?>

<!-- ====== MODAL : INSCRIPTION ====== -->
<div class="modal-overlay" id="reg-modal">
  <div class="modal">
    <h2>Créer un compte</h2>
    <p class="modal-sub">Rejoignez la maison intelligente LumiHome.</p>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Prénom *</label>
        <input class="form-input" id="r-prenom" placeholder="Sophie">
      </div>
      <div class="form-group">
        <label class="form-label">Nom *</label>
        <input class="form-input" id="r-nom" placeholder="Martin">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Login *</label>
      <input class="form-input" id="r-login" placeholder="Votre login">
    </div>
    <div class="form-group">
      <label class="form-label">Email *</label>
      <input class="form-input" id="r-email" type="email" placeholder="Votre email">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Date de naissance</label>
        <input class="form-input" id="r-dob" type="date">
      </div>
      <div class="form-group">
        <label class="form-label">Sexe</label>
        <select class="form-input" id="r-sexe">
          <option value="M">Homme</option>
          <option value="F">Femme</option>
          <option value="Autre">Autre</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Type de membre</label>
      <select class="form-input" id="r-membre">
        <option value="père">Père</option>
        <option value="mère">Mère</option>
        <option value="enfant">Enfant</option>
        <option value="habitant">Habitant</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Mot de passe *</label>
      <input class="form-input" id="r-mdp" type="password" placeholder="6 caractères minimum">
    </div>
    <div class="form-group">
      <label class="form-label">Confirmer le mot de passe *</label>
      <input class="form-input" id="r-mdp2" type="password" placeholder="Répéter le mot de passe">
    </div>

    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('reg-modal')">Annuler</button>
      <button class="btn btn-primary" onclick="doRegister()">Créer mon compte →</button>
    </div>
  </div>
</div>

<!-- ====== MODAL : CONNEXION ====== -->
<div class="modal-overlay" id="login-modal">
  <div class="modal">
    <h2>Connexion</h2>
    <p class="modal-sub">Accédez à votre tableau de bord.</p>

    <div class="form-group">
      <label class="form-label">Login ou Email</label>
      <input class="form-input" id="l-login"
             placeholder="Votre login ou email" autocomplete="username">
    </div>
    <div class="form-group">
      <label class="form-label">Mot de passe</label>
      <input class="form-input" id="l-mdp" type="password"
             placeholder="Votre mot de passe" autocomplete="current-password">
    </div>

    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('login-modal')">Annuler</button>
      <button class="btn btn-primary" onclick="doLogin()">Se connecter →</button>
    </div>
  </div>
</div>

<!-- ====== JS global ====== -->
<script src="/Lumihome/assets/js/app.js"></script>

</body>
</html>
