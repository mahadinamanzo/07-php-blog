<?php
declare(strict_types=1);

function current_admin(): ?array
{
    return isset($_SESSION['admin']) && is_array($_SESSION['admin'])
        ? $_SESSION['admin']
        : null;
}

function require_admin(): void
{
    $now = time();
    $lastActivity = (int) ($_SESSION['admin_last_activity'] ?? 0);
    $authenticatedAt = (int) ($_SESSION['admin_authenticated_at'] ?? 0);
    if (
        current_admin() !== null
        && $lastActivity > 0
        && $authenticatedAt > 0
        && ($now - $lastActivity) <= 1800
        && ($now - $authenticatedAt) <= 28800
    ) {
        $_SESSION['admin_last_activity'] = $now;
        return;
    }

    if (current_admin() !== null) {
        logout_admin();
        session_start();
    }
    if (current_admin() === null) {
        flash('error', 'Please sign in to continue.');
        redirect('admin/login.php');
    }
}

function authenticate_admin(string $email, string $password): bool
{
    $statement = db()->prepare('SELECT id, name, email, password_hash FROM admins WHERE email = :email LIMIT 1');
    $statement->execute(['email' => mb_strtolower(trim($email), 'UTF-8')]);
    $admin = $statement->fetch();
    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        return false;
    }

    if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
        $update = db()->prepare('UPDATE admins SET password_hash = :hash WHERE id = :id');
        $update->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $admin['id']]);
    }

    session_regenerate_id(true);
    unset($admin['password_hash']);
    $_SESSION['admin'] = $admin;
    $_SESSION['admin_authenticated_at'] = time();
    $_SESSION['admin_last_activity'] = time();
    return true;
}

function logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}
