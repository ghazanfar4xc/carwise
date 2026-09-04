<?php
/**
 * AutoPulse — central configuration.
 * THIS IS THE ONLY FILE WHERE DATABASE CREDENTIALS LIVE.
 * Change these values when deploying (localhost → InfinityFree).
 */

// 'development' shows errors; 'production' shows friendly error pages.
define('APP_ENV', 'development');

// Base URL path of the app. '' if the site is at the domain root (InfinityFree htdocs root).
// Use '/subfolder' if installed in a subdirectory.
define('BASE_URL', '');

// ── Database (Local) ────────────────────────────────────────────────
// InfinityFree example:
//   DB_HOST: sqlXXX.infinityfree.com   DB_NAME: ifX_12345678_autopulse
//   DB_USER: ifX_12345678              DB_PASS: (the password set in the client area)
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'autopulse');
define('DB_USER', 'autopulse');
define('DB_PASS', 'autopulse');
define('DB_CHARSET', 'utf8mb4');

// Site defaults (most values can be overridden from Admin → Settings)
define('SITE_TIMEZONE', 'Asia/Karachi');
define('DEFAULT_CURRENCY', 'PKR');
define('ITEMS_PER_PAGE', 12);
define('UPLOAD_MAX_BYTES', 5 * 1024 * 1024); // 5 MB per image
