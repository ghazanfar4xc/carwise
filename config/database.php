<?php
/**
 * AutoPulse — PDO connection (singleton).
 * Prepared statements are used everywhere in the project.
 */

if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('[AutoPulse] DB connection failed: ' . $e->getMessage());
            if (APP_ENV === 'development') {
                http_response_code(500);
                exit('Database connection failed: ' . htmlspecialchars($e->getMessage())
                    . '<br>Check config/config.php');
            }
            http_response_code(503);
            exit('The site is temporarily unavailable. Please try again shortly.');
        }
    }
    return $pdo;
}
