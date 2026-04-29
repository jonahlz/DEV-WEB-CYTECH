<?php
require_once __DIR__ . '/includes/config.php';

$page    = $_GET['page'] ?? 'home';
$allowed = ['home', 'dashboard', 'profil', 'niveaux'];
if (!in_array($page, $allowed)) $page = 'home';

if ($page === 'dashboard' && !isLoggedIn()) { header('Location: /Lumihome/index.php'); exit; }
if ($page === 'profil'    && !isLoggedIn()) { header('Location: /Lumihome/index.php'); exit; }
if ($page === 'niveaux'   && !isLoggedIn()) { header('Location: /Lumihome/index.php'); exit; }

$user_prenom = escape($_SESSION['prenom'] ?? '');
$user_role   = $_SESSION['role']          ?? '';

if (isLoggedIn()) {
    $niveau_session = $_SESSION['niveau'] ?? 'debutant';
    $emoji_nav = match($niveau_session) {
        'intermediaire' => '⚡',
        'avance'        => '🌟',
        'expert'        => '👑',
        default         => '🌱'
    };
}

// Style inline commun pour tous les inputs/selects dans les modals
// On force TOUT ici pour qu'aucune règle Bootstrap ne puisse écraser
$inp = 'style="display:block;width:100%;padding:.3rem .65rem;font-size:.82rem;font-family:DM Sans,sans-serif;line-height:1.4;background:#1e2330;border:1px solid #2a3040;border-radius:7px;color:#e8eaf0;box-shadow:none;outline:none;margin:0"';
$sel = 'style="display:block;width:100%;padding:.3rem .65rem;font-size:.82rem;font-family:DM Sans,sans-serif;line-height:1.4;background:#1e2330;border:1px solid #2a3040;border-radius:7px;color:#e8eaf0;box-shadow:none;outline:none;margin:0;-webkit-appearance:none;appearance:none;background-image:url(\'data:image/svg+xml,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 16 16%27%3e%3cpath fill=%27none%27 stroke=%27%237a8299%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%272%27 d=%27m2 5 6 6 6-6%27/%3e%3c/svg%3e\');background-repeat:no-repeat;background-position:right .5rem center;background-size:12px;padding-right:1.8rem"';
$lbl = 'style="display:block;font-size:.72rem;color:#7a8299;margin:0 0 .15rem 0;font-weight:500"';
$grp = 'style="margin-bottom:.45rem"';
$row = 'style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem;margin-bottom:.45rem"';
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LumiHome<?= $page !== 'home' ? ' – ' . ucfirst($page) : '' ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/Lumihome/assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg lh-navbar sticky-top" data-bs-theme="dark">
  <div class="container-fluid px-4">
    <a class="navbar-brand lh-logo" href="/Lumihome/index.php">
      <i class="bi bi-lightbulb-fill me-1"></i>LumiHome
    </a>
    <button class="navbar-toggler lh-toggler" type="button"
            data-bs-toggle="collapse" data-bs-target="#lhNav"
            aria-controls="lhNav" aria-expanded="false" aria-label="Menu">
      <i class="bi bi-list"></i>
    </button>
    <div class="collapse navbar-collapse" id="lhNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link lh-nav-link <?= $page==='home' ? 'lh-active' : '' ?>" href="/Lumihome/index.php">
            <i class="bi bi-house me-1"></i>Accueil
          </a>
        </li>
        <?php if (isLoggedIn()): ?>
        <li class="nav-item">
          <a class="nav-link lh-nav-link <?= $page==='dashboard' ? 'lh-active' : '' ?>" href="/Lumihome/index.php?page=dashboard">
            <i class="bi bi-grid-1x2 me-1"></i>Tableau de bord
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link lh-nav-link <?= $page==='niveaux' ? 'lh-active' : '' ?>" href="/Lumihome/index.php?page=niveaux">
            <?= $emoji_nav ?> Niveaux
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link lh-nav-link <?= $page==='profil' ? 'lh-active' : '' ?>" href="/Lumihome/index.php?page=profil">
            <i class="bi bi-person me-1"></i>Mon profil
          </a>
        </li>
        <?php endif; ?>
      </ul>
      <div class="d-flex align-items-center gap-2 flex-wrap py-2 py-lg-0">
        <?php if (isLoggedIn()): ?>
          <span class="lh-badge-role"><i class="bi bi-shield-fill"></i><?= ucfirst($user_role) ?></span>
          <span class="lh-badge-niveau"><?= $emoji_nav ?> <?= ucfirst($_SESSION['niveau'] ?? 'debutant') ?></span>
          <span style="font-size:.85rem;color:#7a8299"><?= $user_prenom ?></span>
          <button class="lh-btn-outline lh-btn-sm" onclick="api('logout',{},'GET',true)">
            <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
          </button>
        <?php else: ?>
          <button class="lh-btn-outline" onclick="openModal('login-modal')">
            <i class="bi bi-box-arrow-in-right me-1"></i>Connexion
          </button>
          <button class="lh-btn-primary" onclick="openModal('reg-modal')">
            <i class="bi bi-person-plus me-1"></i>S'inscrire
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<div id="toast"></div>

