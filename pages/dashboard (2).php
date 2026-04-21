<?php
requireLogin();
try {
    $pdo = db();
    $uid = $_SESSION['user_id'];
    // Seulement les lumières de l'utilisateur connecté
    $lights = $pdo->prepare('SELECT l.*,p.nom AS piece_nom,p.emoji FROM lumieres l LEFT JOIN pieces p ON l.id_piece=p.id WHERE l.id_user=? ORDER BY p.id,l.id');
    $lights->execute([$uid]);
    $lights = $lights->fetchAll();
    $pieces = $pdo->query('SELECT * FROM pieces ORDER BY etage,id')->fetchAll();
    // Stats uniquement sur les lumières de l'utilisateur
    $st = $pdo->prepare('SELECT COUNT(*) AS total, SUM(etat="actif") AS on_count, COALESCE(SUM(conso_watt),0) AS conso FROM lumieres WHERE id_user=?');
    $st->execute([$uid]);
    $stats = $st->fetch();
    $conso_w = (float)$stats['conso'];
    $cout_h  = round($conso_w/1000*PRIX_KWH,4);
    $cout_j  = round($cout_h*24,3);
    $cout_m  = round($cout_j*30,2);
    $hist = $pdo->prepare('SELECT h.*,l.nom AS lnom FROM historique h LEFT JOIN lumieres l ON h.id_lumiere=l.id WHERE h.id_user=? ORDER BY h.timestamp DESC LIMIT 10');
    $hist->execute([$uid]);
    $history = $hist->fetchAll();
} catch(Exception $e) {
    $lights=[]; $pieces=[]; $stats=['total'=>0,'on_count'=>0,'conso'=>0]; $history=[];
    $conso_w=$cout_h=$cout_j=$cout_m=0;
}
$prenom_esc = escape($_SESSION['prenom']??'');
$greet = (int)date('H')<12?'Bonjour':'Bonsoir';
?>
<div class="page">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.75rem;flex-wrap:wrap;gap:1rem">
    <div>
      <h1 style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:.15rem"><?= $greet ?>, <?= $prenom_esc ?> 👋</h1>
      <p style="font-size:.85rem;color:var(--muted)">Gérez les lumières de votre maison</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('add-light-modal')">+ Ajouter une lumière</button>
  </div>

  <!-- STATS -->
  <div class="stats-row" id="stats-row">
    <div class="stat-card">
      <div class="stat-top"><span class="stat-icon">💡</span>
        <span style="font-size:.75rem;padding:.15rem .5rem;border-radius:4px;background:rgba(34,197,94,.1);color:var(--on)" id="s-on"><?= (int)$stats['on_count'] ?> actives</span>
      </div>
      <div class="stat-val" id="s-total"><?= (int)$stats['total'] ?></div>
      <div class="stat-lbl">Mes lumières</div>
    </div>
    <div class="stat-card">
      <div class="stat-top"><span class="stat-icon">⚡</span></div>
      <div class="stat-val" id="s-conso"><?= number_format($conso_w,1) ?><small style="font-size:1rem;font-weight:400"> W</small></div>
      <div class="stat-lbl">Consommation instantanée</div>
    </div>
    <div class="stat-card">
      <div class="stat-top"><span class="stat-icon">💰</span></div>
      <div class="stat-val" id="s-cout-j"><?= number_format($cout_j,3) ?><small style="font-size:1rem;font-weight:400"> €</small></div>
      <div class="stat-lbl">Coût estimé / jour</div>
      <div class="stat-sub" id="s-cout-detail"><?= number_format($cout_h,4) ?> €/h · <?= number_format($cout_m,2) ?> €/mois</div>
    </div>
    <div class="stat-card">
      <div class="stat-top"><span class="stat-icon">🏠</span></div>
      <div class="stat-val"><?= count($pieces) ?></div>
      <div class="stat-lbl">Pièces disponibles</div>
    </div>
  </div>

  <!-- FILTRES -->
  <div class="search-bar">
    <input class="form-input" id="f-text" placeholder="Rechercher…" oninput="renderLights()" autocomplete="off" autocorrect="off" autocapitalize="off">
    <select class="form-input" id="f-etat" onchange="renderLights()" style="max-width:150px">
      <option value="">Tous les états</option>
      <option value="actif">Allumées</option>
      <option value="inactif">Éteintes</option>
    </select>
    <select class="form-input" id="f-piece" onchange="renderLights()" style="max-width:190px">
      <option value="">Toutes les pièces</option>
      <?php foreach($pieces as $p): ?>
      <option value="<?= escape($p['nom']) ?>"><?= escape($p['emoji'].' '.$p['nom']) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="form-input" id="f-marque" onchange="renderLights()" style="max-width:160px">
      <option value="">Toutes les marques</option>
      <option value="Philips Hue">Philips Hue</option>
      <option value="IKEA">IKEA</option>
      <option value="Yeelight">Yeelight</option>
      <option value="Govee">Govee</option>
      <option value="BenQ">BenQ</option>
      <option value="Osram">Osram</option>
      <option value="Autre">Autre</option>
    </select>
  </div>

  <!-- GRILLE -->
  <div class="lights-grid" id="lights-grid"></div>

  <!-- HISTORIQUE -->
  <div style="margin-top:2.5rem">
    <div class="sec-header"><div class="sec-title">📋 Vos dernières actions</div></div>
    <div class="card" id="hist-list">
      <?php if(empty($history)): ?>
      <p style="font-size:.85rem;color:var(--muted);text-align:center;padding:1rem">Aucune action pour l'instant.</p>
      <?php else: foreach($history as $h): ?>
      <div class="hist-item">
        <div class="hist-icon"><?= $h['action']==='toggle'?'🔄':'✏️' ?></div>
        <div>
          <div class="hist-text"><?= $h['action']==='toggle'?'Interrupteur':'Modification' ?> — <strong><?= escape($h['lnom']??'?') ?></strong></div>
          <div class="hist-sub"><?= escape($h['val_avant']??'–') ?> → <?= escape($h['val_apres']??'–') ?> · <?= date('d/m H:i',strtotime($h['timestamp'])) ?></div>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<!-- MODAL AJOUTER LUMIÈRE -->
