<?php
/**
 * AutoPulse — router for PHP's built-in dev server (no Apache needed):
 *   php -S localhost:8000 router-dev.php
 * Not used in production (Apache + .htaccess handles routing there).
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

// Serve real static files directly (css/js/images/uploads)
if ($path !== '/' && is_file($file) && !str_ends_with($path, '.php')) {
    return false;
}
// Real PHP files (admin, api) are executed by the built-in server itself
if ($path !== '/' && is_file($file)) {
    return false;
}

$_SERVER['PATH_INFO'] = $path;
require __DIR__ . '/index.php';
