<?php
/**
 * AutoPulse — SEO metadata + JSON-LD structured data.
 * Pages call seo_set() before including header.php.
 */

if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

$GLOBALS['SEO'] = [];
$GLOBALS['SEO_JSONLD'] = [];

function seo_set(array $opts): void
{
    $GLOBALS['SEO'] = array_merge($GLOBALS['SEO'], $opts);
}

function seo_jsonld(array $data): void
{
    $GLOBALS['SEO_JSONLD'][] = $data;
}

function seo_render(): void
{
    $s = array_merge([
        'title'       => '',
        'description' => setting('meta_description'),
        'canonical'   => $_SERVER['REQUEST_URI'] ?? '/',
        'robots'      => 'index, follow',
        'og_type'     => 'website',
        'og_image'    => setting('og_image') ?: 'uploads/general/hero.jpg',
        'og_title'    => '',
        'og_desc'     => '',
    ], $GLOBALS['SEO']);

    $siteName = setting('site_name', 'AutoPulse');
    $fullTitle = $s['title'] !== '' ? $s['title'] . ' | ' . $siteName : $siteName . (setting('tagline') ? ' — ' . setting('tagline') : '');
    if (empty($s['canonical'])) $s['canonical'] = $_SERVER['REQUEST_URI'] ?? '/'; // per-content override may be null
    $canonical = abs_url('/' . ltrim(parse_url($s['canonical'], PHP_URL_PATH) ?: '', '/'));
    $ogImage = $s['og_image'] !== '' && str_starts_with($s['og_image'], 'http') ? $s['og_image'] : abs_url('/' . ltrim($s['og_image'], '/'));

    echo '<title>' . e($fullTitle) . "</title>\n";
    echo '<meta name="description" content="' . e($s['description']) . "\">\n";
    echo '<meta name="robots" content="' . e($s['robots']) . "\">\n";
    echo '<link rel="canonical" href="' . e($canonical) . "\">\n";
    echo '<meta property="og:site_name" content="' . e($siteName) . "\">\n";
    echo '<meta property="og:type" content="' . e($s['og_type']) . "\">\n";
    echo '<meta property="og:title" content="' . e($s['og_title'] ?: $fullTitle) . "\">\n";
    echo '<meta property="og:description" content="' . e($s['og_desc'] ?: $s['description']) . "\">\n";
    echo '<meta property="og:url" content="' . e($canonical) . "\">\n";
    echo '<meta property="og:image" content="' . e($ogImage) . "\">\n";
    if (($s['og_type'] ?? '') === 'article') {
        if (!empty($s['published_time'])) echo '<meta property="article:published_time" content="' . e($s['published_time']) . '">\n';
        if (!empty($s['modified_time'])) echo '<meta property="article:modified_time" content="' . e($s['modified_time']) . '">\n';
        if (!empty($s['author_url'])) echo '<meta property="article:author" content="' . e($s['author_url']) . '">\n';
    }
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . e($s['og_title'] ?: $fullTitle) . "\">\n";
    echo '<meta name="twitter:description" content="' . e($s['og_desc'] ?: $s['description']) . "\">\n";
    echo '<meta name="twitter:image" content="' . e($ogImage) . "\">\n";

    foreach (['google_verification' => 'google-site-verification', 'bing_verification' => 'msvalidate.01'] as $k => $name) {
        if (setting($k) !== '') echo '<meta name="' . $name . '" content="' . e(setting($k)) . "\">\n";
    }

    foreach ($GLOBALS['SEO_JSONLD'] as $ld) {
        echo '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
    }
}

/** Baseline organization + website schema (used on the homepage). */
function seo_default_schema(): void
{
    $siteName = setting('site_name', 'AutoPulse');
    seo_jsonld([
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => $siteName,
        'url'      => abs_url('/'),
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => abs_url('/search') . '?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ]);
    $sameAs = array_values(array_filter([
        setting('social_facebook'), setting('social_twitter'), setting('social_instagram'), setting('social_youtube'),
    ]));
    $org = [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => $siteName,
        'url'      => abs_url('/'),
        'logo'     => abs_url(setting('logo') ?: 'assets/images/logo.svg'),
    ];
    if (setting('org_description')) $org['description'] = setting('org_description');
    if ($sameAs) $org['sameAs'] = $sameAs;
    seo_jsonld($org);
}

function breadcrumb_schema(array $crumbs): void
{
    $list = [];
    $pos = 1;
    foreach ($crumbs as $c) {
        $item = ['@type' => 'ListItem', 'position' => $pos++, 'name' => $c['name']];
        if (!empty($c['url'])) $item['item'] = abs_url('/' . ltrim($c['url'], '/'));
        $list[] = $item;
    }
    seo_jsonld(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list]);
}

function faq_schema(array $faq): void
{
    if (!$faq) return;
    seo_jsonld([
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => array_map(fn($i) => [
            '@type'          => 'Question',
            'name'           => $i['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $i['a']],
        ], $faq),
    ]);
}
