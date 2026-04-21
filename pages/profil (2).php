<?php
requireLogin();
$uid = $_SESSION['user_id'];
try {
    $pdo = db();
    $st = $pdo->prepare('SELECT id,login,nom,prenom,email,role,est_banni,sexe,date_naissance,type_membre,created_at FROM utilisateurs WHERE id=?');
    $st->execute([$uid]);
    $user = $st->fetch();
    // Stats lumières
    $stl = $pdo->prepare('SELECT COUNT(*) AS total, SUM(etat="actif") AS actives, COALESCE(SUM(nb_allumages),0) AS allumages FROM lumieres WHERE id_user=?');
    $stl->execute([$uid]);
    $lstats = $stl->fetch();
    // Dernières actions
    $sth = $pdo->prepare('SELECT h.*,l.nom AS lnom FROM historique h LEFT JOIN lumieres l ON h.id_lumiere=l.id WHERE h.id_user=? ORDER BY h.timestamp DESC LIMIT 5');
    $sth->execute([$uid]);
    $history = $sth->fetchAll();
} catch(Exception $e) {
    $user=[]; $lstats=['total'=>0,'actives'=>0,'allumages'=>0]; $history=[];
}

$msg_ok  = $_GET['ok']  ?? '';
$msg_err = $_GET['err'] ?? '';
?>

