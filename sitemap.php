<?php
/**
 * AutoPulse — dynamic sitemap (all URLs generated from the database, cached 6h).
 * Rewritten from /sitemap.xml by .htaccess.
 */
define('APP_RUNNING', true);
require __DIR__ . '/config/config.php';
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/models.php';

header('Content-Type: application/xml; charset=utf-8');

$urls = cache_get('sitemap', 21600);
if ($urls === null) {
    $urls = [
        ['loc' => abs_url('/'), 'priority' => '1.0'],
        ['loc' => abs_url('cars'), 'priority' => '0.9'],
        ['loc' => abs_url('brands'), 'priority' => '0.8'],
        ['loc' => abs_url('articles'), 'priority' => '0.9'],
        ['loc' => abs_url('compare'), 'priority' => '0.6'],
        ['loc' => abs_url('contact'), 'priority' => '0.4'],
    ];
    try {
        foreach (db()->query('SELECT slug, updated_at FROM brands WHERE status = 1')->fetchAll() as $b) {
            $urls[] = ['loc' => abs_url('brands/' . $b['slug']), 'priority' => '0.7', 'lastmod' => $b['updated_at']];
        }
        foreach (db()->query('SELECT c.slug, b.slug AS bslug, c.updated_at FROM car_models c JOIN brands b ON b.id = c.brand_id WHERE c.status = "published"')->fetchAll() as $c) {
            $urls[] = ['loc' => abs_url('cars/' . $c['bslug'] . '/' . $c['slug']), 'priority' => '0.8', 'lastmod' => $c['updated_at']];
        }
        foreach (db()->query('SELECT a.slug, a.updated_at FROM articles a WHERE ' . ARTICLE_LIVE)->fetchAll() as $a) {
            $urls[] = ['loc' => abs_url('articles/' . $a['slug']), 'priority' => '0.7', 'lastmod' => $a['updated_at']];
        }
        foreach (db()->query('SELECT slug, updated_at FROM categories WHERE status = 1')->fetchAll() as $c) {
            $urls[] = ['loc' => abs_url('category/' . $c['slug']), 'priority' => '0.6', 'lastmod' => $c['updated_at']];
        }
        foreach (db()->query('SELECT slug, updated_at FROM pages WHERE status = "published"')->fetchAll() as $p) {
            $urls[] = ['loc' => abs_url($p['slug']), 'priority' => '0.4', 'lastmod' => $p['updated_at']];
        }
        cache_set('sitemap', $urls);
    } catch (Throwable $e) {
        error_log('[AutoPulse] sitemap: ' . $e->getMessage());
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n    <loc>" . e($u['loc']) . "</loc>\n";
    if (!empty($u['lastmod'])) echo '    <lastmod>' . e(date('c', strtotime($u['lastmod']))) . "</lastmod>\n";
    echo '    <priority>' . $u['priority'] . "</priority>\n  </url>\n";
}
echo '</urlset>';
