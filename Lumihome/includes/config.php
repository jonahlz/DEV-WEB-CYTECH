<?php
// ============================================================
// LumiHome — Configuration générale, connexion BDD & fonctions
// ============================================================

// ── Base de données ─────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'lumihome');
define('DB_USER', 'root');   // ← adapter selon votre environnement
define('DB_PASS', '');       // ← adapter selon votre environnement
define('DB_PORT', 3306);

// ── Constante applicative ────────────────────────────────────
define('PRIX_KWH', 0.1740);  // €/kWh — tarif réglementé 2025

// ── Session ─────────────────────────────────────────────────
session_start();

// ── Connexion PDO (singleton) ────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            DB_HOST, DB_PORT, DB_NAME
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

// ── Helpers authentification ─────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /Lumihome/index.php');
        exit;
    }
}

// ── Réponse JSON ─────────────────────────────────────────────
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ── Sécurité HTML ────────────────────────────────────────────
function escape(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ── Affichage fiche détaillée d'un membre ────────────────────
// Utilisée dans pages/profil_details.php
function afficherProfilDetaille(array $profil): string {
    $age    = (new DateTime())->diff(new DateTime($profil['date_naissance']))->y;
    $prenom = escape($profil['prenom']);
    $login  = escape($profil['login']);
    $email  = escape($profil['email']);
    $sexe   = escape($profil['sexe']);
    $depuis = escape($profil['created_at']);

    return "
        <div class='card'>
            <h2 style='font-family:Syne,sans-serif;font-size:1.4rem;font-weight:800;margin-bottom:1rem'>
                {$prenom} <span style='color:var(--muted);font-weight:400;font-size:1rem'>@{$login}</span>
            </h2>
            <img src='https://randomuser.me/api/portraits/men/75.jpg'
                 alt='Avatar'
                 style='width:72px;height:72px;border-radius:50%;border:2px solid var(--border);margin-bottom:1rem'>
            <div style='display:grid;grid-template-columns:1fr 1fr;gap:.75rem 1.5rem'>
                <div><div class='meta-lbl'>Email</div><div class='meta-val'>{$email}</div></div>
                <div><div class='meta-lbl'>Sexe</div><div class='meta-val'>{$sexe}</div></div>
                <div><div class='meta-lbl'>Âge</div><div class='meta-val'>{$age} ans</div></div>
                <div><div class='meta-lbl'>Membre depuis</div><div class='meta-val'>{$depuis}</div></div>
            </div>
        </div>
    ";
}

// ── Envoi de l'email de vérification via PHPMailer ───────────
// Utilisée dans pages/inscription.php après chaque inscription
function envoyerEmailDeVerification(string $adresseEmail, string $token): bool {
    $base = __DIR__ . '/../PHPMailer-master/PHPMailer-master/src/';
    require_once $base . 'PHPMailer.php';
    require_once $base . 'SMTP.php';
    require_once $base . 'Exception.php';

    $lien = 'http://localhost/Lumihome/pages/verification.php?token=' . $token;
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mariebah574@gmail.com';
        $mail->Password   = 'duqj zohc fapz lrhu';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('mariebah574@gmail.com', 'LumiHome');
        $mail->addAddress($adresseEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Confirmez votre compte LumiHome';
        $mail->Body = "
            <div style='font-family:Arial;text-align:center;'>
                <h2>Bienvenue sur LumiHome 💡</h2>
                <img src='https://cdn-icons-png.flaticon.com/512/295/295128.png' width='100' alt=''>
                <p>Cliquez ci-dessous pour confirmer votre compte :</p>
                <a href='{$lien}'
                   style='display:inline-block;padding:12px 20px;background:#f5c842;
                          color:#0d0f14;text-decoration:none;border-radius:5px;font-weight:600;'>
                    Confirmer le compte
                </a>
                <p style='margin-top:20px;color:#888;font-size:.85rem;'>
                    Si vous n'avez pas créé ce compte, ignorez cet email.
                </p>
            </div>
        ";

        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        return false;
    }
}
