<?php
// ============================================================
// LumiHome — Page profil utilisateur (ex "profil (3).php")
// Accessible via index.php?page=profil
// ============================================================
requireLogin();

$profil       = null;
$age          = '-';
$erreur_profil = '';

try {
    $pdo = db();
    $st  = $pdo->prepare('
        SELECT id, login, nom, prenom, email, role, sexe,
               date_naissance, type_membre, points, created_at
        FROM utilisateurs
        WHERE id = ?
        LIMIT 1
    ');
    $st->execute([$_SESSION['user_id']]);
    $profil = $st->fetch();

    if (!$profil) {
        $erreur_profil = 'Profil introuvable.';
    } elseif (!empty($profil['date_naissance'])) {
        $age = (new DateTime($profil['date_naissance']))->diff(new DateTime())->y . ' ans';
    }
} catch (Exception $e) {
    $erreur_profil = 'Impossible de charger le profil.';
}

if ($erreur_profil !== '') {
    echo '<div class="page"><div class="card" style="border-color:rgba(239,68,68,.35);color:#fca5a5;">'
         . escape($erreur_profil) . '</div></div>';
    return;
}

// Échappement de toutes les valeurs affichées
$prenom      = escape($profil['prenom']      ?? '');
$nom         = escape($profil['nom']         ?? '');
$email       = escape($profil['email']       ?? '');
$login       = escape($profil['login']       ?? '');
$role        = escape($profil['role']        ?? 'membre');
$sexe        = $profil['sexe']               ?? 'Autre';
$dob         = escape($profil['date_naissance'] ?? '');
$type_membre = $profil['type_membre']        ?? 'habitant';
$points      = isset($profil['points'])
               ? number_format((float) $profil['points'], 2, ',', ' ')
               : '0';
$created_at  = !empty($profil['created_at'])
               ? date('d/m/Y H:i', strtotime($profil['created_at']))
               : '-';
?>

<div class="page">

  <!-- En-tête ──────────────────────────────────────────────── -->
  <div style="margin-bottom:1.75rem">
    <h1 style="font-family:Syne,sans-serif;font-size:1.9rem;font-weight:800;margin-bottom:.2rem">
      Mon profil
    </h1>
    <p style="font-size:.9rem;color:var(--muted)">
      Consultez vos informations personnelles et modifiez-les si nécessaire.
    </p>
  </div>

  <!-- Grille principale ────────────────────────────────────── -->
  <div class="profil-grid" style="display:grid;grid-template-columns:1.1fr .9fr;gap:1rem">

    <!-- Carte identité ─────────────────────────────────────── -->
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;
                  gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
        <div>
          <div style="font-family:Syne,sans-serif;font-size:1.5rem;font-weight:800">
            <?= $prenom . ' ' . $nom ?>
          </div>
          <div style="font-size:.85rem;color:var(--muted);margin-top:.2rem">
            @<?= $login ?>
          </div>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
          <span class="badge-role <?= $role === 'admin' ? 'badge-admin' : '' ?>">
            <?= ucfirst($role) ?>
          </span>
          <span class="badge-role"><?= escape($type_membre) ?></span>
        </div>
      </div>

      <!-- Infos détaillées -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem 1rem">
        <div>
          <div class="meta-lbl">Email</div>
          <div class="meta-val"><?= $email ?></div>
        </div>
        <div>
          <div class="meta-lbl">Sexe</div>
          <div class="meta-val"><?= escape($sexe) ?></div>
        </div>
        <div>
          <div class="meta-lbl">Date de naissance</div>
          <div class="meta-val"><?= $dob ? date('d/m/Y', strtotime($dob)) : '-' ?></div>
        </div>
        <div>
          <div class="meta-lbl">Âge</div>
          <div class="meta-val"><?= $age ?></div>
        </div>
        <div>
          <div class="meta-lbl">Points</div>
          <div class="meta-val"><?= $points ?></div>
        </div>
        <div>
          <div class="meta-lbl">Membre depuis</div>
          <div class="meta-val"><?= $created_at ?></div>
        </div>
      </div>

      <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--border);
                  display:flex;gap:.75rem;flex-wrap:wrap">
        <button class="btn btn-primary" onclick="toggleEditProfil()">
          Modifier mon profil
        </button>
        <a class="btn btn-outline" href="/Lumihome/index.php?page=dashboard">
          Retour au dashboard
        </a>
      </div>
    </div>

    <!-- Carte résumé ────────────────────────────────────────── -->
    <div class="card">
      <div class="sec-title" style="margin-bottom:1rem">Résumé</div>
      <div style="display:grid;gap:.8rem">
        <div style="padding:.85rem 1rem;background:var(--surface2);
                    border:1px solid var(--border);border-radius:10px">
          <div class="meta-lbl">Compte</div>
          <div class="meta-val"><?= ucfirst($role) ?></div>
        </div>
        <div style="padding:.85rem 1rem;background:var(--surface2);
                    border:1px solid var(--border);border-radius:10px">
          <div class="meta-lbl">Type de membre</div>
          <div class="meta-val"><?= escape($type_membre) ?></div>
        </div>
        <div style="padding:.85rem 1rem;background:var(--surface2);
                    border:1px solid var(--border);border-radius:10px">
          <div class="meta-lbl">Identifiant</div>
          <div class="meta-val">@<?= $login ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Formulaire de modification (masqué par défaut) ─────────── -->
  <div id="profil-edit-card" class="card" style="margin-top:1rem;display:none">
    <div style="margin-bottom:1rem">
      <div class="sec-title">Modifier mes informations</div>
      <div style="font-size:.82rem;color:var(--muted);margin-top:.15rem">
        Laissez les champs mot de passe vides si vous ne souhaitez pas le changer.
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Prénom</label>
        <input class="form-input" id="p-prenom" value="<?= $prenom ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Nom</label>
        <input class="form-input" id="p-nom" value="<?= $nom ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" id="p-email" type="email" value="<?= $email ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Sexe</label>
        <select class="form-input" id="p-sexe">
          <option value="M"     <?= $sexe === 'M'     ? 'selected' : '' ?>>Homme</option>
          <option value="F"     <?= $sexe === 'F'     ? 'selected' : '' ?>>Femme</option>
          <option value="Autre" <?= $sexe === 'Autre' ? 'selected' : '' ?>>Autre</option>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Date de naissance</label>
        <input class="form-input" id="p-dob" type="date" value="<?= $dob ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Type de membre</label>
        <select class="form-input" id="p-type-membre">
          <option value="habitant" <?= $type_membre === 'habitant' ? 'selected' : '' ?>>Habitant</option>
          <option value="père"     <?= $type_membre === 'père'     ? 'selected' : '' ?>>Père</option>
          <option value="mère"     <?= $type_membre === 'mère'     ? 'selected' : '' ?>>Mère</option>
          <option value="enfant"   <?= $type_membre === 'enfant'   ? 'selected' : '' ?>>Enfant</option>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Nouveau mot de passe</label>
        <input class="form-input" id="p-mdp" type="password"
               placeholder="Laisser vide pour ne pas changer">
      </div>
      <div class="form-group">
        <label class="form-label">Confirmer le mot de passe</label>
        <input class="form-input" id="p-mdp2" type="password"
               placeholder="Répéter le nouveau mot de passe">
      </div>
    </div>

    <div style="display:flex;gap:.75rem;flex-wrap:wrap;justify-content:flex-end;margin-top:.5rem">
      <button class="btn btn-outline" onclick="toggleEditProfil(false)">Annuler</button>
      <button class="btn btn-primary" onclick="saveProfil()">Enregistrer les modifications</button>
    </div>
  </div>

</div>

<script>
/* Affiche ou cache le formulaire de modification */
function toggleEditProfil(force = null) {
    const card      = document.getElementById('profil-edit-card');
    const shouldShow = force === null ? card.style.display === 'none' : !!force;
    card.style.display = shouldShow ? 'block' : 'none';
    if (shouldShow) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/* Envoie les modifications à l'API */
async function saveProfil() {
    const data = await api('update_profil', {
        prenom:      document.getElementById('p-prenom').value.trim(),
        nom:         document.getElementById('p-nom').value.trim(),
        email:       document.getElementById('p-email').value.trim(),
        sexe:        document.getElementById('p-sexe').value,
        dob:         document.getElementById('p-dob').value,
        type_membre: document.getElementById('p-type-membre').value,
        mdp:         document.getElementById('p-mdp').value,
        mdp2:        document.getElementById('p-mdp2').value,
    });
    toast(data.msg, data.ok);
    if (data.ok) setTimeout(() => location.reload(), 700);
}
</script>

<style>
@media (max-width: 860px) {
    .profil-grid { grid-template-columns: 1fr !important; }
}
</style>
