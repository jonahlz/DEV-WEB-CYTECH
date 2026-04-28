<?php
// ============================================================
// LumiHome — Page Niveaux & Points
// Accessible via index.php?page=niveaux
// ============================================================
requireLogin();

$uid = $_SESSION['user_id'];

try {
    $pdo = db();

    // Infos utilisateur complètes
    $st = $pdo->prepare('
        SELECT u.*, nc.libelle AS niveau_libelle, nc.description AS niveau_desc,
               nc.couleur_hex, nc.emoji AS niveau_emoji, nc.pts_requis
        FROM utilisateurs u
        LEFT JOIN niveaux_config nc ON nc.niveau = u.niveau
        WHERE u.id = ?
    ');
    $st->execute([$uid]);
    $user = $st->fetch();

    // Tous les niveaux pour la progression
    $niveaux = $pdo->query('SELECT * FROM niveaux_config ORDER BY pts_requis ASC')->fetchAll();

    // Dernier niveau disponible suivant
    $prochain = null;
    foreach ($niveaux as $n) {
        if ($n['pts_requis'] > (float)$user['points']) {
            $prochain = $n;
            break;
        }
    }

    // Niveaux débloqués (points suffisants)
    $niveaux_disponibles = array_filter($niveaux, fn($n) =>
        $n['pts_requis'] <= (float)$user['points'] && $n['niveau'] !== $user['niveau']
    );

    // Historique des 20 derniers gains de points
    $hist = $pdo->prepare('
        SELECT * FROM points_log
        WHERE id_user = ?
        ORDER BY ts DESC
        LIMIT 20
    ');
    $hist->execute([$uid]);
    $historique_pts = $hist->fetchAll();

    // Stats du jour
    $st_day = $pdo->prepare('
        SELECT
            COALESCE(SUM(CASE WHEN type_gain="connexion" THEN pts_gagnes END), 0) AS pts_cx_today,
            COALESCE(SUM(CASE WHEN type_gain="action"    THEN pts_gagnes END), 0) AS pts_ac_today,
            COUNT(*) AS actions_today
        FROM points_log
        WHERE id_user = ? AND DATE(ts) = CURDATE()
    ');
    $st_day->execute([$uid]);
    $today = $st_day->fetch();

} catch (Exception $e) {
    die('<div class="page"><div class="card">Erreur : ' . escape($e->getMessage()) . '</div></div>');
}

// Calculs de progression
$pts_total  = (float)$user['points'];
$pts_requis = $prochain ? (float)$prochain['pts_requis'] : $pts_total;
$pts_niveau_actuel = (float)($user['pts_requis'] ?? 0);

// Trouver les pts du niveau actuel
foreach ($niveaux as $n) {
    if ($n['niveau'] === $user['niveau']) {
        $pts_niveau_actuel = (float)$n['pts_requis'];
        break;
    }
}

$range     = $prochain ? ($pts_requis - $pts_niveau_actuel) : 1;
$progress  = $prochain
    ? min(100, round(($pts_total - $pts_niveau_actuel) / $range * 100))
    : 100;
$pts_manquants = $prochain ? max(0, round($pts_requis - $pts_total, 2)) : 0;

$couleur_niveau = $user['couleur_hex'] ?? '#f5c842';
$emoji_niveau   = $user['niveau_emoji'] ?? '⭐';
$libelle_niveau = $user['niveau_libelle'] ?? ucfirst($user['niveau']);
?>

<div class="page">

  <!-- EN-TÊTE ─────────────────────────────────────────────── -->
  <div style="margin-bottom:2rem">
    <h1 style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:.2rem">
      Niveaux &amp; Points
    </h1>
    <p style="font-size:.85rem;color:var(--muted)">
      Consultez votre progression et débloquez de nouveaux modules
    </p>
  </div>

  <!-- CARTE NIVEAU ACTUEL ────────────────────────────────── -->
  <div class="card" style="margin-bottom:1.5rem;position:relative;overflow:hidden">
    <!-- Halo de fond -->
    <div style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;
                border-radius:50%;background:<?= escape($couleur_niveau) ?>;
                opacity:.06;pointer-events:none"></div>

    <div style="display:flex;align-items:flex-start;justify-content:space-between;
                gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem">
      <div style="display:flex;align-items:center;gap:1rem">
        <div style="width:64px;height:64px;border-radius:16px;
                    background:rgba(<?= hexToRgb($couleur_niveau) ?>,.12);
                    border:1.5px solid rgba(<?= hexToRgb($couleur_niveau) ?>,.3);
                    display:flex;align-items:center;justify-content:center;font-size:2rem">
          <?= $emoji_niveau ?>
        </div>
        <div>
          <div style="font-size:.72rem;color:var(--muted);font-weight:600;
                      text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem">
            Niveau actuel
          </div>
          <div style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;
                      color:<?= escape($couleur_niveau) ?>">
            <?= escape($libelle_niveau) ?>
          </div>
          <div style="font-size:.82rem;color:var(--muted);margin-top:.15rem">
            <?= escape($user['niveau_desc'] ?? '') ?>
          </div>
        </div>
      </div>

      <!-- Points total -->
      <div style="text-align:right">
        <div style="font-size:.72rem;color:var(--muted);margin-bottom:.2rem">Points totaux</div>
        <div style="font-family:'Syne',sans-serif;font-size:2.5rem;font-weight:800;
                    color:<?= escape($couleur_niveau) ?>">
          <?= number_format($pts_total, 2, ',', ' ') ?>
        </div>
        <div style="font-size:.75rem;color:var(--muted)">
          <?= number_format((float)$user['pts_connexion'], 2, ',', ' ') ?> connexion
          + <?= number_format((float)$user['pts_actions'], 2, ',', ' ') ?> actions
        </div>
      </div>
    </div>

    <!-- Barre de progression ─────────────────────────────── -->
    <?php if ($prochain): ?>
    <div style="margin-bottom:.75rem">
      <div style="display:flex;justify-content:space-between;
                  font-size:.78rem;color:var(--muted);margin-bottom:.5rem">
        <span><?= escape($libelle_niveau) ?></span>
        <span><?= escape($prochain['emoji'].' '.$prochain['libelle']) ?>
          — <?= number_format($pts_requis, 0) ?> pts requis</span>
      </div>
      <div style="height:10px;background:var(--surface2);border-radius:5px;overflow:hidden">
        <div style="height:100%;width:<?= $progress ?>%;
                    background:linear-gradient(90deg,<?= escape($couleur_niveau) ?>,
                    <?= escape($prochain['couleur_hex']) ?>);
                    border-radius:5px;transition:width 1s ease">
        </div>
      </div>
      <div style="display:flex;justify-content:space-between;
                  font-size:.74rem;color:var(--muted);margin-top:.4rem">
        <span><?= number_format($pts_total, 2, ',', ' ') ?> pts</span>
        <span><?= $pts_manquants > 0
            ? number_format($pts_manquants, 2, ',', ' ') . ' pts manquants'
            : 'Niveau débloqué !' ?></span>
      </div>
    </div>
    <?php else: ?>
    <div style="padding:.75rem 1rem;background:rgba(249,115,22,.08);
                border:1px solid rgba(249,115,22,.2);border-radius:10px;
                font-size:.85rem;color:#f97316;text-align:center">
      👑 Vous avez atteint le niveau maximum — Expert !
    </div>
    <?php endif; ?>

    <!-- Bouton changer de niveau ─────────────────────────── -->
    <?php if (!empty($niveaux_disponibles)): ?>
    <div style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--border)">
      <div style="font-size:.82rem;color:var(--muted);margin-bottom:.75rem">
        Niveaux accessibles avec vos points actuels :
      </div>
      <div style="display:flex;gap:.75rem;flex-wrap:wrap">
        <?php foreach ($niveaux_disponibles as $nv): ?>
        <button class="btn btn-outline"
                onclick="changerNiveau('<?= escape($nv['niveau']) ?>','<?= escape($nv['libelle']) ?>')"
                style="border-color:<?= escape($nv['couleur_hex']) ?>;
                       color:<?= escape($nv['couleur_hex']) ?>">
          <?= escape($nv['emoji'].' Passer '.$nv['libelle']) ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- STATISTIQUES RAPIDES ───────────────────────────────── -->
  <div class="stats-row" style="margin-bottom:1.5rem">
    <div class="stat-card">
      <div class="stat-top"><span class="stat-icon">🔌</span></div>
      <div class="stat-val"><?= (int)$user['nb_connexions'] ?></div>
      <div class="stat-lbl">Connexions totales</div>
      <div class="stat-sub">+<?= number_format((float)$user['pts_connexion'],2,',','') ?> pts gagnés</div>
    </div>
    <div class="stat-card">
      <div class="stat-top"><span class="stat-icon">⚙️</span></div>
      <div class="stat-val"><?= (int)$user['nb_actions'] ?></div>
      <div class="stat-lbl">Actions totales</div>
      <div class="stat-sub">+<?= number_format((float)$user['pts_actions'],2,',','') ?> pts gagnés</div>
    </div>
    <div class="stat-card">
      <div class="stat-top"><span class="stat-icon">📅</span></div>
      <div class="stat-val"><?= number_format((float)$today['pts_cx_today']+(float)$today['pts_ac_today'],2,',','') ?></div>
      <div class="stat-lbl">Points gagnés aujourd'hui</div>
      <div class="stat-sub"><?= (int)$today['actions_today'] ?> action(s) ce jour</div>
    </div>
    <div class="stat-card">
      <div class="stat-top"><span class="stat-icon">🏆</span></div>
      <div class="stat-val"><?= $prochain
          ? number_format($pts_manquants,2,',','')
          : '—' ?></div>
      <div class="stat-lbl"><?= $prochain ? 'Pts manquants pour '.escape($prochain['libelle']) : 'Niveau max atteint' ?></div>
      <div class="stat-sub">0,25 pts/connexion · 0,50 pts/action</div>
    </div>
  </div>

  <!-- TOUS LES NIVEAUX ───────────────────────────────────── -->
  <div class="sec-header"><div class="sec-title">🎯 Tous les niveaux</div></div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));
              gap:1rem;margin-bottom:2rem">
    <?php foreach ($niveaux as $nv):
      $est_actuel  = ($nv['niveau'] === $user['niveau']);
      $est_deblock = ((float)$user['points'] >= (float)$nv['pts_requis']);
      $col = $nv['couleur_hex'];
    ?>
    <div class="card" style="
      position:relative;
      border-color:<?= $est_actuel ? escape($col) : 'var(--border)' ?>;
      <?= $est_actuel ? 'box-shadow:0 0 0 1px '.escape($col).'22' : '' ?>
    ">
      <?php if ($est_actuel): ?>
      <div style="position:absolute;top:12px;right:12px;
                  font-size:.65rem;font-weight:700;padding:.2rem .5rem;
                  border-radius:20px;background:<?= escape($col) ?>;color:#0d0f14;
                  text-transform:uppercase;letter-spacing:.4px">
        Actuel
      </div>
      <?php elseif (!$est_deblock): ?>
      <div style="position:absolute;top:12px;right:12px;
                  font-size:.65rem;font-weight:700;padding:.2rem .5rem;
                  border-radius:20px;background:var(--surface2);color:var(--muted);
                  text-transform:uppercase;letter-spacing:.4px">
        🔒 Verrouillé
      </div>
      <?php else: ?>
      <div style="position:absolute;top:12px;right:12px;
                  font-size:.65rem;font-weight:700;padding:.2rem .5rem;
                  border-radius:20px;background:rgba(34,197,94,.1);color:#22c55e;
                  text-transform:uppercase;letter-spacing:.4px">
        ✓ Débloqué
      </div>
      <?php endif; ?>

      <div style="font-size:2rem;margin-bottom:.5rem;opacity:<?= $est_deblock?1:.35 ?>">
        <?= $nv['emoji'] ?>
      </div>
      <div style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:800;
                  color:<?= $est_deblock ? escape($col) : 'var(--muted)' ?>;
                  margin-bottom:.25rem">
        <?= escape($nv['libelle']) ?>
      </div>
      <div style="font-size:.8rem;color:var(--muted);line-height:1.5;margin-bottom:.75rem">
        <?= escape($nv['description']) ?>
      </div>
      <div style="font-size:.8rem;font-weight:600;
                  color:<?= $est_deblock ? escape($col) : 'var(--muted)' ?>">
        <?= $nv['pts_requis'] == 0 ? 'Dès l\'inscription' : number_format((float)$nv['pts_requis'],0).' pts requis' ?>
      </div>
      <!-- Modules débloqués -->
      <div style="margin-top:.75rem;padding-top:.65rem;border-top:1px solid var(--border);
                  font-size:.75rem;color:var(--muted)">
        <?php
        $acces = match($nv['niveau']) {
            'debutant'      => '🔍 Module Information · Visualisation (lecture)',
            'intermediaire' => '🔍 Module Information · Visualisation (complète)',
            'avance'        => '⚙️ + Module Gestion (objets connectés)',
            'expert'        => '👑 + Module Administration (contrôle total)',
            default         => ''
        };
        echo escape($acces);
        ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- COMMENT GAGNER DES POINTS ──────────────────────────── -->
  <div class="sec-header"><div class="sec-title">💡 Comment gagner des points ?</div></div>
  <div class="card" style="margin-bottom:2rem">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1.25rem">
      <div>
        <div style="font-size:1.5rem;margin-bottom:.4rem">🔌</div>
        <div style="font-weight:600;font-size:.9rem;margin-bottom:.25rem">Connexion</div>
        <div style="font-size:.8rem;color:var(--muted);line-height:1.5">
          Chaque connexion à la plateforme rapporte <strong style="color:var(--accent)">0,25 pt</strong>.
        </div>
      </div>
      <div>
        <div style="font-size:1.5rem;margin-bottom:.4rem">💡</div>
        <div style="font-weight:600;font-size:.9rem;margin-bottom:.25rem">Action sur une lumière</div>
        <div style="font-size:.8rem;color:var(--muted);line-height:1.5">
          Allumer, éteindre, modifier l'intensité ou la couleur = <strong style="color:var(--accent)">0,50 pt</strong>.
        </div>
      </div>
      <div>
        <div style="font-size:1.5rem;margin-bottom:.4rem">➕</div>
        <div style="font-weight:600;font-size:.9rem;margin-bottom:.25rem">Ajouter une lumière</div>
        <div style="font-size:.8rem;color:var(--muted);line-height:1.5">
          Enregistrer un nouvel appareil = <strong style="color:var(--accent)">1,00 pt</strong>.
        </div>
      </div>
      <div>
        <div style="font-size:1.5rem;margin-bottom:.4rem">🗑️</div>
        <div style="font-weight:600;font-size:.9rem;margin-bottom:.25rem">Supprimer une lumière</div>
        <div style="font-size:.8rem;color:var(--muted);line-height:1.5">
          Retirer un appareil = <strong style="color:var(--accent)">0,25 pt</strong>.
        </div>
      </div>
    </div>
  </div>

  <!-- HISTORIQUE DES POINTS ──────────────────────────────── -->
  <div class="sec-header"><div class="sec-title">📋 Historique des points</div></div>
  <div class="card">
    <?php if (empty($historique_pts)): ?>
    <p style="font-size:.85rem;color:var(--muted);text-align:center;padding:1rem">
      Aucun gain de points enregistré pour l'instant.
    </p>
    <?php else: ?>
    <?php foreach ($historique_pts as $h):
      $icon = match($h['type_gain']) {
          'connexion'   => '🔌',
          'consultation'=> '🔍',
          'action'      => '⚙️',
          'bonus'       => '🎁',
          'admin'       => '👑',
          default       => '⭐'
      };
      $label = match($h['type_gain']) {
          'connexion'   => 'Connexion',
          'consultation'=> 'Consultation',
          'action'      => 'Action',
          'bonus'       => 'Bonus',
          'admin'       => 'Attribution admin',
          default       => $h['type_gain']
      };
    ?>
    <div class="hist-item">
      <div class="hist-icon"><?= $icon ?></div>
      <div style="flex:1">
        <div class="hist-text">
          <?= escape($label) ?>
          <?php if ($h['detail']): ?>
          — <span style="color:var(--muted)"><?= escape($h['detail']) ?></span>
          <?php endif; ?>
        </div>
        <div class="hist-sub"><?= date('d/m/Y H:i', strtotime($h['ts'])) ?></div>
      </div>
      <div style="font-weight:700;color:var(--accent);font-size:.9rem;white-space:nowrap">
        +<?= number_format((float)$h['pts_gagnes'],2,',','') ?> pt<?= $h['pts_gagnes']>1?'s':'' ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<!-- MODAL CONFIRMATION CHANGEMENT NIVEAU ─────────────────── -->
