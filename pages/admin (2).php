<?php
requireAdmin();
try {
    $pdo = db();
    $users        = $pdo->query('SELECT id,login,nom,prenom,email,role,est_banni,raison_ban,created_at FROM utilisateurs ORDER BY created_at DESC')->fetchAll();
    $nb_ban       = count(array_filter($users, fn($u)=>$u['est_banni']));
    $total_lights = $pdo->query('SELECT COUNT(*) FROM lumieres')->fetchColumn();
    $total_users  = count($users);
    $total_hist   = $pdo->query('SELECT COUNT(*) FROM historique')->fetchColumn();
    // Lumières groupées par utilisateur
    $all_lights = $pdo->query('
        SELECT l.*, p.nom AS piece_nom, p.emoji,
               u.login AS user_login, u.prenom AS user_prenom, u.nom AS user_nom
        FROM lumieres l
        LEFT JOIN pieces p ON l.id_piece = p.id
        LEFT JOIN utilisateurs u ON l.id_user = u.id
        ORDER BY u.login, l.id
    ')->fetchAll();
    // Grouper par user_login
    $lights_by_user = [];
    foreach($all_lights as $l) {
        $key = $l['user_login'] ?? 'inconnu';
        $lights_by_user[$key][] = $l;
    }
    $pieces = $pdo->query('SELECT * FROM pieces ORDER BY etage,id')->fetchAll();
} catch(Exception $e) {
    $users=[]; $nb_ban=0; $total_lights=0; $total_users=0; $total_hist=0; $lights_by_user=[]; $pieces=[];
}
?>

<div class="page">
  <!-- HEADER -->
  <div style="margin-bottom:1.75rem">
    <h1 style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:.15rem">⚙️ Panneau d'administration</h1>
    <p style="font-size:.85rem;color:var(--muted)">Gestion complète de la plateforme LumiHome</p>
  </div>

  <!-- STATS -->
  <div class="stats-row" style="margin-bottom:2rem">
    <div class="stat-card">
      <div class="stat-top"><span class="stat-icon">👥</span></div>
      <div class="stat-val"><?= $total_users ?></div>
      <div class="stat-lbl">Utilisateurs inscrits</div>
    </div>
    <div class="stat-card">
      <div class="stat-top"><span class="stat-icon">🚫</span></div>
      <div class="stat-val" style="color:var(--off)"><?= $nb_ban ?></div>
      <div class="stat-lbl">Comptes bannis</div>
    </div>
    <div class="stat-card">
      <div class="stat-top"><span class="stat-icon">💡</span></div>
      <div class="stat-val"><?= $total_lights ?></div>
      <div class="stat-lbl">Lumières totales</div>
    </div>
    <div class="stat-card">
      <div class="stat-top"><span class="stat-icon">📋</span></div>
      <div class="stat-val"><?= $total_hist ?></div>
      <div class="stat-lbl">Actions journalisées</div>
    </div>
  </div>

  <!-- ONGLETS -->
  <div style="display:flex;gap:.5rem;margin-bottom:1.5rem;border-bottom:1px solid var(--border);padding-bottom:0">
    <button class="tab-btn active" onclick="switchTab('tab-users')" id="tbtn-users" style="padding:.6rem 1.25rem;background:none;border:none;border-bottom:2px solid var(--accent);color:var(--text);font-family:inherit;font-size:.9rem;font-weight:600;cursor:pointer">👥 Utilisateurs</button>
    <button class="tab-btn" onclick="switchTab('tab-lights')" id="tbtn-lights" style="padding:.6rem 1.25rem;background:none;border:none;border-bottom:2px solid transparent;color:var(--muted);font-family:inherit;font-size:.9rem;font-weight:500;cursor:pointer">💡 Lumières</button>
  </div>

  <!-- ONGLET UTILISATEURS -->
  <div id="tab-users">
    <div class="card" style="padding:0;overflow:hidden">
      <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem">
        <div class="sec-title">👥 Gestion des utilisateurs</div>
        <input class="form-input" id="admin-search" placeholder="Filtrer par login, nom, email…" oninput="filterAdmin()" autocomplete="off" style="max-width:260px;padding:.45rem .85rem;font-size:.83rem">
      </div>
      <div style="overflow-x:auto">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Login</th><th>Nom complet</th><th>Email</th>
              <th>Rôle</th><th>Statut</th><th>Inscrit le</th><th>Actions</th>
            </tr>
          </thead>
          <tbody id="admin-tbody">
            <?php foreach($users as $u): ?>
            <tr id="urow-<?= $u['id'] ?>" data-txt="<?= escape(strtolower($u['login'].' '.$u['nom'].' '.$u['prenom'].' '.$u['email'])) ?>">
              <td><strong><?= escape($u['login']) ?></strong></td>
              <td><?= escape($u['prenom'].' '.$u['nom']) ?></td>
              <td style="color:var(--muted);font-size:.8rem"><?= escape($u['email']) ?></td>
              <td>
                <?php if($u['id'] != $_SESSION['user_id']): ?>
                <select onchange="changeRole(<?= $u['id'] ?>,this.value)" style="background:var(--surface2);border:1px solid var(--border);color:var(--text);border-radius:6px;padding:.2rem .4rem;font-size:.78rem;cursor:pointer">
                  <option value="membre"  <?= $u['role']==='membre' ?'selected':'' ?>>Membre</option>
                  <option value="admin"   <?= $u['role']==='admin'  ?'selected':'' ?>>Admin</option>
                </select>
                <?php else: ?>
                <span class="badge-admin-t">Admin (vous)</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if($u['est_banni']): ?>
                <span class="badge-banned" title="<?= escape($u['raison_ban']??'') ?>">🚫 Banni</span>
                <?php else: ?>
                <span class="badge-ok">✓ Actif</span>
                <?php endif; ?>
              </td>
              <td style="color:var(--muted);font-size:.8rem"><?= date('d/m/Y',strtotime($u['created_at'])) ?></td>
              <td>
                <?php if($u['id'] != $_SESSION['user_id']): ?>
                <div style="display:flex;gap:.35rem;flex-wrap:wrap;align-items:center">
                  <button class="btn btn-outline btn-sm" onclick="viewUserLights(<?= $u['id'] ?>,'<?= escape($u['login']) ?>')" title="Voir les lumières">💡</button>
                  <?php if(!$u['est_banni']): ?>
                  <button class="btn btn-danger btn-sm" onclick="openBan(<?= $u['id'] ?>,'<?= escape($u['login']) ?>')">Bannir</button>
                  <?php else: ?>
                  <button class="btn btn-outline btn-sm" onclick="doUnban(<?= $u['id'] ?>)">Débannir</button>
                  <?php endif; ?>
                  <button class="btn btn-sm" style="background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3)" onclick="doDelete(<?= $u['id'] ?>,'<?= escape($u['login']) ?>')">Supprimer</button>
                </div>
                <?php else: ?>
                <span style="font-size:.75rem;color:var(--muted)">(vous)</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ONGLET LUMIÈRES -->
  <div id="tab-lights" style="display:none">
    <?php if(empty($lights_by_user)): ?>
    <div class="card" style="text-align:center;padding:3rem;color:var(--muted)">
      <div style="font-size:2rem;margin-bottom:.75rem">💡</div>
      <div>Aucune lumière enregistrée sur la plateforme.</div>
    </div>
    <?php else: ?>
    <?php foreach($lights_by_user as $login => $lamps): ?>
    <?php $u = array_values(array_filter($users, fn($x)=>$x['login']===$login))[0] ?? null; ?>
    <div class="card" style="margin-bottom:1.25rem;padding:0;overflow:hidden">
      <!-- Header utilisateur -->
      <div style="padding:1rem 1.5rem;background:var(--surface2);border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
        <div style="display:flex;align-items:center;gap:.75rem">
          <div style="width:36px;height:36px;border-radius:50%;background:rgba(245,200,66,.15);border:1px solid rgba(245,200,66,.3);display:flex;align-items:center;justify-content:center;font-size:1rem">👤</div>
          <div>
            <div style="font-weight:600;font-size:.95rem"><?= escape($login) ?> <?= $u?'<span style="font-size:.78rem;color:var(--muted)">('.escape($u['prenom'].' '.$u['nom']).')</span>':'' ?></div>
            <div style="font-size:.75rem;color:var(--muted)"><?= count($lamps) ?> lumière<?= count($lamps)>1?'s':'' ?></div>
          </div>
        </div>
        <?php if($u && $u['est_banni']): ?>
        <span class="badge-banned">🚫 Compte banni</span>
        <?php endif; ?>
      </div>
      <!-- Lumières de l'utilisateur -->
      <div style="padding:1rem 1.5rem">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:.75rem">
          <?php foreach($lamps as $l): ?>
          <div style="background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:1rem;<?= $l['etat']==='actif'?'border-top:2px solid var(--accent)':'' ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.6rem">
              <div>
                <div style="font-weight:600;font-size:.9rem"><?= escape($l['nom']) ?></div>
                <div style="font-size:.75rem;color:var(--muted)"><?= escape($l['emoji']??'') ?> <?= escape($l['piece_nom']??'Sans pièce') ?> · <?= escape($l['marque']??'') ?></div>
              </div>
              <span style="font-size:.7rem;font-weight:600;padding:.15rem .45rem;border-radius:20px;<?= $l['etat']==='actif'?'background:rgba(34,197,94,.15);color:var(--on)':'background:rgba(239,68,68,.1);color:var(--off)' ?>">
                <?= $l['etat']==='actif'?'● ON':'○ OFF' ?>
              </span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.3rem .6rem;font-size:.78rem;margin-bottom:.75rem">
              <div><span style="color:var(--muted)">Luminosité</span><br><strong><?= $l['luminosite'] ?>%</strong></div>
              <div><span style="color:var(--muted)">Conso.</span><br><strong><?= $l['conso_watt'] ?> W</strong></div>
              <div><span style="color:var(--muted)">Réseau</span><br><strong><?= escape($l['connectivite']) ?></strong></div>
              <div><span style="color:var(--muted)">Couleur</span><br>
                <span style="display:inline-flex;align-items:center;gap:.3rem">
                  <span style="width:11px;height:11px;border-radius:3px;background:<?= escape($l['couleur_hex']) ?>;border:1px solid rgba(255,255,255,.2);display:inline-block"></span>
                  <strong><?= escape($l['couleur_hex']) ?></strong>
                </span>
              </div>
            </div>
            <!-- Actions admin sur cette lumière -->
            <div style="display:flex;gap:.4rem;border-top:1px solid var(--border);padding-top:.65rem">
              <button class="btn btn-outline btn-sm" onclick="adminEditLight(<?= $l['id'] ?>,<?= $l['luminosite'] ?>,'<?= escape($l['couleur_hex']) ?>','<?= escape($l['nom']) ?>')" style="flex:1">✏️ Modifier</button>
              <button class="btn btn-sm" style="background:rgba(239,68,68,.12);color:#f87171;border:1px solid rgba(239,68,68,.25)" onclick="adminDeleteLight(<?= $l['id'] ?>,'<?= escape($l['nom']) ?>')">🗑️</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- MODAL MODIFIER LUMIÈRE (admin) -->
<div class="modal-overlay" id="admin-edit-modal">
<div class="modal">
  <h2>✏️ Modifier la lumière</h2>
  <p class="modal-sub" id="admin-edit-sub">Modification admin</p>
  <input type="hidden" id="ae-id">
  <div class="form-group"><label class="form-label">Luminosité (%)</label>
    <input class="form-input" id="ae-lum" type="number" min="0" max="100">
  </div>
  <div class="form-group"><label class="form-label">Couleur</label>
    <input class="form-input" id="ae-col" type="color" style="height:42px;cursor:pointer">
  </div>
  <div class="modal-footer">
    <button class="btn btn-outline" onclick="closeModal('admin-edit-modal')">Annuler</button>
    <button class="btn btn-primary" onclick="adminSaveLight()">Enregistrer</button>
  </div>
</div>
</div>

<!-- MODAL LUMIÈRES D'UN USER -->
<div class="modal-overlay" id="user-lights-modal">
<div class="modal" style="max-width:600px">
  <h2>💡 Lumières de <span id="ul-login"></span></h2>
  <p class="modal-sub" id="ul-sub"></p>
  <div id="ul-content" style="max-height:400px;overflow-y:auto"></div>
  <div class="modal-footer">
    <button class="btn btn-outline" onclick="closeModal('user-lights-modal')">Fermer</button>
  </div>
</div>
</div>

<script>
const PIECES = <?= json_encode($pieces, JSON_UNESCAPED_UNICODE) ?>;

// ---- Onglets ----
function switchTab(tab) {
  document.getElementById('tab-users').style.display  = tab==='tab-users'  ? '' : 'none';
  document.getElementById('tab-lights').style.display = tab==='tab-lights' ? '' : 'none';
  document.getElementById('tbtn-users').style.borderBottomColor  = tab==='tab-users'  ? 'var(--accent)' : 'transparent';
  document.getElementById('tbtn-lights').style.borderBottomColor = tab==='tab-lights' ? 'var(--accent)' : 'transparent';
  document.getElementById('tbtn-users').style.color  = tab==='tab-users'  ? 'var(--text)' : 'var(--muted)';
  document.getElementById('tbtn-lights').style.color = tab==='tab-lights' ? 'var(--text)' : 'var(--muted)';
  document.getElementById('tbtn-users').style.fontWeight  = tab==='tab-users'  ? '600' : '500';
  document.getElementById('tbtn-lights').style.fontWeight = tab==='tab-lights' ? '600' : '500';
}

// ---- Filtre utilisateurs ----
function filterAdmin() {
  const q = document.getElementById('admin-search').value.toLowerCase();
  document.querySelectorAll('#admin-tbody tr').forEach(r => {
    r.style.display = r.dataset.txt.includes(q) ? '' : 'none';
  });
}

// ---- Changer rôle ----
async function changeRole(uid, role) {
  const d = await api('change_role', {uid, role});
  toast(d.msg, d.ok);
  if(d.ok) setTimeout(()=>location.reload(), 800);
}

// ---- Voir lumières d'un user (raccourci depuis tableau) ----
async function viewUserLights(uid, login) {
  document.getElementById('ul-login').textContent = login;
  const d = await api('admin_user_lights', {uid});
  if(!d.ok){ toast(d.msg,false); return; }
  const lights = d.lights;
  document.getElementById('ul-sub').textContent = lights.length + ' lumière(s) enregistrée(s)';
  if(!lights.length){
    document.getElementById('ul-content').innerHTML = '<p style="color:var(--muted);text-align:center;padding:1.5rem">Cet utilisateur n\'a aucune lumière.</p>';
  } else {
    document.getElementById('ul-content').innerHTML = lights.map(l=>`
      <div style="display:flex;justify-content:space-between;align-items:center;padding:.65rem 0;border-bottom:1px solid var(--border)">
        <div>
          <div style="font-weight:600;font-size:.88rem">${l.nom}</div>
          <div style="font-size:.75rem;color:var(--muted)">${l.piece_nom||'Sans pièce'} · ${l.marque||''} · ${l.connectivite}</div>
        </div>
        <div style="display:flex;align-items:center;gap:.5rem">
          <span style="font-size:.7rem;padding:.15rem .4rem;border-radius:20px;${l.etat==='actif'?'background:rgba(34,197,94,.15);color:#22c55e':'background:rgba(239,68,68,.1);color:#ef4444'}">${l.etat==='actif'?'ON':'OFF'}</span>
          <button class="btn btn-sm" style="background:rgba(239,68,68,.12);color:#f87171;border:1px solid rgba(239,68,68,.25);padding:.2rem .5rem" onclick="adminDeleteLight(${l.id},'${l.nom.replace(/'/g,"\\'")}')" >🗑️</button>
        </div>
      </div>`).join('');
  }
  openModal('user-lights-modal');
}

// ---- Modifier lumière (admin) ----
function adminEditLight(id, lum, col, nom) {
  document.getElementById('ae-id').value  = id;
  document.getElementById('ae-lum').value = lum;
  document.getElementById('ae-col').value = col;
  document.getElementById('admin-edit-sub').textContent = 'Lumière : ' + nom;
  openModal('admin-edit-modal');
}

async function adminSaveLight() {
  const id  = document.getElementById('ae-id').value;
  const lum = document.getElementById('ae-lum').value;
  const col = document.getElementById('ae-col').value;
  const d = await api('admin_update_light', {id, luminosite:lum, couleur:col});
  toast(d.msg||( d.ok?'Modifié !':'Erreur'), d.ok);
  if(d.ok){ closeModal('admin-edit-modal'); setTimeout(()=>location.reload(),600); }
}

// ---- Supprimer lumière (admin) ----
async function adminDeleteLight(id, nom) {
  if(!confirm('Supprimer "'+nom+'" définitivement ?')) return;
  const d = await api('admin_delete_light', {id});
  toast(d.msg||(d.ok?'Supprimée !':'Erreur'), d.ok);
  if(d.ok) setTimeout(()=>location.reload(),600);
}

// ---- Supprimer utilisateur ----
async function doDelete(uid, login) {
  if(!confirm('Supprimer définitivement le compte "'+login+'" et toutes ses lumières ?')) return;
  const d = await api('delete_user',{uid});
  toast(d.msg, d.ok);
  if(d.ok) setTimeout(()=>location.reload(),800);
}

// ---- Débannir ----
async function doUnban(uid) {
  const d = await api('ban_user',{uid,ban:0});
  toast(d.msg, d.ok);
  if(d.ok) setTimeout(()=>location.reload(),600);
}
</script>
