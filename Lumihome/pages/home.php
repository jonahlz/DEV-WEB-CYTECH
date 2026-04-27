<?php
// Page home – démo si déconnecté, vraies lumières si connecté
$est_connecte = isLoggedIn();
try {
    $pdo = db();
    if ($est_connecte) {
        // Vraies lumières de l'utilisateur connecté
        $st = $pdo->prepare('SELECT l.nom, l.etat, l.luminosite, l.couleur_hex, p.nom AS piece FROM lumieres l LEFT JOIN pieces p ON l.id_piece=p.id WHERE l.id_user=? ORDER BY l.id');
        $st->execute([$_SESSION['user_id']]);
        $demo_lights = $st->fetchAll();
        $st2 = $pdo->prepare('SELECT COUNT(*) AS total, SUM(etat="actif") AS on_count FROM lumieres WHERE id_user=?');
        $st2->execute([$_SESSION['user_id']]);
        $stats_pub = $st2->fetch();
        $nb_pieces = db()->query('SELECT COUNT(*) FROM pieces')->fetchColumn();
    } else {
        // Démo fixe pour les visiteurs
        $demo_lights = [
            ['nom'=>'Lustre Salon',       'piece'=>'Salon',          'etat'=>'actif',   'luminosite'=>80,  'couleur_hex'=>'#FFEECC'],
            ['nom'=>'Plafonnier Cuisine', 'piece'=>'Cuisine',        'etat'=>'actif',   'luminosite'=>100, 'couleur_hex'=>'#FFFFFF'],
            ['nom'=>'Lampe Bureau',       'piece'=>'Bureau',         'etat'=>'inactif', 'luminosite'=>0,   'couleur_hex'=>'#FFEEDD'],
            ['nom'=>'Veilleuse',          'piece'=>'Chambre enfant', 'etat'=>'actif',   'luminosite'=>20,  'couleur_hex'=>'#FF88CC'],
        ];
        $stats_pub = ['total' => 4, 'on_count' => 3];
    $nb_pieces  = 6; // nombre de pièces de la démo
    }
} catch(Exception $e) {
    $demo_lights = [];
    $stats_pub = ['total'=>0,'on_count'=>0];
    $est_connecte = false;
}
?>

<!-- HERO -->
<div class="hero">
  <div style="display:inline-flex;align-items:center;gap:.4rem;padding:.3rem .8rem;background:rgba(245,200,66,.08);border:1px solid rgba(245,200,66,.2);border-radius:20px;font-size:.78rem;color:var(--accent);margin-bottom:1.25rem">
    🏠 Maison intelligente · ING1 2025-2026
  </div>
  <h1>Gérez vos<br><span>lumières connectées</span></h1>
  <p>Contrôlez l'éclairage de toute votre maison, économisez de l'énergie et créez des ambiances depuis une seule plateforme.</p>
  <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
    <?php if(isLoggedIn()): ?>
    <a href="/Lumihome/index.php?page=dashboard" class="btn btn-primary">Aller à mon tableau de bord →</a>
    <?php else: ?>
    <button class="btn btn-primary" onclick="openModal('reg-modal')">Rejoindre la maison →</button>
    <button class="btn btn-outline" onclick="openModal('login-modal')">Se connecter</button>
    <?php endif; ?>
  </div>
</div>