<div class="modal-overlay" id="add-light-modal">
<div class="modal">
  <h2>➕ Ajouter une lumière</h2>
  <p class="modal-sub">Enregistrez une nouvelle lumière connectée dans votre maison.</p>
  <div class="form-group"><label class="form-label">Nom de la lumière *</label>
    <input class="form-input" id="al-nom" placeholder="ex: Lustre Salon"></div>
  <div class="form-row">
    <div class="form-group"><label class="form-label">Marque *</label>
      <select class="form-input" id="al-marque">
        <option value="Philips Hue">Philips Hue</option>
        <option value="IKEA">IKEA</option>
        <option value="Yeelight">Yeelight</option>
        <option value="Govee">Govee</option>
        <option value="BenQ">BenQ</option>
        <option value="Osram">Osram</option>
        <option value="Müller Licht">Müller Licht</option>
        <option value="Autre">Autre</option>
      </select></div>
    <div class="form-group"><label class="form-label">Modèle</label>
      <input class="form-input" id="al-modele" placeholder="ex: Hue White A19"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label class="form-label">Pièce</label>
      <select class="form-input" id="al-piece">
        <option value="">— Aucune pièce —</option>
        <?php foreach($pieces as $p): ?>
        <option value="<?= $p['id'] ?>"><?= escape($p['emoji'].' '.$p['nom']) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="form-group"><label class="form-label">Connectivité</label>
      <select class="form-input" id="al-connect">
        <option value="Wi-Fi">Wi-Fi</option>
        <option value="Zigbee">Zigbee</option>
        <option value="Bluetooth">Bluetooth</option>
        <option value="Z-Wave">Z-Wave</option>
      </select></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label class="form-label">Puissance max (Watts) *</label>
      <input class="form-input" id="al-watt" type="number" min="1" max="200" value="9" placeholder="9"></div>
    <div class="form-group"><label class="form-label">Force du signal (%)</label>
      <input class="form-input" id="al-signal" type="number" min="0" max="100" value="90"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label class="form-label">Température couleur (K)</label>
      <input class="form-input" id="al-temp" type="number" min="2700" max="6500" value="4000" placeholder="2700-6500"></div>
    <div class="form-group"><label class="form-label">Couleur par défaut</label>
      <input class="form-input" id="al-couleur" type="color" value="#FFFFFF" style="height:42px;cursor:pointer"></div>
  </div>
  <div class="form-group"><label class="form-label">Description</label>
    <input class="form-input" id="al-desc" placeholder="ex: Lustre central du salon, 6 bras"></div>
  <div class="modal-footer">
    <button class="btn btn-outline" onclick="closeModal('add-light-modal')">Annuler</button>
    <button class="btn btn-primary" onclick="doAddLight()">Ajouter la lumière →</button>
  </div>
</div>
</div>

<script>
let lights = <?= json_encode($lights, JSON_UNESCAPED_UNICODE) ?>;
const PRIX_KWH = <?= PRIX_KWH ?>;

