/* ============================================================
   LumiHome — JavaScript global
   Chargé en bas de index.php via <script src="...">

   Contenu :
     - Constante API (chemin vers api/actions.php)
     - Fonction générique api()
     - Toasts
     - Gestion des modals
     - Auth : doRegister(), doLogin(), doLogout()
   ============================================================ */

/* ── Point d'entrée de l'API ────────────────────────────── */
const API = '/Lumihome/api/actions.php';

/* ── Requête générique vers l'API ───────────────────────── */
/**
 * Envoie une requête POST (ou GET) à l'API et retourne la réponse JSON.
 *
 * @param {string}  action    - Nom de l'action (ex: 'login', 'toggle_light')
 * @param {object}  body      - Données à envoyer (ignoré si method='GET')
 * @param {string}  method    - 'POST' (défaut) ou 'GET'
 * @param {boolean} redirect  - Si true, suit data.redirect automatiquement
 */
async function api(action, body = {}, method = 'POST', redirect = false) {
  let response;

  if (method === 'GET') {
    response = await fetch(`${API}?action=${action}`);
  } else {
    const fd = new FormData();
    fd.append('action', action);
    Object.entries(body).forEach(([k, v]) => fd.append(k, v));
    response = await fetch(API, { method: 'POST', body: fd });
  }

  const data = await response.json();

  if (data.redirect && redirect !== false) {
    window.location = data.redirect;
  }

  return data;
}

/* ── Toasts ─────────────────────────────────────────────── */
/**
 * Affiche un message toast en bas à droite.
 * @param {string}  msg - Texte à afficher
 * @param {boolean} ok  - true = toast vert, false = toast rouge
 */
function toast(msg, ok = true) {
  const container = document.getElementById('toast');
  const el = document.createElement('div');
  el.className = 'toast-item ' + (ok ? 'toast-ok' : 'toast-err');
  el.textContent = msg;
  container.appendChild(el);
  requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('show')));
  setTimeout(() => {
    el.classList.remove('show');
    setTimeout(() => el.remove(), 300);
  }, 3500);
}

/* ── Modals ─────────────────────────────────────────────── */
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// Ferme la modal en cliquant sur le fond sombre
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) overlay.classList.remove('open');
    });
  });

  // Connexion avec la touche Entrée dans le champ mot de passe
  const mdpInput = document.getElementById('l-mdp');
  if (mdpInput) {
    mdpInput.addEventListener('keydown', e => {
      if (e.key === 'Enter') doLogin();
    });
  }
});

/* ── Inscription ────────────────────────────────────────── */
async function doRegister() {
  const data = await api('register', {
    prenom:      document.getElementById('r-prenom').value,
    nom:         document.getElementById('r-nom').value,
    login:       document.getElementById('r-login').value,
    email:       document.getElementById('r-email').value,
    sexe:        document.getElementById('r-sexe').value,
    dob:         document.getElementById('r-dob').value,
    type_membre: document.getElementById('r-membre').value,
    mdp:         document.getElementById('r-mdp').value,
    mdp2:        document.getElementById('r-mdp2').value,
  });
  toast(data.msg, data.ok);
  if (data.ok && data.redirect) {
    setTimeout(() => window.location = data.redirect, 800);
  }
}

/* ── Connexion ──────────────────────────────────────────── */
async function doLogin() {
  const data = await api('login', {
    login: document.getElementById('l-login').value,
    mdp:   document.getElementById('l-mdp').value,
  });
  toast(data.msg, data.ok);
  if (data.ok && data.redirect) {
    setTimeout(() => window.location = data.redirect, 600);
  }
}

/* ── Déconnexion ────────────────────────────────────────── */
async function doLogout() {
  const data = await api('logout', {});
  if (data.redirect) window.location = data.redirect;
}
