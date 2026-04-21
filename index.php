<?php
require_once __DIR__ . '/includes/config.php';

$page = $_GET['page'] ?? 'home';
$allowed = ['home','dashboard','admin','profil','login','register'];
if (!in_array($page, $allowed)) $page = 'home';

// Protections
if ($page === 'dashboard' && !isLoggedIn()) { header('Location: /Lumihome/index.php?page=login'); exit; }
if ($page === 'profil'    && !isLoggedIn()) { header('Location: /Lumihome/index.php?page=login'); exit; }
if ($page === 'admin'     && !isAdmin())     { header('Location: /Lumihome/index.php?page=dashboard'); exit; }
if (($page === 'login' || $page === 'register') && isLoggedIn()) { header('Location: /Lumihome/index.php?page=dashboard'); exit; }

// Données communes
$user_login  = escape($_SESSION['login']  ?? '');
$user_prenom = escape($_SESSION['prenom'] ?? '');
$user_role   = $_SESSION['role'] ?? '';

?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>LumiHome<?= $page!=='home' ? ' – '.ucfirst($page) : '' ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0d0f14; --surface:#161921; --surface2:#1e2330; --border:#2a3040;
  --accent:#f5c842; --accent2:#ff8c42; --text:#e8eaf0; --muted:#7a8299;
  --on:#22c55e; --off:#ef4444; --r:14px;
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
a{color:inherit;text-decoration:none}
input,select,textarea{font-family:inherit}

/* NAV */
nav{position:sticky;top:0;z-index:100;background:rgba(13,15,20,.92);backdrop-filter:blur(16px);
    border-bottom:1px solid var(--border);padding:0 2rem;display:flex;align-items:center;
    justify-content:space-between;height:64px}