function costPerHour(w) { return (w/1000*PRIX_KWH).toFixed(4); }

function lightCard(l) {
  const on  = l.etat === 'actif';
  const lum = parseInt(l.luminosite)||0;
  const con = parseFloat(l.conso_watt)||0;
  return `
  <div class="light-card ${on?'on':''}" id="lc-${l.id}">
    <div class="top">
      <div class="light-icon ${on?'on':'off'}">💡</div>
      <span class="status-pill ${on?'pill-on':'pill-off'}" id="pill-${l.id}">${on?'● Allumée':'○ Éteinte'}</span>
    </div>
    <div class="light-name">${l.nom}</div>
    <div class="light-room">${l.emoji||''} ${l.piece_nom||'Sans pièce'} · ${l.marque||''}</div>
    <div class="light-meta">
      <div><div class="meta-lbl">Luminosité</div><div class="meta-val" id="lv-lum-${l.id}">${lum}%</div></div>
      <div><div class="meta-lbl">Conso.</div><div class="meta-val" id="lv-conso-${l.id}">${con} W</div></div>
      <div><div class="meta-lbl">Couleur</div>
        <div class="meta-val" style="display:flex;align-items:center;gap:.3rem">
          <span id="lv-sw-${l.id}" style="display:inline-block;width:13px;height:13px;border-radius:3px;background:${l.couleur_hex};border:1px solid rgba(255,255,255,.15)"></span>
          <span id="lv-hex-${l.id}">${l.couleur_hex}</span>
        </div>
      </div>
      <div><div class="meta-lbl">Réseau</div><div class="meta-val">${l.connectivite} ${l.signal_force}%</div></div>
    </div>
    <div class="lum-bar"><div class="lum-fill" id="lv-bar-${l.id}" style="width:${on?lum:0}%"></div></div>
    ${on?`
    <input type="range" class="lum-slider" min="0" max="100" value="${lum}" style="margin:.5rem 0"
      oninput="updateSlider(${l.id},this.value)"
      onchange="commitUpdate(${l.id},this.value,document.getElementById('col-${l.id}').value)">
    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem">
      <label style="font-size:.74rem;color:var(--muted)">Couleur</label>
      <input type="color" id="col-${l.id}" value="${l.couleur_hex}" style="width:32px;height:24px;border:none;border-radius:4px;cursor:pointer;padding:0"
        onchange="commitUpdate(${l.id},document.querySelector('#lc-${l.id} .lum-slider').value,this.value)">
    </div>`:''}
    <div class="light-footer">
      <span class="conso-badge" id="lv-badge-${l.id}">${on?con+' W · '+costPerHour(con)+' €/h':'Éteinte'}</span>
      <div style="display:flex;align-items:center;gap:.5rem">
        <button onclick="deleteLight(${l.id},'${l.nom.replace(/'/g,"\\\'")}')" style="background:none;border:none;cursor:pointer;color:var(--muted);font-size:.75rem;padding:.2rem .4rem" title="Supprimer">🗑️</button>
        <label class="toggle">
          <input type="checkbox" ${on?'checked':''} onchange="toggleLight(${l.id},this.checked,${l.puissance_max_watt})">
          <div class="toggle-track"></div><div class="toggle-thumb"></div>
        </label>
      </div>
    </div>
  </div>`;
}

function resetFiltres() {
  document.getElementById('f-text').value  = '';
  document.getElementById('f-etat').value  = '';
  document.getElementById('f-piece').value = '';
  document.getElementById('f-marque').value= '';
}

function renderLights() {
  const txt    = (document.getElementById('f-text').value  || '').toLowerCase().trim();
  const etat   = (document.getElementById('f-etat').value  || '').trim();
  const piece  = (document.getElementById('f-piece').value || '').trim();
  const marque = (document.getElementById('f-marque').value|| '').trim();

  const fil = lights.filter(l => {
    const nomOk    = !txt    || (l.nom||'').toLowerCase().includes(txt) || (l.piece_nom||'').toLowerCase().includes(txt) || (l.marque||'').toLowerCase().includes(txt);
    const etatOk   = !etat   || (l.etat||'') === etat;
    const pieceOk  = !piece  || (l.piece_nom||'').trim() === piece;
    const marqueOk = !marque || (l.marque||'').trim() === marque;
    return nomOk && etatOk && pieceOk && marqueOk;
  });

  const grid = document.getElementById('lights-grid');
  if (fil.length) {
    grid.innerHTML = fil.map(lightCard).join('');
  } else if (lights.length === 0) {
    grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--muted)">
      <div style="font-size:2.5rem;margin-bottom:.75rem">💡</div>
      <div style="font-weight:600;font-size:1rem;margin-bottom:.35rem">Aucune lumière enregistrée</div>
      <div style="font-size:.83rem">Cliquez sur <strong style="color:var(--accent)">+ Ajouter une lumière</strong> pour commencer.</div>
    </div>`;
  } else {
    grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--muted)">
      <div style="font-size:1.5rem;margin-bottom:.5rem">🔍</div>
      <div style="font-weight:600;margin-bottom:.25rem">Aucun résultat</div>
      <div style="font-size:.83rem">Modifiez vos filtres.</div>
      <button onclick="resetFiltres();renderLights();" class="btn btn-outline" style="margin-top:.75rem;font-size:.8rem">Réinitialiser les filtres</button>
    </div>`;
  }
}

