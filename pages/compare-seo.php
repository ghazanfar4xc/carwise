<?php
/** AutoPulse — SEO comparison page: /compare/{car-a}-vs-{car-b} (server-rendered, machine-readable). */
$parts = explode('-vs-', $params['pair'] ?? '', 2);
if (count($parts) !== 2) {
    header('Location: ' . url('compare'), true, 302);
    exit;
}

$findCar = function (string $slug): ?array {
    $st = db()->prepare("SELECT c.*, b.name AS brand, b.slug AS brand_slug FROM car_models c JOIN brands b ON b.id = c.brand_id WHERE c.slug = ? AND c.status = 'published'");
    $st->execute([$slug]);
    $car = $st->fetch() ?: null;
    if ($car) {
        $sp = db()->prepare('SELECT * FROM car_specs WHERE car_id = ?');
        $sp->execute([$car['id']]);
        $car['specs'] = $sp->fetch() ?: [];
    }
    return $car;
};
$carA = $findCar(trim($parts[0], '/'));
$carB = $findCar(trim($parts[1], '/'));
if (!$carA || !$carB) {
    http_response_code(404);
    $SEO = ['title' => 'Comparison not found', 'robots' => 'noindex, follow'];
    include __DIR__ . '/404.php';
    return;
}

$fullName = fn(array $c): string => $c['year_start'] . ' ' . $c['brand'] . ' ' . $c['name'];
$title = $fullName($carA) . ' vs ' . $fullName($carB);

seo_set([
    'title'       => $title . ' — Head-to-Head Comparison',
    'description' => $title . ': price, power, fuel economy, dimensions and features compared side by side with links to full specifications and reviews.',
    'robots'      => 'index, follow',
]);
render_breadcrumbs([
    ['name' => 'Home', 'url' => ''],
    ['name' => 'Compare', 'url' => 'compare'],
    ['name' => $carA['brand'] . ' ' . $carA['name'] . ' vs ' . $carB['brand'] . ' ' . $carB['name']],
]);

$spec = fn(array $c, string $k) => $c['specs'][$k] ?? null;
$rows = [
    'Starting price'   => [format_price($carA['price'], $carA['price_note']), format_price($carB['price'], $carB['price_note'])],
    'Body type'        => [$carA['body_type'], $carB['body_type']],
    'Engine'           => [$spec($carA, 'engine'), $spec($carB, 'engine')],
    'Power'            => [$spec($carA, 'power_hp') ? $spec($carA, 'power_hp') . ' hp' : '—', $spec($carB, 'power_hp') ? $spec($carB, 'power_hp') . ' hp' : '—'],
    'Torque'           => [$spec($carA, 'torque_nm') ? $spec($carA, 'torque_nm') . ' lb-ft' : '—', $spec($carB, 'torque_nm') ? $spec($carB, 'torque_nm') . ' lb-ft' : '—'],
    '0–60 mph'         => [$spec($carA, 'acceleration_s') ? spec_num($spec($carA, 'acceleration_s')) . ' s' : '—', $spec($carB, 'acceleration_s') ? spec_num($spec($carB, 'acceleration_s')) . ' s' : '—'],
    'City economy'     => [$spec($carA, 'mileage_city_kml') ? spec_num($spec($carA, 'mileage_city_kml')) . ' mpg' : '—', $spec($carB, 'mileage_city_kml') ? spec_num($spec($carB, 'mileage_city_kml')) . ' mpg' : '—'],
    'Highway economy'  => [$spec($carA, 'mileage_highway_kml') ? spec_num($spec($carA, 'mileage_highway_kml')) . ' mpg' : '—', $spec($carB, 'mileage_highway_kml') ? spec_num($spec($carB, 'mileage_highway_kml')) . ' mpg' : '—'],
    'Electric range'   => [$spec($carA, 'range_km') ? $spec($carA, 'range_km') . ' mi' : '—', $spec($carB, 'range_km') ? $spec($carB, 'range_km') . ' mi' : '—'],
    'Seating'          => [$spec($carA, 'seating') ?: '—', $spec($carB, 'seating') ?: '—'],
    'Cargo space'      => [$spec($carA, 'boot_space_l') ? $spec($carA, 'boot_space_l') . ' cu ft' : '—', $spec($carB, 'boot_space_l') ? $spec($carB, 'boot_space_l') . ' cu ft' : '—'],
];

seo_jsonld([
    '@context' => 'https://schema.org',
    '@type'    => 'ItemList',
    'name'     => $title,
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => $fullName($carA), 'url' => car_url($carA)],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $fullName($carB), 'url' => car_url($carB)],
    ],
]);

include __DIR__ . '/../includes/header.php';
?>
<header class="page-head">
    <div class="container">
        <h1><?= e($title) ?></h1>
        <p>Key specifications from our verified database, side by side. Values shown as “—” are not yet in our database.</p>
    </div>
</header>

<div class="section">
    <div class="container">
        <div class="compare-table-wrap">
            <table class="compare-table spec-table">
                <thead>
                    <tr>
                        <th class="spec-label" scope="col">Specification</th>
                        <th scope="col">
                            <a href="<?= e(car_url($carA)) ?>" style="color:#fff"><img src="<?= e(img_url($carA['main_image'])) ?>" alt="<?= e($fullName($carA)) ?>" loading="lazy" width="120" height="80"><br><?= e($fullName($carA)) ?></a>
                        </th>
                        <th scope="col">
                            <a href="<?= e(car_url($carB)) ?>" style="color:#fff"><img src="<?= e(img_url($carB['main_image'])) ?>" alt="<?= e($fullName($carB)) ?>" loading="lazy" width="120" height="80"><br><?= e($fullName($carB)) ?></a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $label => [$va, $vb]): ?>
                    <tr>
                        <th scope="row" class="spec-label"><?= e($label) ?></th>
                        <td><?= e((string)$va) ?></td>
                        <td><?= e((string)$vb) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="compare-reviews" style="grid-template-columns:repeat(2,1fr)">
            <article class="compare-review">
                <h3><a href="<?= e(car_url($carA)) ?>"><?= e($fullName($carA)) ?></a></h3>
                <div class="prose prose-sm"><?= $carA['overview'] /* admin-authored */ ?></div>
                <a class="btn btn-outline btn-sm" href="<?= e(car_url($carA)) ?>">Full review &amp; specifications</a>
            </article>
            <article class="compare-review">
                <h3><a href="<?= e(car_url($carB)) ?>"><?= e($fullName($carB)) ?></a></h3>
                <div class="prose prose-sm"><?= $carB['overview'] /* admin-authored */ ?></div>
                <a class="btn btn-outline btn-sm" href="<?= e(car_url($carB)) ?>">Full review &amp; specifications</a>
            </article>
        </div>

        <p style="color:var(--color-muted)">Compare more models with the <a href="<?= e(url('compare')) ?>">interactive comparison tool</a>.</p>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
