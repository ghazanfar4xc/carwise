<?php
/**
 * AutoPulse admin — shared bootstrap: config, session, CSRF guard.
 * Every admin page starts with:  require __DIR__ . '/includes/bootstrap.php';
 */
define('APP_RUNNING', true);

require dirname(__DIR__, 2) . '/config/config.php';
require dirname(__DIR__, 2) . '/config/database.php';
require dirname(__DIR__, 2) . '/includes/functions.php';
require dirname(__DIR__, 2) . '/includes/models.php';
require dirname(__DIR__, 2) . '/includes/auth.php';

date_default_timezone_set(SITE_TIMEZONE);
error_reporting(APP_ENV === 'development' ? E_ALL : 0);
ini_set('display_errors', APP_ENV === 'development' ? '1' : '0');
@ini_set('error_log', dirname(__DIR__, 2) . '/cache/error.log');

/* ── Sessions ──────────────────────────────────────────────────────
 * Preview mode (development only): if a valid ?apt= token is present,
 * pin the PHP session id to it so the admin panel stays logged in
 * inside sandboxed iframes where cookies are blocked. */
$APT = (APP_ENV === 'development') ? (string)($_GET['apt'] ?? '') : '';
if ($APT !== '' && apt_valid($APT)) {
    define('PREVIEW_APT', $APT);
    session_id('apt' . md5($APT));
} else {
    define('PREVIEW_APT', '');
}

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

// adopt the token's user into this (possibly fresh) session
if (PREVIEW_APT !== '' && empty($_SESSION['admin_id'])) {
    $aptUser = apt_user(PREVIEW_APT);
    if ($aptUser > 0) $_SESSION['admin_id'] = $aptUser;
}

$GLOBALS['site_settings'] = load_settings();

// Every admin POST must carry a valid CSRF token (Module 26)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_require();
}

/** Escaped old form value: row value → POST → default (sticky forms). */
function fv(?string $postKey, $rowValue = null, string $default = ''): string
{
    if (isset($_POST[$postKey])) return e((string)$_POST[$postKey]);
    if ($rowValue !== null && $rowValue !== '') return e((string)$rowValue);
    return e($default);
}
