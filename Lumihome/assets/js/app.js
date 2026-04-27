/* ============================================================
   LumiHome — JavaScript global
   ============================================================ */

/* ── Point d'entrée de l'API ────────────────────────────── */
const API = '/Lumihome/api/actions.php';

/* ── Requête générique vers l'API ───────────────────────── */
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

document.addEventListener('DOMContentLoaded', () => {
  // Ferme la modal en cliquant sur le fond sombre
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) overlay.classList.remove('open');
    });
  });

  // Connexion avec la touche Entrée
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