function updateStatsUI(s) {
  if(!s) return;
  document.getElementById('s-total').textContent   = s.total;
  document.getElementById('s-on').textContent      = s.actives+' actives';
  document.getElementById('s-conso').innerHTML     = s.conso_w+'<small style="font-size:1rem;font-weight:400"> W</small>';
  document.getElementById('s-cout-j').innerHTML    = s.cout_jour+'<small style="font-size:1rem;font-weight:400"> €</small>';
  document.getElementById('s-cout-detail').textContent = s.cout_heure+' €/h · '+s.cout_mois+' €/mois';
}

async function toggleLight(id, on, maxW) {
  const etat  = on ? 'actif' : 'inactif';
  const conso = on ? parseFloat(maxW) : 0;
  const d = await api('toggle_light', {id, etat, conso});
  if (!d.ok) { toast(d.msg, false); renderLights(); return; }
  const l = lights.find(x=>x.id==id);
  if(l){ l.etat=etat; l.conso_watt=conso; if(on) l.luminosite=100; }
  resetFiltres();
  renderLights();
  updateStatsUI(d.stats);
  toast(on?'💡 Lumière allumée':'🌙 Lumière éteinte');
}

function updateSlider(id, val) {
  document.getElementById('lv-lum-'+id).textContent = val+'%';
  document.getElementById('lv-bar-'+id).style.width = val+'%';
}

async function commitUpdate(id, lum, couleur) {
  const d = await api('update_light', {id, luminosite:lum, couleur});
  if(!d.ok){ toast(d.msg,false); return; }
  const l = lights.find(x=>x.id==id);
  if(l){ l.luminosite=parseInt(lum); l.couleur_hex=couleur; l.conso_watt=parseFloat(d.conso)||0; }
  document.getElementById('lv-lum-'+id).textContent   = lum+'%';
  document.getElementById('lv-bar-'+id).style.width   = lum+'%';
  document.getElementById('lv-conso-'+id).textContent = d.conso+' W';
  document.getElementById('lv-badge-'+id).textContent = d.conso+' W · '+costPerHour(d.conso)+' €/h';
  document.getElementById('lv-sw-'+id).style.background = couleur;
  document.getElementById('lv-hex-'+id).textContent   = couleur;
  updateStatsUI(d.stats);
}

async function doAddLight() {
  const nom  = document.getElementById('al-nom').value.trim();
  const watt = document.getElementById('al-watt').value;
  if(!nom){ toast('Le nom est obligatoire.',false); return; }
  if(!watt||watt<1){ toast('La puissance est obligatoire.',false); return; }
  const d = await api('add_light',{
    nom, watt,
    marque:   document.getElementById('al-marque').value,
    modele:   document.getElementById('al-modele').value,
    piece:    document.getElementById('al-piece').value,
    connect:  document.getElementById('al-connect').value,
    signal:   document.getElementById('al-signal').value,
    temp:     document.getElementById('al-temp').value,
    couleur:  document.getElementById('al-couleur').value,
    desc:     document.getElementById('al-desc').value,
  });
  if(!d.ok){ toast(d.msg,false); return; }
  lights.push(d.light);
  closeModal('add-light-modal');
  renderLights();
  updateStatsUI(d.stats);
  toast('💡 Lumière ajoutée avec succès !');
  document.getElementById('al-nom').value='';
  document.getElementById('al-modele').value='';
  document.getElementById('al-desc').value='';
}

async function deleteLight(id, nom) {
  if(!confirm('Supprimer "'+nom+'" ?')) return;
  const d = await api('delete_light',{id});
  if(!d.ok){ toast(d.msg,false); return; }
  lights = lights.filter(l=>l.id!=id);
  renderLights();
  updateStatsUI(d.stats);
  toast('🗑️ Lumière supprimée.');
}

renderLights();
</script>