<!-- STATS PUBLIQUES -->
<div style="max-width:1100px;margin:0 auto;padding:0 2rem">
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--border);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;margin-bottom:3rem">
    <div style="background:var(--surface);padding:1.5rem;text-align:center">
      <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:var(--accent)"><?= $stats_pub['total'] ?></div>
      <div style="font-size:.8rem;color:var(--muted);margin-top:.2rem">Lumières enregistrées</div>
    </div>
    <div style="background:var(--surface);padding:1.5rem;text-align:center">
      <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:var(--accent)"><?= $stats_pub['on_count'] ?></div>
      <div style="font-size:.8rem;color:var(--muted);margin-top:.2rem">Allumées en ce moment</div>
    </div>
    <div style="background:var(--surface);padding:1.5rem;text-align:center">
      <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:var(--accent)"><?= $nb_pieces ?></div>
      <div style="font-size:.8rem;color:var(--muted);margin-top:.2rem">Pièces disponibles</div>
    </div>
  </div>

  <!-- APERÇU LIMITÉ VISITEUR -->
  <div style="margin-bottom:3rem">
    <div class="sec-header">
      <div class="sec-title">
        <?php if($est_connecte): ?>
        Mes lumières <span style="font-size:.8rem;font-weight:400;color:var(--on)">● En direct</span>
        <?php else: ?>
        Aperçu démo <span style="font-size:.8rem;font-weight:400;background:rgba(245,200,66,.12);color:var(--accent);border:1px solid rgba(245,200,66,.25);padding:.1rem .5rem;border-radius:20px">Exemple fictif</span>
        <?php endif; ?>
      </div>
      <?php if($est_connecte): ?>
      <a href="/Lumihome/index.php?page=dashboard" style="font-size:.78rem;color:var(--accent)">Gérer mes lumières →</a>
      <?php else: ?>
      <span style="font-size:.78rem;color:var(--accent);cursor:pointer" onclick="openModal('reg-modal')">Accéder à mes lumières →</span>
      <?php endif; ?>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.75rem">
      <?php foreach($demo_lights as $l): ?>
      <div class="demo-light">
        <div class="demo-dot <?= $l['etat']==='actif'?'on':'off' ?>"></div>
        <div style="flex:1">
          <div style="font-weight:600;font-size:.88rem"><?= escape($l['nom']) ?></div>
          <div style="font-size:.74rem;color:var(--muted)"><?= escape($l['piece']??'') ?></div>
        </div>
        <?php if($l['etat']==='actif'): ?>
        <div style="display:flex;align-items:center;gap:.35rem">
          <div style="width:12px;height:12px;border-radius:3px;background:<?= escape($l['couleur_hex']) ?>;border:1px solid rgba(255,255,255,.15)"></div>
          <span style="font-size:.75rem;color:var(--muted)"><?= $l['luminosite'] ?>%</span>
        </div>
        <?php else: ?>
        <span style="font-size:.72rem;color:var(--off)">Éteinte</span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if(!isLoggedIn()): ?>
    <div style="margin-top:1rem;padding:1rem;background:rgba(245,200,66,.04);border:1px dashed rgba(245,200,66,.2);border-radius:10px;text-align:center">
      <span style="font-size:.85rem;color:var(--muted)">🔒 Connectez-vous pour voir et contrôler toutes les lumières en temps réel.</span>
      <button class="btn btn-primary btn-sm" style="margin-left:.75rem" onclick="openModal('login-modal')">Se connecter</button>
    </div>
    <?php else: ?>
    <div style="margin-top:1rem;padding:1rem;background:rgba(34,197,94,.04);border:1px dashed rgba(34,197,94,.2);border-radius:10px;text-align:center">
      <span style="font-size:.85rem;color:var(--muted)">✅ Vous êtes connecté. Rendez-vous sur votre tableau de bord pour tout contrôler.</span>
      <a href="/Lumihome/index.php?page=dashboard" class="btn btn-primary btn-sm" style="margin-left:.75rem">Mon tableau de bord</a>
    </div>
    <?php endif; ?>
  </div>

  <!-- ACTUALITÉS -->
  <div class="sec-header"><div class="sec-title">📰 Actualités</div></div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;margin-bottom:3rem">
    <?php
    $news = [
      ['tag'=>'Économies',    'titre'=>'Réduisez votre facture d\'éclairage de 30 %',         'txt'=>'Les planifications intelligentes et la détection de présence permettent de réduire significativement la consommation.',          'date'=>'18 avr. 2026'],
      ['tag'=>'Ambiance',     'titre'=>'Créer des scènes lumineuses pour chaque moment',       'txt'=>'Du réveil en douceur avec une lumière imitant le soleil levant jusqu\'à l\'ambiance cinéma : maîtrisez l\'éclairage dynamique.', 'date'=>'15 avr. 2026'],
      ['tag'=>'Mise à jour',  'titre'=>'Nouvelle intégration Zigbee 3.0 disponible',           'txt'=>'LumiHome supporte maintenant Zigbee 3.0 pour une meilleure compatibilité avec les appareils IKEA et Philips Hue.',              'date'=>'10 avr. 2026'],
    ];
    foreach($news as $n): ?>
    <div class="card card-sm">
      <div style="font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--accent);margin-bottom:.35rem"><?= $n['tag'] ?></div>
      <div style="font-weight:600;font-size:.9rem;margin-bottom:.35rem"><?= $n['titre'] ?></div>
      <div style="font-size:.8rem;color:var(--muted);line-height:1.5"><?= $n['txt'] ?></div>
      <div style="font-size:.72rem;color:var(--muted);margin-top:.65rem"><?= $n['date'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<footer style="border-top:1px solid var(--border);padding:1.5rem;text-align:center;color:var(--muted);font-size:.8rem">
  <strong style="color:var(--text)">LumiHome</strong> · Plateforme de maison intelligente · ING1 CY Tech 2025-2026
</footer>