<div class="page" style="max-width:900px">

  <!-- HEADER -->
  <div style="margin-bottom:2rem">
    <h1 style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:.15rem">👤 Mon profil</h1>
    <p style="font-size:.85rem;color:var(--muted)">Consultez et modifiez vos informations personnelles</p>
  </div>

  <?php if($msg_ok): ?>
  <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);border-radius:10px;padding:.85rem 1.25rem;margin-bottom:1.5rem;color:var(--on);font-size:.88rem">
    ✅ <?= escape($msg_ok) ?>
  </div>
  <?php endif; ?>
  <?php if($msg_err): ?>
  <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:.85rem 1.25rem;margin-bottom:1.5rem;color:var(--off);font-size:.88rem">
    ❌ <?= escape($msg_err) ?>
  </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem">

    <!-- CARTE IDENTITÉ -->
    <div class="card" style="grid-column:1/-1">
      <div style="display:flex;align-items:center;gap:1.25rem;margin-bottom:1.5rem;padding-bottom:1.25rem;border-bottom:1px solid var(--border)">
        <div style="width:64px;height:64px;border-radius:50%;background:rgba(245,200,66,.12);border:2px solid rgba(245,200,66,.3);display:flex;align-items:center;justify-content:center;font-size:1.8rem;flex-shrink:0">
          <?= strtoupper(substr($user['prenom']??'?',0,1)) ?>
        </div>
        <div>
          <div style="font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:800"><?= escape(($user['prenom']??'').' '.($user['nom']??'')) ?></div>
          <div style="font-size:.85rem;color:var(--muted);margin-top:.15rem">@<?= escape($user['login']??'') ?></div>
          <div style="margin-top:.35rem;display:flex;gap:.5rem;flex-wrap:wrap">
            <span style="font-size:.72rem;font-weight:600;padding:.15rem .55rem;border-radius:20px;background:rgba(245,200,66,.12);color:var(--accent);border:1px solid rgba(245,200,66,.25)">
              <?= $user['role']==='admin' ? '⚙️ Administrateur' : '👤 Membre' ?>
            </span>
            <span style="font-size:.72rem;font-weight:600;padding:.15rem .55rem;border-radius:20px;background:rgba(99,102,241,.1);color:#a5b4fc;border:1px solid rgba(99,102,241,.25)">
              <?= escape(ucfirst($user['type_membre']??'habitant')) ?>
            </span>
          </div>
        </div>

      </div>

      <!-- INFO PUBLIQUES -->
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.75rem 1.5rem">
        <?php
        $infos = [
          ['label'=>'Email',           'val'=>$user['email']??'—'],
          ['label'=>'Date de naissance','val'=>$user['date_naissance']?date('d/m/Y',strtotime($user['date_naissance'])):'—'],
          ['label'=>'Sexe',            'val'=>match($user['sexe']??''){  'M'=>'Homme','F'=>'Femme',default=>'Autre'}],
          ['label'=>'Membre depuis',   'val'=>date('d/m/Y',strtotime($user['created_at']??'now'))],
        ];
        foreach($infos as $i): ?>
        <div>
          <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:.2rem"><?= $i['label'] ?></div>
          <div style="font-size:.9rem;font-weight:500"><?= escape($i['val']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- STATS LUMIÈRES -->
    <div class="card">
      <div style="font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:1rem">Mes lumières</div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.5rem;text-align:center">
        <div>
          <div style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;color:var(--accent)"><?= (int)$lstats['total'] ?></div>
          <div style="font-size:.75rem;color:var(--muted);margin-top:.1rem">Enregistrées</div>
        </div>
        <div>
          <div style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;color:var(--on)"><?= (int)$lstats['actives'] ?></div>
          <div style="font-size:.75rem;color:var(--muted);margin-top:.1rem">Actives</div>
        </div>
        <div>
          <div style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800"><?= (int)$lstats['allumages'] ?></div>
          <div style="font-size:.75rem;color:var(--muted);margin-top:.1rem">Allumages</div>
        </div>
      </div>
      <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--border)">
        <a href="/Lumihome/index.php?page=dashboard" style="font-size:.83rem;color:var(--accent)">Gérer mes lumières →</a>
      </div>
    </div>

    <!-- DERNIÈRES ACTIONS -->
    <div class="card">
      <div style="font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:.75rem">Dernières actions</div>
      <?php if(empty($history)): ?>
      <p style="font-size:.83rem;color:var(--muted)">Aucune action enregistrée.</p>
      <?php else: foreach($history as $h): ?>
      <div style="display:flex;gap:.6rem;align-items:flex-start;padding:.45rem 0;border-bottom:1px solid rgba(42,48,64,.4)">
        <span style="font-size:.9rem"><?= $h['action']==='toggle'?'🔄':'✏️' ?></span>
        <div>
          <div style="font-size:.8rem;font-weight:500"><?= escape($h['lnom']??'?') ?></div>
          <div style="font-size:.72rem;color:var(--muted)"><?= date('d/m H:i',strtotime($h['timestamp'])) ?></div>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- FORMULAIRE MODIFICATION -->
  <div class="card">
    <div style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;margin-bottom:1.25rem;padding-bottom:.75rem;border-bottom:1px solid var(--border)">
      ✏️ Modifier mes informations
    </div>
    <form method="POST" action="/Lumihome/api/index.php?action=update_profil" onsubmit="return submitProfil(event)">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Prénom</label>
          <input class="form-input" name="prenom" id="p-prenom" value="<?= escape($user['prenom']??'') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Nom</label>
          <input class="form-input" name="nom" id="p-nom" value="<?= escape($user['nom']??'') ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Email</label>
          <input class="form-input" name="email" id="p-email" type="email" value="<?= escape($user['email']??'') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Sexe</label>
          <select class="form-input" name="sexe" id="p-sexe">
            <option value="M"     <?= ($user['sexe']??'')==='M'    ?'selected':'' ?>>Homme</option>
            <option value="F"     <?= ($user['sexe']??'')==='F'    ?'selected':'' ?>>Femme</option>
            <option value="Autre" <?= ($user['sexe']??'')==='Autre'?'selected':'' ?>>Autre</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Date de naissance</label>
          <input class="form-input" name="dob" id="p-dob" type="date" value="<?= escape($user['date_naissance']??'') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Type de membre</label>
          <select class="form-input" name="type_membre" id="p-membre">
            <?php foreach(['père','mère','enfant','habitant','autre'] as $tm): ?>
            <option value="<?= $tm ?>" <?= ($user['type_membre']??'')===$tm?'selected':'' ?>><?= ucfirst($tm) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- CHANGEMENT MOT DE PASSE -->
      <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
        <div style="font-size:.85rem;font-weight:600;margin-bottom:.75rem;color:var(--muted)">🔒 Changer le mot de passe <span style="font-weight:400">(laisser vide pour ne pas modifier)</span></div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nouveau mot de passe</label>
            <input class="form-input" name="mdp" id="p-mdp" type="password" placeholder="6 caractères minimum" autocomplete="new-password">
          </div>
          <div class="form-group">
            <label class="form-label">Confirmer le mot de passe</label>
            <input class="form-input" name="mdp2" id="p-mdp2" type="password" placeholder="Répéter le mot de passe" autocomplete="new-password">
          </div>
        </div>
      </div>

      <div style="display:flex;justify-content:flex-end;margin-top:1.25rem">
        <button type="submit" class="btn btn-primary">Enregistrer les modifications →</button>
      </div>
    </form>
  </div>
</div>

<script>
async function submitProfil(e) {
  e.preventDefault();
  const mdp  = document.getElementById('p-mdp').value;
  const mdp2 = document.getElementById('p-mdp2').value;
  if (mdp && mdp !== mdp2) { toast('Les mots de passe ne correspondent pas.', false); return false; }
  if (mdp && mdp.length < 6) { toast('Mot de passe trop court (6 caractères minimum).', false); return false; }

  const d = await api('update_profil', {
    prenom:      document.getElementById('p-prenom').value,
    nom:         document.getElementById('p-nom').value,
    email:       document.getElementById('p-email').value,
    sexe:        document.getElementById('p-sexe').value,
    dob:         document.getElementById('p-dob').value,
    type_membre: document.getElementById('p-membre').value,
    mdp, mdp2,
  });
  toast(d.msg, d.ok);
  if (d.ok) setTimeout(() => location.reload(), 1000);
  return false;
}
</script>