<div class="modal-overlay" id="confirm-niveau-modal">
  <div class="modal">
    <h2>Changer de niveau</h2>
    <p class="modal-sub" id="confirm-niveau-msg">Confirmer le passage de niveau ?</p>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('confirm-niveau-modal')">Annuler</button>
      <button class="btn btn-primary" id="confirm-niveau-btn">Confirmer →</button>
    </div>
  </div>
</div>

<script>
let _pendingNiveau = null;

function changerNiveau(code, libelle) {
  _pendingNiveau = code;
  document.getElementById('confirm-niveau-msg').textContent =
    'Voulez-vous passer au niveau ' + libelle + ' ?';
  document.getElementById('confirm-niveau-btn').onclick = doChangerNiveau;
  openModal('confirm-niveau-modal');
}

async function doChangerNiveau() {
  if (!_pendingNiveau) return;
  closeModal('confirm-niveau-modal');
  const d = await api('changer_niveau', { niveau: _pendingNiveau });
  if (d.ok) {
    toast('🎉 ' + d.msg);
    setTimeout(() => location.reload(), 1200);
  } else {
    toast(d.msg, false);
  }
}
</script>

<?php
// Fonction utilitaire : convertit #RRGGBB → "R,G,B"
function hexToRgb(string $hex): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3)
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    $r = hexdec(substr($hex,0,2));
    $g = hexdec(substr($hex,2,2));
    $b = hexdec(substr($hex,4,2));
    return "$r,$g,$b";
}
?>
