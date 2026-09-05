<?php
/* AutoPulse — dynamic robots.txt (rewritten from /robots.txt by .htaccess) */
define('APP_RUNNING', true);
require __DIR__ . '/config/config.php';
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/models.php';

header('Content-Type: text/plain; charset=utf-8');

if (setting('robots_custom') !== null && setting('robots_custom') !== '') {
    // Admin-managed override (Admin → SEO → Robots.txt)
    echo setting('robots_custom') . "\nSitemap: " . abs_url('sitemap.xml') . "\n";
    return;
}

echo "User-agent: *\n";
echo "Disallow: /admin/\n";
echo "Disallow: /api/\n";
echo "Disallow: /cache/\n";
echo "Disallow: /config/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /search\n";
echo "\n";
echo "Sitemap: " . abs_url('sitemap.xml') . "\n";
