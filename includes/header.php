<?php
/**
 * AutoPulse — site header & navigation (Modules 06, 21, 25).
 * Requires: $SEO set via seo_set() before include (optional).
 */
if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

$siteName  = setting('site_name', 'AutoPulse');
$tagline   = setting('tagline');
$menu      = menu_tree('main');
$topCats   = get_categories('article');
$themeMode = setting('theme_mode', 'auto');
$fallback  = $themeMode === 'auto'
    ? '(matchMedia("(prefers-color-scheme: dark)").matches?"dark":"light")'
    : json_encode($themeMode); // '"light"' or '"dark"'
$themeInit = '<script>(function(){var t;try{t=localStorage.getItem("theme")}catch(e){}if(!t){t=' . $fallback . '}document.documentElement.dataset.theme=t})();</script>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?= $themeInit ?>
<?php seo_render(); ?>
<link rel="icon" href="<?= e(url(setting('favicon') ?: 'assets/images/favicon.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/main.css')) ?>">
<?= theme_custom_css() /* Admin → Appearance overrides */ ?>
<noscript><style>.reveal, .reveal > * { opacity: 1 !important; transform: none !important; }</style></noscript>
<?php if (setting('custom_css')): ?><style><?= setting('custom_css') /* admin-controlled, trusted */ ?></style><?php endif; ?>
<?php if (setting('google_analytics_id')): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e(setting('google_analytics_id')) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e(setting('google_analytics_id')) ?>');</script>
<?php endif; ?>
<?= setting('header_scripts') /* admin-controlled */ ?>
</head>
<body data-base="<?= e(url('')) ?>">
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header" id="site-header">
    <div class="container header-bar">
        <a href="<?= e(url('')) ?>" class="logo" aria-label="<?= e($siteName) ?> — home">
            <?php render_logo('logo-svg logo-header', 36) ?>
        </a>

        <nav class="nav-links" aria-label="Main navigation" id="nav-links">
            <ul>
                <?php foreach ($menu as $item): ?>
                    <li class="<?= $item['children'] ? 'has-dropdown' : '' ?>">
                        <?php if ($item['children']): ?>
                            <button type="button" class="nav-drop-btn" aria-expanded="false" aria-haspopup="true"><?= e($item['label']) ?> <span class="caret" aria-hidden="true">▾</span></button>
                            <ul class="dropdown">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li><a href="<?= e(str_starts_with($child['url'], 'http') ? $child['url'] : url($child['url'])) ?>"><?= e($child['label']) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <a href="<?= e(str_starts_with($item['url'], 'http') ? $item['url'] : url($item['url'])) ?>"><?= e($item['label']) ?></a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="header-actions">
            <button type="button" class="icon-btn" id="search-open" aria-label="Search">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </button>
            <button type="button" class="icon-btn theme-toggle" aria-label="Toggle dark mode">
                <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/></svg>
            </button>
            <a href="<?= e(url('compare')) ?>" class="btn btn-primary btn-sm header-cta">Compare Cars</a>
            <button type="button" class="icon-btn nav-burger" id="nav-burger" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-nav">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>
</header>

<?php /* Category quick-strip: all car + article categories, site-wide (admin-managed) */
$carCats    = get_categories('car');
$basePath   = rtrim((string)parse_url(url(''), PHP_URL_PATH), '/');
$reqPath    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($basePath !== '' && str_starts_with($reqPath, $basePath)) { $reqPath = substr($reqPath, strlen($basePath)); }
$activeSlug = preg_match('~^/category/([a-z0-9-]+)~i', $reqPath, $m) ? $m[1] : null;
?>
<div class="cat-strip">
    <div class="container cat-strip-inner">
        <span class="cat-strip-label">Browse</span>
        <nav class="cat-strip-links" aria-label="All categories">
            <?php foreach ($carCats as $c): ?>
                <a class="cat-chip<?= $activeSlug === $c['slug'] ? ' active' : '' ?>" href="<?= e(category_url($c)) ?>"><?= e($c['name']) ?></a>
            <?php endforeach; ?>
            <span class="cat-strip-sep" aria-hidden="true"></span>
            <?php foreach ($topCats as $c): ?>
                <a class="cat-chip<?= $activeSlug === $c['slug'] ? ' active' : '' ?>" href="<?= e(category_url($c)) ?>"><?= e($c['name']) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</div>
<?php ad_slot('header', 'ad-header'); ?>

<!-- Mobile navigation drawer -->
<div class="drawer-backdrop" id="drawer-backdrop" hidden></div>
<nav class="drawer" id="mobile-nav" aria-label="Mobile navigation" hidden>
    <div class="drawer-head">
        <span class="logo-text"><?= e($siteName) ?></span>
        <button type="button" class="icon-btn" id="nav-close" aria-label="Close menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
    </div>
    <ul class="drawer-links">
        <?php foreach ($menu as $item): ?>
            <li>
                <?php if ($item['children']): ?>
                    <details>
                        <summary><?= e($item['label']) ?></summary>
                        <ul>
                            <?php foreach ($item['children'] as $child): ?>
                                <li><a href="<?= e(str_starts_with($child['url'], 'http') ? $child['url'] : url($child['url'])) ?>"><?= e($child['label']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php else: ?>
                    <a href="<?= e(str_starts_with($item['url'], 'http') ? $item['url'] : url($item['url'])) ?>"><?= e($item['label']) ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="drawer-foot">
        <a href="<?= e(url('compare')) ?>" class="btn btn-primary btn-block">Compare Cars</a>
    </div>
</nav>

<!-- Search overlay -->
<div class="search-overlay" id="search-overlay" role="dialog" aria-modal="true" aria-label="Search" hidden>
    <div class="search-panel">
        <div class="search-box">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" id="search-input" placeholder="Search cars, brands, articles…" autocomplete="off" aria-label="Search query">
            <button type="button" class="btn btn-sm btn-ghost" id="search-close">Esc</button>
        </div>
        <div class="search-suggest" id="search-suggest" aria-live="polite"></div>
    </div>
</div>

<main id="main">
<?php if (setting('maintenance_mode') === '1' && is_admin()): ?>
    <div class="alert alert-warning container mt-2">Maintenance mode is ON — visitors currently see a maintenance page.</div>
<?php endif; ?>
