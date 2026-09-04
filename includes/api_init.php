<?php
/**
 * AutoPulse — shared bootstrap for /api endpoints.
 * JSON in, JSON out. POST endpoints require a valid CSRF token.
 */
define('APP_RUNNING', true);

require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/config/database.php';
require dirname(__DIR__) . '/includes/functions.php';
require dirname(__DIR__) . '/includes/models.php';

date_default_timezone_set(SITE_TIMEZONE);
error_reporting(0); // APIs never leak internals (Module 48)

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
$GLOBALS['site_settings'] = load_settings();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_require();
}
