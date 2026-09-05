<?php
/**
 * AutoPulse — front controller: bootstraps the app and routes clean URLs.
 *
 * Routes:
 *   /                          home
 *   /cars                      car catalogue (filters via GET)
 *   /cars/{brand}/{model}      car detail
 *   /brands  /brands/{slug}    brand index / brand page
 *   /articles  /articles/{slug}
 *   /category/{slug}
 *   /search?q=                 search results
 *   /compare                   comparison tool
 *   /contact  /{page-slug}     contact & CMS pages
 */

define('APP_RUNNING', true);
define('APP_START', microtime(true));

require __DIR__ . '/config/config.php';
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/models.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/seo.php';
require __DIR__ . '/includes/breadcrumbs.php';
require __DIR__ . '/includes/pagination.php';
require __DIR__ . '/includes/cards.php';
require __DIR__ . '/includes/ads.php';

date_default_timezone_set(SITE_TIMEZONE);

/* ── Sessions (secure defaults) ────────────────────────────────────── */
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
session_start();

/* ── Error handling (Module 48) ────────────────────────────────────── */
error_reporting(APP_ENV === 'development' ? E_ALL : 0);
ini_set('display_errors', APP_ENV === 'development' ? '1' : '0');
ini_set('log_errors', '1');
@ini_set('error_log', __DIR__ . '/cache/error.log');

set_exception_handler(function (Throwable $e): void {
    error_log('[AutoPulse] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (APP_ENV === 'development') {
        echo '<pre style="padding:20px;font:14px/1.6 monospace">' . e($e->getMessage()) . "\n\n" . e($e->getTraceAsString()) . '</pre>';
    } elseif (is_file(__DIR__ . '/pages/500.php')) {
        $SEO = ['title' => 'Server Error', 'robots' => 'noindex, follow'];
        include __DIR__ . '/pages/500.php';
    } else {
        echo 'Something went wrong. Please try again later.';
    }
    exit;
});

/* ── Settings & maintenance mode ───────────────────────────────────── */
$GLOBALS['site_settings'] = load_settings();

if (setting('maintenance_mode') === '1' && !is_admin() && PHP_SAPI !== 'cli') {
    http_response_code(503);
    header('Retry-After: 3600');
    $SEO = ['title' => 'Maintenance', 'robots' => 'noindex, nofollow'];
    include __DIR__ . '/pages/503.php';
    exit;
}

/* ── Resolve current path ──────────────────────────────────────────── */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (BASE_URL !== '' && str_starts_with($uri, BASE_URL)) $uri = substr($uri, strlen(BASE_URL));
$path = trim(rawurldecode($uri), '/');

/* Google Search Console HTML-file verification (Admin → SEO) */
$gscFile = setting('gsc_file_name');
if ($gscFile !== '' && $path === trim($gscFile, '/')) {
    header('Content-Type: text/html; charset=utf-8');
    echo 'google-site-verification: ' . $gscFile;
    exit;
}

/* Managed redirects (Module 20) — exact path matches (with or without leading slash) */
if ($path !== '' && !str_contains($path, '/api/')) {
    try {
        $st = db()->prepare('SELECT new_path, status_code FROM redirects WHERE old_path IN (?, ?) LIMIT 1');
        $st->execute([$path, '/' . $path]);
        if ($redir = $st->fetch()) {
            header('Location: ' . url($redir['new_path']), true, (int)$redir['status_code'] ?: 301);
            exit;
        }
    } catch (Throwable $ignored) {
    }
}

if (isset($_GET['error'])) {
    http_response_code((int)$_GET['error'] === 403 ? 403 : 404);
    $SEO = ['title' => (int)$_GET['error'] === 403 ? 'Forbidden' : 'Page not found', 'robots' => 'noindex, follow'];
    include __DIR__ . '/pages/404.php';
    return;
}

/* ── Route matching ────────────────────────────────────────────────── */
$seg = $path === '' ? [] : explode('/', $path);
$page = null;
$params = [];

switch ($seg[0] ?? '') {
    case '':
        $page = 'home';
        break;

    case 'cars':
        if (count($seg) === 1) $page = 'cars';
        elseif (count($seg) === 3) { $page = 'car'; $params = ['brand' => $seg[1], 'slug' => $seg[2]]; }
        break;

    case 'brands':
    case 'brand': // alias
        if (count($seg) === 1) $page = 'brands';
        elseif (count($seg) === 2) { $page = 'brand'; $params = ['slug' => $seg[1]]; }
        break;

    case 'articles':
    case 'blog': // legacy alias — also seeded as a redirect
        if (count($seg) === 1) $page = 'articles';
        elseif (count($seg) === 2) { $page = 'article'; $params = ['slug' => $seg[1]]; }
        break;

    case 'category':
        if (count($seg) === 2) { $page = 'category'; $params = ['slug' => $seg[1]]; }
        break;

    case 'search':
        $page = 'search';
        break;

    case 'compare':
        $page = 'compare';
        if (count($seg) === 2) { $page = 'compare-seo'; $params = ['pair' => $seg[1]]; }
        break;

    case 'sitemap':
        $page = 'sitemap-page';
        break;

    case 'contact':
        $page = 'contact';
        break;

    case 'author':
        if (count($seg) === 2) { $page = 'author'; $params = ['slug' => $seg[1]]; }
        break;

    case 'page':
        if (count($seg) === 2) { $page = 'page'; $params = ['slug' => $seg[1]]; }
        break;

    default:
        // Bare slug → CMS page (e.g. /about-us, /privacy-policy)
        if (count($seg) === 1) { $page = 'page'; $params = ['slug' => $seg[0]]; }
}

if ($page === null) { // 404
    http_response_code(404);
    $SEO = ['title' => 'Page not found', 'robots' => 'noindex, follow'];
    include __DIR__ . '/pages/404.php';
    return;
}

include __DIR__ . '/pages/' . $page . '.php';
