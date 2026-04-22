<?php
/**
 * 1st Time Runners — config.php
 * ─────────────────────────────
 * EDIT the four DB_* constants below with your Hostinger MySQL credentials.
 * In Hostinger cPanel → MySQL Databases → create a DB, user, and assign them.
 */

// ── DATABASE ────────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');          // Almost always 'localhost' on Hostinger
define('DB_NAME', 'runners_db'); // local database name
define('DB_USER', 'root');       // default XAMPP user
define('DB_PASS', '');           // default XAMPP password is empty

// ── ADMIN CREDENTIALS ───────────────────────────────────────────────────────
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'runners2025');

// ── UPLOADS ─────────────────────────────────────────────────────────────────
// Mirrors Flask's static/uploads path so admin.html JS (/static/uploads/file) works unchanged
define('UPLOAD_DIR',  __DIR__ . '/static/uploads/');
define('UPLOAD_URL',  '/static/uploads/');
define('ALLOWED_EXT', ['png', 'jpg', 'jpeg', 'gif']);

// ── PDO CONNECTION ──────────────────────────────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    // Try multiple common local configurations automatically
    $configs = [
        ['host' => '127.0.0.1', 'user' => 'root', 'pass' => ''],
        ['host' => 'localhost', 'user' => 'root', 'pass' => ''],
        ['host' => '127.0.0.1:3307', 'user' => 'root', 'pass' => ''],
        ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'root'],
    ];

    $lastError = '';
    foreach ($configs as $c) {
        try {
            $dsn = "mysql:host={$c['host']};dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            return $pdo; // Success!
        } catch (PDOException $e) {
            $lastError = $e->getMessage();
            continue; // Try next config
        }
    }

    // If all fail, throw the last error
    throw new PDOException("Connection failed after multiple attempts. Last error: " . $lastError);
}

// ── RESPONSE HELPERS ────────────────────────────────────────────────────────
function json_out(mixed $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function get_json(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

// ── AUTH GUARDS ─────────────────────────────────────────────────────────────
function is_admin(): bool {
    return !empty($_SESSION['admin']);
}

function is_runner(): bool {
    return !empty($_SESSION['runner_id']);
}

function require_admin(): void {
    if (!is_admin()) json_out(['error' => 'Unauthorized'], 401);
}

function require_runner(): void {
    if (!is_runner()) json_out(['error' => 'Runner session required'], 401);
}

// ── UPLOAD HELPER ────────────────────────────────────────────────────────────
function allowed_file(string $filename): bool {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ALLOWED_EXT, true);
}

function safe_filename(string $original, int $runner_id): string {
    $ext  = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $base = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($original));
    return 'proof_' . $runner_id . '_' . time() . '_' . $base;
}
