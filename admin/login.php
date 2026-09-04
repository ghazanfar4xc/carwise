<?php
/** AutoPulse admin — login (rate-limited, CSRF-protected). */
define('APP_RUNNING', true);
require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/config/database.php';
require dirname(__DIR__) . '/includes/functions.php';
require dirname(__DIR__) . '/includes/models.php';
require dirname(__DIR__) . '/includes/auth.php';

date_default_timezone_set(SITE_TIMEZONE);
error_reporting(0);

/* Cookieless preview support: pin the session to a hidden form token so the
 * login POST can be verified even when the iframe drops the session cookie. */
$loginSid = (string)($_POST['login_sid'] ?? '');
if (!preg_match('/^[a-f0-9]{32}$/', $loginSid)) $loginSid = bin2hex(random_bytes(16));
session_id('lg' . md5($loginSid));
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
$GLOBALS['site_settings'] = load_settings();
if (is_admin() && !isset($_GET['force'])) redirect('admin/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Security token expired — please try again.';
    } elseif (!rate_limit('admin_login', 5, 900)) {
        $error = 'Too many failed attempts. Please wait 15 minutes before trying again.';
    } else {
        $user = attempt_login(post('username'), (string)($_POST['password'] ?? ''));
        if ($user) {
            login_user($user);
            $to = $_SESSION['intended_url'] ?? '';
            unset($_SESSION['intended_url']);
            if ($to === '' || !str_starts_with($to, url(''))) $to = admin_url('dashboard.php');
            // development preview: continue the session via token (cookieless iframes)
            if (APP_ENV === 'development' && !str_contains($to, 'apt=')) {
                $t = apt_create((int)$user['id']);
                $to .= (str_contains($to, '?') ? '&' : '?') . 'apt=' . $t;
            }
            header('Location: ' . $to);
            exit;
        }
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Log in — <?= e(setting('site_name', 'AutoPulse')) ?></title>
<link rel="icon" href="<?= e(url(setting('favicon') ?: 'assets/images/favicon.svg')) ?>" type="image/svg+xml">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="login-body">
<div class="login-card">
    <?php render_logo('logo-svg logo-login', 34) ?>
    <h1>Welcome back</h1>
    <p class="login-sub">Sign in to manage <?= e(setting('site_name', 'AutoPulse')) ?></p>

    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>

    <form method="post" action="">
        <?= csrf_field() ?>
        <input type="hidden" name="login_sid" value="<?= e($loginSid) ?>">
        <div class="form-field">
            <label for="l-user">Username or email</label>
            <input type="text" id="l-user" name="username" class="input" required autofocus autocomplete="username">
        </div>
        <div class="form-field">
            <label for="l-pass">Password</label>
            <input type="password" id="l-pass" name="password" class="input" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Log in</button>
    </form>
    <a class="login-home" href="<?= e(url('')) ?>">← Back to website</a>
</div>
</body>
</html>
