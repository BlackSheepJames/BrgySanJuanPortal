<?php
declare(strict_types=1);

const APP_NAME = 'San Juan Barangay Management';
const DB_HOST = '127.0.0.1';
const DB_NAME = 'brgy_sanjuan';
const DB_USER = 'root';
const DB_PASS = '';
const APP_TIMEZONE = 'Asia/Manila';

date_default_timezone_set(APP_TIMEZONE);

function password_meets_policy(string $password): bool
{
    return mb_strlen($password) >= 8
        && preg_match('/[A-Z]/', $password) === 1
        && preg_match('/[0-9]/', $password) === 1
        && preg_match('/[^A-Za-z0-9\s]/', $password) === 1;
}

function is_gmail_address(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false
        && str_ends_with(strtolower($email), '@gmail.com');
}

/** Return the shared database connection and apply compatible schema upgrades. */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    upgrade_account_schema($pdo);

    return $pdo;
}

/** Upgrade databases created by earlier versions without replacing user data. */
function upgrade_account_schema(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $users = $pdo->query(
        "SELECT COLUMN_NAME, COLUMN_TYPE
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'"
    )->fetchAll();

    if (!$users) {
        $done = true;
        return;
    }

    $columns = [];
    foreach ($users as $column) {
        $columns[$column['COLUMN_NAME']] = $column['COLUMN_TYPE'];
    }

    if (!isset($columns['email'])) {
        $pdo->exec('ALTER TABLE users ADD COLUMN email VARCHAR(160) NULL UNIQUE AFTER username');
    }

    if (!isset($columns['phone'])) {
        $pdo->exec('ALTER TABLE users ADD COLUMN phone VARCHAR(40) NULL AFTER email');
    }

    if (isset($columns['name']) && str_contains(strtolower($columns['name']), 'varchar(120)')) {
        $pdo->exec('ALTER TABLE users MODIFY name VARCHAR(255) NOT NULL');
    }

    $roleColumn = strtolower((string) ($columns['role'] ?? ''));
    foreach (['als_teacher', 'resident'] as $role) {
        if (!str_contains($roleColumn, "'$role'")) {
            $pdo->exec(
                "ALTER TABLE users MODIFY role
                 ENUM('admin','captain','secretary','health_worker','tanod','als_teacher','resident')
                 NOT NULL DEFAULT 'resident'"
            );
            break;
        }
    }

    $residentUserId = $pdo->query(
        "SELECT COLUMN_NAME
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'residents'
           AND COLUMN_NAME = 'user_id'"
    )->fetchColumn();

    if (!$residentUserId) {
        $pdo->exec(
            'ALTER TABLE residents ADD COLUMN user_id INT UNSIGNED NULL UNIQUE,
             ADD CONSTRAINT fk_residents_user FOREIGN KEY (user_id)
             REFERENCES users(id) ON DELETE SET NULL'
        );
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS announcements (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(180) NOT NULL,
            body TEXT NOT NULL,
            starts_at DATETIME NOT NULL,
            ends_at DATETIME NULL,
            location VARCHAR(180) NULL,
            author_id INT UNSIGNED NULL,
            author_role ENUM('admin','captain','secretary','health_worker','tanod','als_teacher') NOT NULL,
            status ENUM('Published','Cancelled') NOT NULL DEFAULT 'Published',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_announcements_start (starts_at),
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $done = true;
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    session_name('brgy_sanjuan');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);

    session_start();
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function current_user(): ?array
{
    start_session();

    if (!empty($_SESSION['user'])) {
        $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);

        if ($lastActivity && time() - $lastActivity > 1800) {
            unset($_SESSION['user'], $_SESSION['csrf']);
            return null;
        }

        $_SESSION['last_activity'] = time();
    }

    return $_SESSION['user'] ?? null;
}

function audit(
    PDO $pdo,
    string $action,
    string $entity,
    string|int|null $entityId = null,
    array $details = []
): void {
    $user = current_user();
    $statement = $pdo->prepare(
        'INSERT INTO audit_logs (user_id, username, action, entity, entity_id, details, ip_address)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->execute([
        $user['id'] ?? null,
        $user['username'] ?? null,
        $action,
        $entity,
        $entityId,
        json_encode($details, JSON_UNESCAPED_UNICODE),
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

function require_auth(): array
{
    $user = current_user();

    if (!$user) {
        json_response(['error' => 'Please sign in to continue.'], 401);
    }

    $statement = db()->prepare(
        'SELECT id, name, username, role, active FROM users WHERE id = ? LIMIT 1'
    );
    $statement->execute([(int) $user['id']]);
    $storedUser = $statement->fetch();

    if (!$storedUser || !$storedUser['active']) {
        unset($_SESSION['user'], $_SESSION['csrf']);
        json_response(['error' => 'This account is no longer active.'], 401);
    }

    $user = [
        'id' => (int) $storedUser['id'],
        'name' => $storedUser['name'],
        'username' => $storedUser['username'],
        'role' => $storedUser['role'],
    ];
    $_SESSION['user'] = $user;

    return $user;
}

function require_roles(array $roles): array
{
    $user = require_auth();

    if (!in_array($user['role'], $roles, true)) {
        json_response(['error' => 'Your account does not have access to this action.'], 403);
    }

    return $user;
}