.logo{font-family:'Syne',sans-serif;font-size:1.35rem;font-weight:800;color:var(--accent);display:flex;align-items:center;gap:.4rem}
.nav-links{display:flex;gap:1.5rem;list-style:none}
.nav-links a{color:var(--muted);font-size:.88rem;font-weight:500;transition:color .2s;padding:.3rem 0}
.nav-links a:hover,.nav-links a.active{color:var(--text)}
.nav-links a.active{border-bottom:2px solid var(--accent)}
.nav-right{display:flex;gap:.75rem;align-items:center}
.badge-role{font-size:.7rem;font-weight:600;padding:.15rem .5rem;border-radius:20px;background:rgba(245,200,66,.12);color:var(--accent);border:1px solid rgba(245,200,66,.25)}
.badge-admin{background:rgba(239,68,68,.12);color:#f87171;border-color:rgba(239,68,68,.25)}
.btn{padding:.5rem 1.1rem;border-radius:8px;border:none;font-family:inherit;font-size:.875rem;font-weight:500;cursor:pointer;transition:all .18s}
.btn-outline{background:transparent;border:1px solid var(--border);color:var(--muted)}
.btn-outline:hover{border-color:var(--accent);color:var(--accent)}
.btn-primary{background:var(--accent);color:#0d0f14;font-weight:600}
.btn-primary:hover{background:#ffd555;transform:translateY(-1px)}
.btn-danger{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3)}
.btn-danger:hover{background:rgba(239,68,68,.25)}
.btn-sm{padding:.3rem .75rem;font-size:.78rem}

/* TOAST */
#toast{position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem}
.toast-item{padding:.75rem 1.25rem;border-radius:10px;font-size:.875rem;font-weight:500;opacity:0;transform:translateY(8px);transition:all .3s;max-width:320px}
.toast-ok{background:#14532d;color:#86efac;border:1px solid #166534}
.toast-err{background:#450a0a;color:#fca5a5;border:1px solid #7f1d1d}
.toast-item.show{opacity:1;transform:translateY(0)}

/* PAGE WRAPPER */
.page{max-width:1200px;margin:0 auto;padding:2rem}

/* CARDS */
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:1.5rem}
.card-sm{padding:1rem}

/* FORMS */
.form-group{margin-bottom:1rem}
.form-label{display:block;font-size:.82rem;color:var(--muted);margin-bottom:.35rem;font-weight:500}
.form-input{width:100%;padding:.65rem 1rem;background:var(--surface2);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:inherit;font-size:.9rem;transition:border-color .2s}
.form-input:focus{outline:none;border-color:var(--accent)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.form-error{font-size:.78rem;color:#f87171;margin-top:.3rem;display:none}

/* MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(4px);z-index:500;display:none;align-items:center;justify-content:center;padding:1rem}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:calc(var(--r)*1.5);padding:2rem;width:100%;max-width:480px;max-height:90vh;overflow-y:auto}
.modal h2{font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;margin-bottom:.25rem}
.modal-sub{font-size:.83rem;color:var(--muted);margin-bottom:1.5rem}
.modal-footer{display:flex;justify-content:flex-end;gap:.75rem;margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--border)}

/* LUMIÈRE CARD */
.lights-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:1rem}
.light-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:1.25rem;transition:border-color .2s,transform .15s;position:relative;overflow:hidden}
.light-card:hover{border-color:rgba(245,200,66,.25);transform:translateY(-1px)}
.light-card.on::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--accent),var(--accent2))}
.light-card .top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.9rem}
.light-icon{width:44px;height:44px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;transition:background .3s}
.light-icon.on{background:rgba(245,200,66,.15)} .light-icon.off{background:var(--surface2)}
.light-name{font-weight:600;font-size:.95rem}
.light-room{font-size:.76rem;color:var(--muted);margin-top:.1rem}
.status-pill{padding:.18rem .55rem;border-radius:20px;font-size:.7rem;font-weight:600;white-space:nowrap}
.pill-on{background:rgba(34,197,94,.15);color:var(--on)} .pill-off{background:rgba(239,68,68,.1);color:var(--off)} .pill-err{background:rgba(249,115,22,.1);color:#f97316}
.light-meta{display:grid;grid-template-columns:1fr 1fr;gap:.4rem .75rem;margin:.75rem 0}
.meta-lbl{font-size:.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:.4px}
.meta-val{font-size:.83rem;font-weight:500;margin-top:.08rem}
.lum-bar{height:3px;background:var(--surface2);border-radius:2px;overflow:hidden;margin:.5rem 0 .25rem}
.lum-fill{height:100%;background:linear-gradient(90deg,var(--accent),var(--accent2));transition:width .4s}
.light-footer{display:flex;justify-content:space-between;align-items:center;margin-top:.6rem}
.conso-badge{font-size:.75rem;color:var(--muted)}
.toggle{position:relative;width:40px;height:22px;flex-shrink:0}
.toggle input{opacity:0;position:absolute;inset:0;cursor:pointer;width:100%;height:100%}
.toggle-track{position:absolute;inset:0;background:var(--surface2);border:1px solid var(--border);border-radius:11px;transition:background .25s;pointer-events:none}
.toggle input:checked ~ .toggle-track{background:var(--accent)}
.toggle-thumb{position:absolute;top:2px;left:2px;width:16px;height:16px;background:#fff;border-radius:50%;transition:transform .25s;pointer-events:none}
.toggle input:checked ~ .toggle-thumb{transform:translateX(18px)}

/* SLIDER */
.lum-slider{-webkit-appearance:none;appearance:none;width:100%;height:4px;border-radius:2px;background:var(--surface2);outline:none;margin:.4rem 0}
.lum-slider::-webkit-slider-thumb{-webkit-appearance:none;width:14px;height:14px;border-radius:50%;background:var(--accent);cursor:pointer}

/* STAT CARDS */
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:2rem}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:1.25rem}
.stat-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem}
.stat-icon{font-size:1.3rem}
.stat-val{font-family:'Syne',sans-serif;font-size:1.75rem;font-weight:800}
.stat-lbl{font-size:.78rem;color:var(--muted)}
.stat-sub{font-size:.75rem;color:var(--muted);margin-top:.2rem}

/* SEARCH */
.search-bar{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:1.25rem;margin-bottom:1.5rem;display:flex;gap:.75rem;flex-wrap:wrap}
.search-bar .form-input{flex:1;min-width:160px;margin:0}

