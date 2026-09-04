<?php
/** AutoPulse — live search suggestions: GET /api/search.php?q=… */
require dirname(__DIR__) . '/includes/api_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_out(['success' => false, 'message' => 'Method not allowed'], 405);

$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q) < 2) json_out(['success' => true, 'data' => []]);
if (!rate_limit('api_search', 60, 60)) json_out(['success' => false, 'message' => 'Too many searches, slow down.'], 429);

$res = search_all(mb_substr($q, 0, 60), 4);

json_out(['success' => true, 'data' => [
    'cars' => array_map(fn($c) => [
        'title' => $c['brand'] . ' ' . $c['name'],
        'sub'   => format_price($c['price'], $c['price_note']),
        'image' => img_url($c['main_image']),
        'url'   => car_url($c),
    ], $res['cars']),
    'articles' => array_map(fn($a) => [
        'title' => $a['title'],
        'sub'   => 'Article',
        'image' => img_url($a['featured_image']),
        'url'   => article_url($a),
    ], $res['articles']),
    'brands' => array_map(fn($b) => [
        'title' => $b['name'],
        'sub'   => 'Brand',
        'image' => img_url($b['logo'], 'assets/images/brands/default.svg'),
        'url'   => brand_url($b),
    ], $res['brands']),
]]);
