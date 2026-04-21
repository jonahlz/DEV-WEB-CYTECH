<?php
// ============================================================
// CONFIG – LumiHome
// ============================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'lumihome');
define('DB_USER', 'root');        // ← adapter selon ton env
define('DB_PASS', '');            // ← adapter selon ton env
define('DB_PORT', 3306);

define('SITE_NAME', 'LumiHome');
define('PRIX_KWH',  0.1740);     // €/kWh (tarif réglementé 2025)

session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /lumihome/index.php?page=login');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /lumihome/index.php?page=dashboard');
        exit;
    }
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function escape(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