/* ADMIN TABLE */
.admin-table{width:100%;border-collapse:collapse;font-size:.875rem}
.admin-table th{text-align:left;padding:.65rem 1rem;font-size:.75rem;text-transform:uppercase;letter-spacing:.4px;color:var(--muted);border-bottom:1px solid var(--border)}
.admin-table td{padding:.65rem 1rem;border-bottom:1px solid rgba(42,48,64,.5)}
.admin-table tr:hover td{background:rgba(30,35,48,.5)}
.badge-banned{background:rgba(239,68,68,.1);color:#f87171;padding:.15rem .5rem;border-radius:4px;font-size:.72rem;font-weight:600}
.badge-ok{background:rgba(34,197,94,.1);color:var(--on);padding:.15rem .5rem;border-radius:4px;font-size:.72rem;font-weight:600}
.badge-membre{background:rgba(99,102,241,.1);color:#a5b4fc;padding:.15rem .5rem;border-radius:4px;font-size:.72rem;font-weight:600}
.badge-admin-t{background:rgba(245,200,66,.1);color:var(--accent);padding:.15rem .5rem;border-radius:4px;font-size:.72rem;font-weight:600}

/* PAGE AUTH */
.auth-wrap{min-height:calc(100vh - 64px);display:flex;align-items:center;justify-content:center;padding:2rem}
.auth-box{width:100%;max-width:440px}
.auth-title{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;margin-bottom:.25rem}

/* HERO */
.hero{padding:4rem 2rem 3rem;text-align:center;position:relative}
.hero::before{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:500px;height:300px;background:radial-gradient(ellipse,rgba(245,200,66,.06),transparent 70%);pointer-events:none}
.hero h1{font-family:'Syne',sans-serif;font-size:clamp(2rem,6vw,4rem);font-weight:800;line-height:1.05;letter-spacing:-1px;margin-bottom:.75rem}
.hero h1 span{color:var(--accent)}
.hero p{font-size:1rem;color:var(--muted);max-width:480px;margin:0 auto 2rem;line-height:1.7}

/* DEMO GRID (visiteur – version réduite) */
.demo-light{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1rem;display:flex;align-items:center;gap:.75rem}
.demo-dot{width:10px;height:10px;border-radius:50%}
.demo-dot.on{background:var(--accent);box-shadow:0 0 6px var(--accent)}
.demo-dot.off{background:var(--surface2);border:1px solid var(--border)}

/* SECTION HEADER */
.sec-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem}
.sec-title{font-family:'Syne',sans-serif;font-size:1.25rem;font-weight:700}

/* HISTORY */
.hist-item{display:flex;gap:.75rem;align-items:flex-start;padding:.65rem 0;border-bottom:1px solid rgba(42,48,64,.5)}
.hist-icon{width:32px;height:32px;border-radius:8px;background:var(--surface2);display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0}
.hist-text{font-size:.85rem}
.hist-sub{font-size:.75rem;color:var(--muted);margin-top:.1rem}

@media(max-width:640px){.form-row{grid-template-columns:1fr}.nav-links{display:none}.stats-row{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>

<!-- ====== NAVBAR ====== -->
<nav>
  <a href="/Lumihome/index.php" class="logo">💡 LumiHome</a>
  <ul class="nav-links">
    <li><a href="/Lumihome/index.php" class="<?= $page==='home'?'active':'' ?>">Accueil</a></li>
    <?php if(isLoggedIn()): ?>
    <li><a href="/Lumihome/index.php?page=dashboard" class="<?= $page==='dashboard'?'active':'' ?>">Tableau de bord</a></li>
    <li><a href="/Lumihome/index.php?page=profil" class="<?= $page==='profil'?'active':'' ?>">Mon profil</a></li>
    <?php if(isAdmin()): ?>
    <li><a href="/Lumihome/index.php?page=admin" class="<?= $page==='admin'?'active':'' ?>">Administration</a></li>
    <?php endif; ?>
    <?php endif; ?>
  </ul>
  <div class="nav-right">
    <?php if(isLoggedIn()): ?>
      <span class="badge-role <?= $user_role==='admin'?'badge-admin':'' ?>"><?= $user_role==='admin'?'Admin':'Membre' ?></span>
      <span style="font-size:.85rem;color:var(--muted)"><?= $user_prenom ?></span>
      <button class="btn btn-outline btn-sm" onclick="api('logout',{},'GET',true)">Déconnexion</button>
    <?php else: ?>
      <button class="btn btn-outline" onclick="openModal('login-modal')">Connexion</button>
      <button class="btn btn-primary" onclick="openModal('reg-modal')">S'inscrire</button>
    <?php endif; ?>
  </div>
</nav>

<!-- ====== TOAST ====== -->
<div id="toast"></div>

<?php
// ====== PAGES ======
if ($page === 'home') include __DIR__.'/pages/home.php';
elseif ($page === 'dashboard') include __DIR__.'/pages/dashboard.php';
elseif ($page === 'admin') include __DIR__.'/pages/admin.php';
elseif ($page === 'profil') include __DIR__.'/pages/profil.php';
?>

<!-- ====== MODAL INSCRIPTION ====== -->
<div class="modal-overlay" id="reg-modal">
<div class="modal">
  <h2>Créer un compte</h2>
  <p class="modal-sub">Rejoignez la maison intelligente LumiHome.</p>
  <div class="form-row">
    <div class="form-group"><label class="form-label">Prénom *</label><input class="form-input" id="r-prenom" placeholder="Sophie"></div>
    <div class="form-group"><label class="form-label">Nom *</label><input class="form-input" id="r-nom" placeholder="Martin"></div>
  </div>
  <div class="form-group"><label class="form-label">Login *</label><input class="form-input" id="r-login" placeholder="Votre login"></div>
  <div class="form-group"><label class="form-label">Email *</label><input class="form-input" id="r-email" type="email" placeholder="Votre email"></div>
  <div class="form-row">
    <div class="form-group"><label class="form-label">Date de naissance</label><input class="form-input" id="r-dob" type="date"></div>
    <div class="form-group"><label class="form-label">Sexe</label>
      <select class="form-input" id="r-sexe"><option value="M">Homme</option><option value="F">Femme</option><option value="Autre">Autre</option></select>
    </div>
  </div>
  <div class="form-group"><label class="form-label">Type de membre</label>
    <select class="form-input" id="r-membre">
      <option value="père">Père</option><option value="mère">Mère</option><option value="enfant">Enfant</option><option value="habitant">Habitant</option>
    </select>
  </div>
  <div class="form-group"><label class="form-label">Mot de passe *</label><input class="form-input" id="r-mdp" type="password" placeholder="6 caractères minimum"></div>
  <div class="form-group"><label class="form-label">Confirmer le mot de passe *</label><input class="form-input" id="r-mdp2" type="password" placeholder="Répéter le mot de passe"></div>
  <div class="modal-footer">
    <button class="btn btn-outline" onclick="closeModal('reg-modal')">Annuler</button>
    <button class="btn btn-primary" onclick="doRegister()">Créer mon compte →</button>
  </div>
</div>
</div>

<!-- ====== MODAL CONNEXION ====== -->
<div class="modal-overlay" id="login-modal">
<div class="modal">
  <h2>Connexion</h2>
  <p class="modal-sub">Accédez à votre tableau de bord.</p>
  <div style="background:rgba(245,200,66,.07);border:1px solid rgba(245,200,66,.2);border-radius:8px;padding:.6rem 1rem;margin-bottom:1rem;font-size:.8rem;color:var(--muted)">
    <strong style="color:var(--accent)">Compte administrateur :</strong> &nbsp;<code style="color:var(--text)">admin</code> / <code style="color:var(--text)">password</code>
  </div>
  <div class="form-group"><label class="form-label">Login ou Email</label><input class="form-input" id="l-login" placeholder="Votre login ou email" autocomplete="username"></div>
  <div class="form-group"><label class="form-label">Mot de passe</label><input class="form-input" id="l-mdp" type="password" placeholder="Votre mot de passe" autocomplete="current-password" onkeydown="if(event.key==='Enter')doLogin()"></div>
  <div class="modal-footer">
    <button class="btn btn-outline" onclick="closeModal('login-modal')">Annuler</button>
    <button class="btn btn-primary" onclick="doLogin()">Se connecter →</button>
  </div>
</div>
</div>

<!-- ====== MODAL BAN ====== -->
<div class="modal-overlay" id="ban-modal">
<div class="modal">
  <h2>Bannir l'utilisateur</h2>
  <p class="modal-sub" id="ban-modal-sub">Précisez la raison du bannissement.</p>
  <input type="hidden" id="ban-uid">
  <div class="form-group"><label class="form-label">Raison du bannissement</label><input class="form-input" id="ban-raison" placeholder="ex: Non-respect des règles"></div>
  <div class="modal-footer">
    <button class="btn btn-outline" onclick="closeModal('ban-modal')">Annuler</button>
    <button class="btn btn-danger" onclick="doBan()">Confirmer le bannissement</button>
  </div>
</div>
</div>

<script>
const API = '/Lumihome/api/index.php';

// ---- API helper ----
async function api(action, body={}, method='POST', redirect=false) {
  const fd = new FormData();
  fd.append('action', action);
  Object.entries(body).forEach(([k,v]) => fd.append(k,v));
  const r = await fetch(API, {method, body: method==='POST'?fd:undefined,
    ...(method==='GET'?{headers:{}}:{})});
  const url = method==='GET'? API+'?action='+action : null;
  const res = await (method==='GET'? fetch(url) : Promise.resolve(r));
  const data = method==='GET'? await res.json() : await r.json();
  if (data.redirect && redirect !== false) { window.location = data.redirect; return data; }
  return data;
}

async function apiGET(action) {
  const r = await fetch(API+'?action='+action);
  return r.json();
}

// ---- Toast ----
function toast(msg, ok=true) {
  const t = document.getElementById('toast');
  const el = document.createElement('div');
  el.className = 'toast-item '+(ok?'toast-ok':'toast-err');
  el.textContent = msg;
  t.appendChild(el);
  requestAnimationFrame(()=>{ requestAnimationFrame(()=>el.classList.add('show')); });
  setTimeout(()=>{ el.classList.remove('show'); setTimeout(()=>el.remove(),300); }, 3500);
}

// ---- Modals ----
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); }));

