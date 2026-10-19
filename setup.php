<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
start_session();
$error = '';
$installed = false;
try {
    $root = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $check = $root->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME=?');
    $check->execute([DB_NAME]);
    if ($check->fetch()) {
        $exists = $root->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=" . $root->quote(DB_NAME) . " AND TABLE_NAME='users'")->fetchColumn();
        if ($exists) $installed = (int)$root->query('SELECT COUNT(*) FROM ' . DB_NAME . '.users')->fetchColumn() > 0;
    }
} catch (Throwable $e) { $error = 'Could not connect to MySQL. Start MySQL in XAMPP, then check the settings in config.php.'; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed && !$error) {
    $name = trim((string)($_POST['name'] ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');
    if (mb_strlen($name) < 2 || !preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username) || !password_meets_policy($password) || $password !== $confirm) {
        $error = 'Enter a name, a username (3–50 letters or numbers), and matching passwords with 8+ characters, an uppercase letter, a number, and a symbol.';
    } else {
        try {
            $schema = file_get_contents(__DIR__ . '/schema.sql');
            $schema = preg_replace('/^CREATE DATABASE.*?;\s*/im', '', $schema);
            $schema = preg_replace('/^USE\s+[^;]+;\s*/im', '', $schema);
            foreach (preg_split('/;\s*(?:\r?\n|$)/', trim($schema)) as $statement) {
                if (trim($statement) !== '') {
                    try { $root->exec($statement); }
                    catch (PDOException $e) { if (!str_starts_with(strtoupper(trim($statement)), 'CREATE TRIGGER') || !str_contains(strtolower($e->getMessage()), 'already exists')) throw $e; }
                }
            }
            $insert = $root->prepare('INSERT INTO ' . DB_NAME . '.users (name,username,password_hash,role) VALUES (?,?,?,?)');
            $insert->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), 'admin']);
            audit(db(), 'installed', 'system', 1, ['installer_username'=>$username]);
            header('Location: index.php'); exit;
        } catch (Throwable $e) { $error = 'Setup could not finish. Confirm MySQL is running and its account can create the database. Details: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'); }
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Setup · San Juan</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/app.css"></head><body class="setup-body"><main class="setup-card"><div class="brand-mark">SJ</div><p class="eyebrow">BARANGAY SAN JUAN</p><h1>Set up your workspace</h1><p class="muted">Create your administrator account to prepare your local records database.</p><?php if($error): ?><div class="alert"><?=htmlspecialchars($error, ENT_QUOTES, 'UTF-8')?></div><?php endif; ?><?php if($installed): ?><div class="alert success">This workspace is already set up. <a href="index.php">Go to sign in</a>.</div><?php elseif(!$error || $_SERVER['REQUEST_METHOD']==='POST'): ?><form method="post" class="stack-form"><label>Administrator name<input name="name" required minlength="2" placeholder="e.g. Maria Santos"></label><label>Username<input name="username" required minlength="3" maxlength="50" placeholder="admin"></label><label>Password<input name="password" type="password" required minlength="8" autocomplete="new-password" placeholder="8+ characters with uppercase, number, symbol"><small>Include an uppercase letter, a number, and a symbol.</small></label><label>Confirm password<input name="confirm" type="password" required minlength="8" autocomplete="new-password"></label><button class="primary-btn">Create workspace</button></form><?php endif; ?><p class="setup-note">Records stay in your local MySQL database. Use a unique password and keep regular backups.</p></main></body></html>