<?php
if      ($page === 'home')      include __DIR__ . '/pages/home.php';
elseif  ($page === 'dashboard') include __DIR__ . '/pages/dashboard.php';
elseif  ($page === 'profil')    include __DIR__ . '/pages/profil.php';
elseif  ($page === 'niveaux')   include __DIR__ . '/pages/niveaux.php';
?>

<!-- MODAL INSCRIPTION -->
<div class="modal-overlay" id="reg-modal">
  <div class="lh-modal">
    <h2 style="font-family:Syne,sans-serif;font-size:1rem;font-weight:800;margin:0 0 .05rem 0;color:#e8eaf0">Créer un compte</h2>
    <p style="font-size:.72rem;color:#7a8299;margin:0 0 .5rem 0">Rejoignez la maison intelligente LumiHome.</p>

    <!-- Ligne 1 : Prénom + Nom -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem;margin-bottom:.35rem">
      <div><label <?= $lbl ?>>Prénom *</label><input id="r-prenom" placeholder="Sophie" <?= $inp ?>></div>
      <div><label <?= $lbl ?>>Nom *</label><input id="r-nom" placeholder="Martin" <?= $inp ?>></div>
    </div>
    <!-- Ligne 2 : Login + Email -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem;margin-bottom:.35rem">
      <div><label <?= $lbl ?>>Login *</label><input id="r-login" placeholder="Votre login" <?= $inp ?>></div>
      <div><label <?= $lbl ?>>Email *</label><input id="r-email" type="email" placeholder="Votre email" <?= $inp ?>></div>
    </div>
    <!-- Ligne 3 : Date naissance + Sexe -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem;margin-bottom:.35rem">
      <div><label <?= $lbl ?>>Date de naissance</label><input id="r-dob" type="date" <?= $inp ?>></div>
      <div><label <?= $lbl ?>>Sexe</label>
        <select id="r-sexe" <?= $sel ?>>
          <option value="M">Homme</option>
          <option value="F">Femme</option>
          <option value="Autre">Autre</option>
        </select>
      </div>
    </div>
    <!-- Ligne 4 : Type membre -->
    <div style="margin-bottom:.35rem"><label <?= $lbl ?>>Type de membre</label>
      <select id="r-membre" <?= $sel ?>>
        <option value="père">Père</option>
        <option value="mère">Mère</option>
        <option value="enfant">Enfant</option>
        <option value="habitant">Habitant</option>
      </select>
    </div>
    <!-- Ligne 5 : Mot de passe + Confirmer -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem;margin-bottom:.35rem">
      <div><label <?= $lbl ?>>Mot de passe *</label><input id="r-mdp" type="password" placeholder="6 car. min." <?= $inp ?>></div>
      <div><label <?= $lbl ?>>Confirmer *</label><input id="r-mdp2" type="password" placeholder="Répéter" <?= $inp ?>></div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:.5rem;padding-top:.45rem;border-top:1px solid #2a3040">
      <button class="btn btn-outline btn-sm" onclick="closeModal('reg-modal')">Annuler</button>
      <button class="btn btn-primary btn-sm" onclick="doRegister()">Créer mon compte →</button>
    </div>
  </div>
</div>

<!-- MODAL CONNEXION -->
<div class="modal-overlay" id="login-modal">
  <div class="lh-modal">
    <h2 style="font-family:Syne,sans-serif;font-size:1rem;font-weight:800;margin:0 0 .05rem 0;color:#e8eaf0">Connexion</h2>
    <p style="font-size:.72rem;color:#7a8299;margin:0 0 .6rem 0">Accédez à votre tableau de bord.</p>
    <div <?= $grp ?>><label <?= $lbl ?>>Login ou Email</label><input id="l-login" placeholder="Votre login ou email" autocomplete="username" <?= $inp ?>></div>
    <div <?= $grp ?>><label <?= $lbl ?>>Mot de passe</label><input id="l-mdp" type="password" placeholder="Votre mot de passe" autocomplete="current-password" <?= $inp ?>></div>
    <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:.6rem;padding-top:.55rem;border-top:1px solid #2a3040">
      <button class="btn btn-outline btn-sm" onclick="closeModal('login-modal')">Annuler</button>
      <button class="btn btn-primary btn-sm" onclick="doLogin()">Se connecter →</button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmOj0DuKaQoNQV2IHqIJQ3vB5sE" crossorigin="anonymous"></script>
<script src="/Lumihome/assets/js/app.js"></script>
</body>
</html>