// ---- Register ----
async function doRegister() {
  const d = await api('register', {
    prenom: document.getElementById('r-prenom').value,
    nom:    document.getElementById('r-nom').value,
    login:  document.getElementById('r-login').value,
    email:  document.getElementById('r-email').value,
    sexe:   document.getElementById('r-sexe').value,
    dob:    document.getElementById('r-dob').value,
    type_membre: document.getElementById('r-membre').value,
    mdp:    document.getElementById('r-mdp').value,
    mdp2:   document.getElementById('r-mdp2').value,
  });
  toast(d.msg, d.ok);
  if (d.ok && d.redirect) setTimeout(()=>window.location=d.redirect, 800);
}

// ---- Login ----
async function doLogin() {
  const d = await api('login', {
    login: document.getElementById('l-login').value,
    mdp:   document.getElementById('l-mdp').value,
  });
  toast(d.msg, d.ok);
  if (d.ok && d.redirect) setTimeout(()=>window.location=d.redirect, 600);
}

// ---- Logout ----
async function doLogout() {
  const d = await api('logout',{});
  if(d.redirect) window.location=d.redirect;
}

// ---- Ban helpers ----
function openBan(uid, login) {
  document.getElementById('ban-uid').value = uid;
  document.getElementById('ban-modal-sub').textContent = 'Bannir : '+login;
  document.getElementById('ban-raison').value = '';
  openModal('ban-modal');
}
async function doBan() {
  const d = await api('ban_user',{uid:document.getElementById('ban-uid').value, raison:document.getElementById('ban-raison').value, ban:1});
  toast(d.msg, d.ok);
  if(d.ok){ closeModal('ban-modal'); if(typeof loadUsers==='function') loadUsers(); }
}
async function doUnban(uid) {
  const d = await api('ban_user',{uid,ban:0});
  toast(d.msg, d.ok);
  if(d.ok && typeof loadUsers==='function') loadUsers();
}
async function doDelete(uid, login) {
  if(!confirm('Supprimer définitivement '+login+' ?')) return;
  const d = await api('delete_user',{uid});
  toast(d.msg, d.ok);
  if(d.ok && typeof loadUsers==='function') loadUsers();
}
</script>
</body>
</html>
