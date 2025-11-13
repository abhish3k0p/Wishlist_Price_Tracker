<?php
require_once __DIR__ . '/mailer.php';
function require_login() {
    if (empty($_SESSION['user_id'])) {
        $base = env('APP_URL', '');
        $to = rtrim($base, '/') . '/public/login.php';
        header('Location: ' . $to);
        exit;
    }
}

function auth_register(PDO $db, string $name, string $email, string $password): bool {
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) return false;
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users(name, email, password_hash) VALUES (?, ?, ?)');
    $ok = $stmt->execute([$name, $email, $hash]);
    if ($ok) {
        sendWelcomeEmail(['email'=>$email, 'name'=>$name]);
    }
    return $ok;
}

function auth_login(PDO $db, string $email, string $password): ?array {
    $stmt = $db->prepare('SELECT id, name, email, password_hash, COALESCE(is_admin,0) as is_admin FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }
    return null;
}
