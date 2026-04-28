<?php
requireLogin();

// ============================================================
// Config niveaux côté PHP (miroir de actions.php)
// ============================================================
$NIVEAUX_CFG = [
    'debutant'      => ['limite' => 3,  'intensite' => false, 'couleur' => false,
                        'label' => 'Débutant',      'emoji' => '🌱', 'color' => '#7a8299'],
    'intermediaire' => ['limite' => 5,  'intensite' => true,  'couleur' => false,
                        'label' => 'Intermédiaire', 'emoji' => '⚡', 'color' => '#3b82f6'],
    'avance'        => ['limite' => 8,  'intensite' => true,  'couleur' => true,
                        'label' => 'Avancé',        'emoji' => '🌟', 'color' => '#f5c842'],
    'expert'        => ['limite' => 99, 'intensite' => true,  'couleur' => true,
                        'label' => 'Expert',        'emoji' => '👑', 'color' => '#f97316'],
];

$niveau    = $_SESSION['niveau'] ?? 'debutant';
$droits    = $NIVEAUX_CFG[$niveau] ?? $NIVEAUX_CFG['debutant'];

try {
    $pdo = db();
    $uid = (int)$_SESSION['user_id'];

    $stL = $pdo->prepare('SELECT l.*,p.nom AS piece_nom,p.emoji
                           FROM lumieres l LEFT JOIN pieces p ON l.id_piece=p.id
                           WHERE l.id_user=? ORDER BY p.id,l.id');
    $stL->execute([$uid]);
    $lights = $stL->fetchAll();

    $pieces = $pdo->query('SELECT * FROM pieces ORDER BY etage,id')->fetchAll();

    $stS = $pdo->prepare('SELECT COUNT(*) AS total, SUM(etat="actif") AS on_count,
                                  COALESCE(SUM(conso_watt),0) AS conso
                           FROM lumieres WHERE id_user=?');
    $stS->execute([$uid]);
    $stats   = $stS->fetch();
    $conso_w = (float)$stats['conso'];
    $cout_h  = round($conso_w / 1000 * PRIX_KWH, 4);
    $cout_j  = round($cout_h * 24, 3);
    $cout_m  = round($cout_j * 30, 2);

    $stH = $pdo->prepare('SELECT h.*,l.nom AS lnom FROM historique h
                           LEFT JOIN lumieres l ON h.id_lumiere=l.id
                           WHERE h.id_user=? ORDER BY h.timestamp DESC LIMIT 10');
    $stH->execute([$uid]);
    $history = $stH->fetchAll();

    // Niveau & progression
    $stN = $pdo->prepare('SELECT u.points, u.niveau, u.pts_connexion, u.pts_actions,
                                  nc.couleur_hex, nc.emoji AS niv_emoji, nc.libelle AS niv_libelle
                           FROM utilisateurs u
                           LEFT JOIN niveaux_config nc ON nc.niveau = u.niveau
                           WHERE u.id = ?');
    $stN->execute([$uid]);
    $niv_user = $stN->fetch();

    $stPN = $pdo->prepare('SELECT * FROM niveaux_config WHERE pts_requis > ? ORDER BY pts_requis ASC LIMIT 1');
    $stPN->execute([$niv_user['points'] ?? 0]);
    $prochain_niv = $stPN->fetch();

    $nb_lumieres = count($lights);

} catch(Exception $e) {
    $lights = []; $pieces = []; $history = [];
    $stats  = ['total'=>0,'on_count'=>0,'conso'=>0];
    $conso_w = $cout_h = $cout_j = $cout_m = 0;
    $niv_user = null; $prochain_niv = null; $nb_lumieres = 0;
}

$prenom_esc  = escape($_SESSION['prenom'] ?? '');
$greet       = (int)date('H') < 12 ? 'Bonjour' : 'Bonsoir';
$peut_ajouter = $nb_lumieres < $droits['limite'];
?>

<div class="page">

  <!-- EN-TÊTE ────────────────────────────────────────────── -->
  <div style="display:flex;justify-content:space-between;align-items:flex-start;
              margin-bottom:1.75rem;flex-wrap:wrap;gap:1rem">
    <div>
      <h1 style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:.15rem">
        <?= $greet ?>, <?= $prenom_esc ?> <?= $droits['emoji'] ?>
      </h1>
      <p style="font-size:.85rem;color:var(--muted)">
        Niveau <strong style="color:<?= escape($droits['color']) ?>"><?= escape($droits['label']) ?></strong>
        — <?= $nb_lumieres ?>/<?= $droits['limite'] == 99 ? '∞' : $droits['limite'] ?> lumières
      </p>
    </div>
    <?php if ($peut_ajouter): ?>
    <button class="btn btn-primary" onclick="openModal('add-light-modal')">+ Ajouter une lumière</button>
    <?php else: ?>
    <button class="btn btn-outline" style="cursor:not-allowed;opacity:.6"
            onclick="toast('🔒 Limite de <?= $droits['limite'] ?> lumières atteinte. Montez de niveau pour en ajouter !', false)"
            title="Limite atteinte pour ce niveau">
      🔒 Limite atteinte (<?= $droits['limite'] ?>)
    </button>
    <?php endif; ?>
  </div>

  <!-- STATS ──────────────────────────────────────────────── -->
  <div class="stats-row" id="stats-row">
    <div class="stat-card">
      <div class="stat-top"><span class="stat-icon">💡</span>
        <span style="font-size:.75rem;padding:.15rem .5rem;border-radius:4px;
                     background:rgba(34,197,94,.1);color:var(--on)" id="s-on">
          <?= (int)$stats['on_count'] ?> actives
        </span>
      </div>
      <div class="stat-val" id="s-total"><?= (int)$stats['total'] ?></div>
      <div class="stat-lbl">Mes lumières</div>
      <div class="stat-sub" id="s-limite">
        <?= $nb_lumieres ?>/<?= $droits['limite'] == 99 ? '∞' : $droits['limite'] ?> — niveau <?= escape($droits['label']) ?>
      </div>
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

  <!-- WIDGET NIVEAU ──────────────────────────────────────── -->
  <?php if ($niv_user):
    $pts = (float)($niv_user['points'] ?? 0);
    $col = $niv_user['couleur_hex'] ?? '#f5c842';
    $seuils = ['debutant'=>0,'intermediaire'=>3,'avance'=>5,'expert'=>20];
    $seuil_actuel = $seuils[$niv_user['niveau']] ?? 0;
    $seuil_suivant = $prochain_niv ? (float)$prochain_niv['pts_requis'] : $pts;
    $range = $prochain_niv ? ($seuil_suivant - $seuil_actuel) : 1;
    $prog  = $prochain_niv ? min(100, round(($pts - $seuil_actuel) / $range * 100)) : 100;
  ?>
  <div class="card" style="margin-bottom:1.5rem;padding:1rem 1.5rem;
       display:flex;align-items:center;justify-content:space-between;gap:1.5rem;flex-wrap:wrap">
    <div style="display:flex;align-items:center;gap:.85rem">
      <div style="font-size:1.75rem"><?= $niv_user['niv_emoji'] ?? '🌱' ?></div>
      <div>
        <div style="font-size:.72rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.4px">Votre niveau</div>
        <div style="font-family:'Syne',sans-serif;font-weight:800;color:<?= escape($col) ?>;font-size:1.1rem">
          <?= escape($niv_user['niv_libelle'] ?? ucfirst($niv_user['niveau'])) ?>
        </div>
      </div>
    </div>
    <div style="flex:1;min-width:180px">
      <?php if ($prochain_niv): ?>
      <div style="display:flex;justify-content:space-between;font-size:.73rem;color:var(--muted);margin-bottom:.35rem">
        <span><?= round($pts,2) ?> pts</span>
        <span>→ <?= escape($prochain_niv['emoji'].' '.$prochain_niv['libelle']) ?> (<?= $prochain_niv['pts_requis'] ?> pts)</span>
      </div>
      <div style="height:7px;background:var(--surface2);border-radius:4px;overflow:hidden">
        <div style="height:100%;width:<?= $prog ?>%;background:<?= escape($col) ?>;border-radius:4px;transition:width 1s"></div>
      </div>
      <?php else: ?>
      <div style="font-size:.8rem;color:#f97316">👑 Niveau maximum atteint !</div>
      <?php endif; ?>
    </div>
    <a href="/Lumihome/index.php?page=niveaux" class="btn btn-outline btn-sm"
       style="white-space:nowrap;border-color:<?= escape($col) ?>;color:<?= escape($col) ?>">
      Voir ma progression →
    </a>
  </div>
  <?php endif; ?>

  <!-- FONCTIONNALITÉS DÉBLOQUÉES ─────────────────────────── -->
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem">
    <span style="font-size:.75rem;padding:.25rem .65rem;border-radius:20px;
                 background:rgba(34,197,94,.1);color:#22c55e;border:1px solid rgba(34,197,94,.2)">
      ✓ Allumer / Éteindre
    </span>
    <?php if ($droits['intensite']): ?>
    <span style="font-size:.75rem;padding:.25rem .65rem;border-radius:20px;
                 background:rgba(59,130,246,.1);color:#3b82f6;border:1px solid rgba(59,130,246,.2)">
      ✓ Réglage intensité
    </span>
    <?php else: ?>
    <span style="font-size:.75rem;padding:.25rem .65rem;border-radius:20px;
                 background:var(--surface2);color:var(--muted);border:1px solid var(--border)"
          title="Débloqué au niveau Intermédiaire (3 pts)">
      🔒 Intensité — niveau Intermédiaire
    </span>
    <?php endif; ?>
    <?php if ($droits['couleur']): ?>
    <span style="font-size:.75rem;padding:.25rem .65rem;border-radius:20px;
                 background:rgba(245,200,66,.1);color:var(--accent);border:1px solid rgba(245,200,66,.2)">
      ✓ Changement couleur
    </span>
    <?php else: ?>
    <span style="font-size:.75rem;padding:.25rem .65rem;border-radius:20px;
                 background:var(--surface2);color:var(--muted);border:1px solid var(--border)"
          title="Débloqué au niveau Avancé (5 pts)">
      🔒 Couleur — niveau Avancé
    </span>
    <?php endif; ?>
  </div>

  <!-- FILTRES ─────────────────────────────────────────────── -->
  <div class="search-bar">
    <input class="form-input" id="f-text" placeholder="Rechercher…"
           oninput="renderLights()" autocomplete="off" autocorrect="off" autocapitalize="off">
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
      <option>Philips Hue</option><option>IKEA</option><option>Yeelight</option>
      <option>Govee</option><option>BenQ</option><option>Osram</option><option>Autre</option>
    </select>
  </div>

  <!-- GRILLE ──────────────────────────────────────────────── -->
  <div class="lights-grid" id="lights-grid"></div>

  <!-- HISTORIQUE ──────────────────────────────────────────── -->
  <div style="margin-top:2.5rem">
    <div class="sec-header"><div class="sec-title">📋 Vos dernières actions</div></div>
    <div class="card" id="hist-list">
      <?php if(empty($history)): ?>
      <p style="font-size:.85rem;color:var(--muted);text-align:center;padding:1rem">Aucune action pour l'instant.</p>
      <?php else: foreach($history as $h): ?>
      <div class="hist-item">
        <div class="hist-icon"><?= in_array($h['action'],['toggle','intensite','couleur'])?'💡':'✏️' ?></div>
        <div>
          <div class="hist-text"><?= escape(ucfirst($h['action'])) ?> — <strong><?= escape($h['lnom']??'?') ?></strong></div>
          <div class="hist-sub"><?= escape($h['val_avant']??'–') ?> → <?= escape($h['val_apres']??'–') ?> · <?= date('d/m H:i',strtotime($h['timestamp'])) ?></div>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<!-- MODAL AJOUTER LUMIÈRE ───────────────────────────────── -->
<div class="modal-overlay" id="add-light-modal">
<div class="modal">
  <h2>➕ Ajouter une lumière</h2>
  <p class="modal-sub">Enregistrez une nouvelle lumière connectée.</p>
  <div class="form-group"><label class="form-label">Nom *</label>
    <input class="form-input" id="al-nom" placeholder="ex: Lustre Salon"></div>
  <div class="form-row">
    <div class="form-group"><label class="form-label">Marque *</label>
      <select class="form-input" id="al-marque">
        <option>Philips Hue</option><option>IKEA</option><option>Yeelight</option>
        <option>Govee</option><option>BenQ</option><option>Osram</option>
        <option>Müller Licht</option><option>Autre</option>
      </select></div>
    <div class="form-group"><label class="form-label">Modèle</label>
      <input class="form-input" id="al-modele" placeholder="ex: Hue White A19"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label class="form-label">Pièce</label>
      <select class="form-input" id="al-piece">
        <option value="">— Aucune —</option>
        <?php foreach($pieces as $p): ?>
        <option value="<?= $p['id'] ?>"><?= escape($p['emoji'].' '.$p['nom']) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="form-group"><label class="form-label">Connectivité</label>
      <select class="form-input" id="al-connect">
        <option>Wi-Fi</option><option>Zigbee</option>
        <option>Bluetooth</option><option>Z-Wave</option>
      </select></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label class="form-label">Puissance max (W) *</label>
      <input class="form-input" id="al-watt" type="number" min="1" max="200" value="9"></div>
    <div class="form-group"><label class="form-label">Signal (%)</label>
      <input class="form-input" id="al-signal" type="number" min="0" max="100" value="90"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label class="form-label">Température couleur (K)</label>
      <input class="form-input" id="al-temp" type="number" min="2700" max="6500" value="4000"></div>
    <div class="form-group"><label class="form-label">Couleur par défaut</label>
      <input class="form-input" id="al-couleur" type="color" value="#FFFFFF" style="height:42px;cursor:pointer"></div>
  </div>
  <div class="form-group"><label class="form-label">Description</label>
    <input class="form-input" id="al-desc" placeholder="ex: Lustre central du salon"></div>
  <div class="modal-footer">
    <button class="btn btn-outline" onclick="closeModal('add-light-modal')">Annuler</button>
    <button class="btn btn-primary" onclick="doAddLight()">Ajouter →</button>
  </div>
</div>
</div>

<script>
// ============================================================
// Données injectées depuis PHP
// ============================================================
let lights       = <?= json_encode($lights, JSON_UNESCAPED_UNICODE) ?>;
let nbLumieres   = <?= $nb_lumieres ?>;
const PRIX_KWH   = <?= PRIX_KWH ?>;

// Droits du niveau courant — contrôlent l'UI
const DROITS = {
    niveau       : <?= json_encode($niveau) ?>,
    intensite    : <?= $droits['intensite'] ? 'true' : 'false' ?>,
    couleur      : <?= $droits['couleur']   ? 'true' : 'false' ?>,
    limite       : <?= $droits['limite'] ?>,
    peutAjouter  : <?= $peut_ajouter ? 'true' : 'false' ?>
};

function costPerHour(w) { return (w / 1000 * PRIX_KWH).toFixed(4); }

// ============================================================
// Rendu d'une carte lumière selon les droits du niveau
// ============================================================
function lightCard(l) {
    const on  = l.etat === 'actif';
    const lum = parseInt(l.luminosite) || 0;
    const con = parseFloat(l.conso_watt) || 0;

    // ── Slider intensité (Intermédiaire+) ──
    const sliderHTML = (on && DROITS.intensite) ? `
    <div style="margin:.5rem 0 .25rem">
      <div style="display:flex;justify-content:space-between;font-size:.72rem;color:var(--muted);margin-bottom:.2rem">
        <span>Intensité</span><span id="lv-lum-txt-${l.id}">${lum}%</span>
      </div>
      <input type="range" class="lum-slider" min="1" max="100" value="${lum}"
        oninput="previewIntensity(${l.id},this.value)"
        onchange="commitIntensity(${l.id},this.value)">
    </div>` :
    (on && !DROITS.intensite) ? `
    <div style="margin:.4rem 0;padding:.4rem .6rem;background:var(--surface2);
                border-radius:6px;font-size:.73rem;color:var(--muted);
                border:1px dashed var(--border);cursor:pointer"
         onclick="toast('🔒 Réglage d\'intensité disponible au niveau Intermédiaire (3 pts).', false)">
      🔒 Intensité — niveau Intermédiaire
    </div>` : '';

    // ── Sélecteur couleur (Avancé+) ──
    const couleurHTML = (on && DROITS.couleur) ? `
    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem">
      <label style="font-size:.74rem;color:var(--muted)">Couleur</label>
      <input type="color" id="col-${l.id}" value="${l.couleur_hex}"
             style="width:32px;height:24px;border:none;border-radius:4px;cursor:pointer;padding:0"
             onchange="commitCouleur(${l.id},this.value)">
      <span style="font-size:.72rem;color:var(--muted)" id="lv-hex-${l.id}">${l.couleur_hex}</span>
    </div>` :
    (on && DROITS.intensite && !DROITS.couleur) ? `
    <div style="margin:.3rem 0 .4rem;padding:.4rem .6rem;background:var(--surface2);
                border-radius:6px;font-size:.73rem;color:var(--muted);
                border:1px dashed var(--border);cursor:pointer"
         onclick="toast('🔒 Changement de couleur disponible au niveau Avancé (5 pts).', false)">
      🔒 Couleur — niveau Avancé
    </div>` : '';

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
          <span id="lv-sw-${l.id}" style="display:inline-block;width:13px;height:13px;
                border-radius:3px;background:${l.couleur_hex};border:1px solid rgba(255,255,255,.15)"></span>
          <span id="lv-hex-${l.id}">${l.couleur_hex}</span>
        </div>
      </div>
      <div><div class="meta-lbl">Réseau</div><div class="meta-val">${l.connectivite} ${l.signal_force}%</div></div>
    </div>
    <div class="lum-bar"><div class="lum-fill" id="lv-bar-${l.id}" style="width:${on?lum:0}%"></div></div>
    ${sliderHTML}
    ${couleurHTML}
    <div class="light-footer">
      <span class="conso-badge" id="lv-badge-${l.id}">${on ? con+' W · '+costPerHour(con)+' €/h' : 'Éteinte'}</span>
      <div style="display:flex;align-items:center;gap:.5rem">
        <button onclick="deleteLight(${l.id},'${l.nom.replace(/'/g,"\\'")}')"
                style="background:none;border:none;cursor:pointer;color:var(--muted);font-size:.75rem;padding:.2rem .4rem"
                title="Supprimer">🗑️</button>
        <label class="toggle">
          <input type="checkbox" ${on?'checked':''} onchange="toggleLight(${l.id},this.checked,${l.puissance_max_watt})">
          <div class="toggle-track"></div><div class="toggle-thumb"></div>
        </label>
      </div>
    </div>
  </div>`;
}

// ============================================================
// Filtres & rendu
// ============================================================
function resetFiltres() {
    ['f-text','f-etat','f-piece','f-marque'].forEach(id => {
        document.getElementById(id).value = '';
    });
}

function renderLights() {
    const txt    = (document.getElementById('f-text').value   || '').toLowerCase().trim();
    const etat   = (document.getElementById('f-etat').value   || '').trim();
    const piece  = (document.getElementById('f-piece').value  || '').trim();
    const marque = (document.getElementById('f-marque').value || '').trim();

    const fil = lights.filter(l => {
        const nomOk    = !txt    || (l.nom||'').toLowerCase().includes(txt)
                                 || (l.piece_nom||'').toLowerCase().includes(txt)
                                 || (l.marque||'').toLowerCase().includes(txt);
        const etatOk   = !etat   || l.etat === etat;
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
          <button onclick="resetFiltres();renderLights();" class="btn btn-outline"
                  style="margin-top:.75rem;font-size:.8rem">Réinitialiser</button>
        </div>`;
    }
}

// ============================================================
// Mise à jour des stats dans le header
// ============================================================
function updateStatsUI(s) {
    if (!s) return;
    document.getElementById('s-total').textContent     = s.total;
    document.getElementById('s-on').textContent        = s.actives + ' actives';
    document.getElementById('s-conso').innerHTML       = s.conso_w + '<small style="font-size:1rem;font-weight:400"> W</small>';
    document.getElementById('s-cout-j').innerHTML      = s.cout_jour + '<small style="font-size:1rem;font-weight:400"> €</small>';
    document.getElementById('s-cout-detail').textContent = s.cout_heure + ' €/h · ' + s.cout_mois + ' €/mois';
}

function updateLimiteUI() {
    const el = document.getElementById('s-limite');
    if (el) el.textContent = nbLumieres + '/' + (DROITS.limite === 99 ? '∞' : DROITS.limite) + ' — niveau ' + DROITS.niveau;
}

// ============================================================
// ACTIONS
// ============================================================

// Toggle ON/OFF
async function toggleLight(id, on, maxW) {
    const etat  = on ? 'actif' : 'inactif';
    const conso = on ? parseFloat(maxW) : 0;
    const d = await api('toggle_light', {id, etat, conso});
    if (!d.ok) { toast(d.msg, false); renderLights(); return; }
    const l = lights.find(x => x.id == id);
    if (l) { l.etat = etat; l.conso_watt = conso; if (on) l.luminosite = 100; }
    if (d.pts_gagnes > 0) showPtsBadge('+' + d.pts_gagnes + ' pt');
    resetFiltres(); renderLights(); updateStatsUI(d.stats);
    toast(on ? '💡 Lumière allumée' : '🌙 Lumière éteinte');
}

// Aperçu en temps réel de l'intensité (sans appel API)
function previewIntensity(id, val) {
    const el = document.getElementById('lv-lum-txt-' + id);
    if (el) el.textContent = val + '%';
    const bar = document.getElementById('lv-bar-' + id);
    if (bar) bar.style.width = val + '%';
    const lum = document.getElementById('lv-lum-' + id);
    if (lum) lum.textContent = val + '%';
}

// Valider l'intensité (appel API sur release)
async function commitIntensity(id, lum) {
    const d = await api('update_intensite', {id, luminosite: lum});
    if (!d.ok) { toast(d.msg, false); return; }
    const l = lights.find(x => x.id == id);
    if (l) { l.luminosite = parseInt(lum); l.conso_watt = parseFloat(d.conso) || 0; }
    document.getElementById('lv-conso-' + id).textContent = d.conso + ' W';
    document.getElementById('lv-badge-' + id).textContent = d.conso + ' W · ' + costPerHour(d.conso) + ' €/h';
    document.getElementById('lv-bar-' + id).style.width = lum + '%';
    if (d.pts_gagnes > 0) showPtsBadge('+' + d.pts_gagnes + ' pt');
    updateStatsUI(d.stats);
}

// Valider la couleur (appel API immédiat)
async function commitCouleur(id, couleur) {
    const d = await api('update_couleur', {id, couleur});
    if (!d.ok) { toast(d.msg, false); return; }
    const l = lights.find(x => x.id == id);
    if (l) l.couleur_hex = couleur;
    const sw  = document.getElementById('lv-sw-' + id);
    const hex = document.getElementById('lv-hex-' + id);
    if (sw)  sw.style.background = couleur;
    if (hex) hex.textContent = couleur;
    if (d.pts_gagnes > 0) showPtsBadge('+' + d.pts_gagnes + ' pt');
    updateStatsUI(d.stats);
}

// Ajouter une lumière
async function doAddLight() {
    if (!DROITS.peutAjouter || nbLumieres >= DROITS.limite) {
        toast('🔒 Limite de ' + DROITS.limite + ' lumières atteinte. Montez de niveau !', false);
        return;
    }
    const nom  = document.getElementById('al-nom').value.trim();
    const watt = document.getElementById('al-watt').value;
    if (!nom)  { toast('Le nom est obligatoire.', false); return; }
    if (!watt || watt < 1) { toast('La puissance est obligatoire.', false); return; }

    const d = await api('add_light', {
        nom, watt,
        marque:  document.getElementById('al-marque').value,
        modele:  document.getElementById('al-modele').value,
        piece:   document.getElementById('al-piece').value,
        connect: document.getElementById('al-connect').value,
        signal:  document.getElementById('al-signal').value,
        temp:    document.getElementById('al-temp').value,
        couleur: document.getElementById('al-couleur').value,
        desc:    document.getElementById('al-desc').value,
    });

    if (!d.ok) { toast(d.msg, false); return; }

    lights.push(d.light);
    nbLumieres = d.nb_lumieres;
    DROITS.peutAjouter = nbLumieres < DROITS.limite;
    closeModal('add-light-modal');
    renderLights(); updateStatsUI(d.stats); updateLimiteUI();
    showPtsBadge('+1 pt');
    toast('💡 Lumière ajoutée !');
    document.getElementById('al-nom').value = '';
    document.getElementById('al-modele').value = '';
    document.getElementById('al-desc').value = '';
}

// Supprimer une lumière
async function deleteLight(id, nom) {
    if (!confirm('Supprimer "' + nom + '" ?')) return;
    const d = await api('delete_light', {id});
    if (!d.ok) { toast(d.msg, false); return; }
    lights = lights.filter(l => l.id != id);
    nbLumieres = d.nb_lumieres;
    DROITS.peutAjouter = nbLumieres < DROITS.limite;
    renderLights(); updateStatsUI(d.stats); updateLimiteUI();
    toast('🗑️ Lumière supprimée.');
}

// Badge de points flottant (feedback visuel immédiat)
function showPtsBadge(txt) {
    const b = document.createElement('div');
    b.textContent = txt;
    b.style.cssText = `position:fixed;bottom:5rem;right:1.5rem;z-index:10000;
      background:var(--accent);color:#0d0f14;font-weight:700;font-size:.85rem;
      padding:.4rem .9rem;border-radius:20px;pointer-events:none;
      animation:ptsFly .9s ease forwards`;
    document.body.appendChild(b);
    if (!document.getElementById('pts-anim-style')) {
        const s = document.createElement('style');
        s.id = 'pts-anim-style';
        s.textContent = '@keyframes ptsFly{0%{opacity:1;transform:translateY(0)}100%{opacity:0;transform:translateY(-60px)}}';
        document.head.appendChild(s);
    }
    setTimeout(() => b.remove(), 950);
}

renderLights();
</script>
