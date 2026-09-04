<?php
/** AutoPulse — car list JSON: GET /api/cars.php?brand_id=&body=&q= */
require dirname(__DIR__) . '/includes/api_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_out(['success' => false, 'message' => 'Method not allowed'], 405);

$filters = [
    'q'    => mb_substr(trim((string)($_GET['q'] ?? '')), 0, 60),
    'body' => trim((string)($_GET['body'] ?? '')),
    'page' => get_int('page', 1),
];
if (get_int('brand_id', 0)) {
    $st = db()->prepare('SELECT slug FROM brands WHERE id = ?');
    $st->execute([get_int('brand_id')]);
    if ($slug = $st->fetchColumn()) $filters['brand'] = $slug;
}

$res = cars_query(array_filter($filters, fn($v) => $v !== '') + ['per' => 40]);

json_out(['success' => true, 'data' => array_map(fn($c) => [
    'id'     => (int)$c['id'],
    'label'  => $c['brand'] . ' ' . $c['name'] . ' (' . $c['year_start'] . ')',
    'slug'   => $c['slug'],
    'url'    => car_url($c),
], $res['items'])]);
