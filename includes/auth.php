<?php
/**
 * AutoPulse — admin authentication & authorization.
 * Server-side verification on every admin request.
 */

if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

function current_user(): ?array
{
    if (empty($_SESSION['admin_id'])) return null;
    static $user = null;
    if ($user === null) {
        $st = db()->prepare('SELECT id, username, email, role, status FROM users WHERE id = ? AND status = 1');
        $st->execute([$_SESSION['admin_id']]);
        $user = $st->fetch() ?: false;
    }
    return $user ?: null;
}

function is_admin(): bool
{
    return current_user() !== null;
}

/** Pages restricted to full administrators (editors get read/write on content only). */
function admin_can(string $cap): bool
{
    $u = current_user();
    if (!$u) return false;
    if ($u['role'] === 'admin') return true;
    return !in_array($cap, ['users', 'settings', 'ads', 'seo', 'backup'], true);
}

function require_admin(string $cap = ''): void
{
    if (!is_admin()) {
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect('admin/login.php');
    }
    if ($cap !== '' && !admin_can($cap)) {
        http_response_code(403);
        exit('<h1>403 — Access denied</h1><p>Your account does not have permission for this area. <a href="' . e(url('admin/dashboard.php')) . '">Back to dashboard</a></p>');
    }
}

/** Verifies credentials. Rate limiting is enforced by the caller (file based, per IP). */
function attempt_login(string $username, string $password): ?array
{
    $st = db()->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 1');
    $st->execute([$username, $username]);
    $user = $st->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        return $user;
    }
    return null;
}

function login_user(array $user): void
{
    session_regenerate_id(true);                       // prevent session fixation
    $_SESSION['admin_id'] = (int)$user['id'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
