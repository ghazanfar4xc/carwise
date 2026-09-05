<?php
/** AutoPulse — car detail page (Module 09). */
$car = get_car($params['brand'] ?? '', $params['slug'] ?? '');
if (!$car) {
    http_response_code(404);
    $SEO = ['title' => 'Car not found', 'robots' => 'noindex, follow'];
    include __DIR__ . '/404.php';
    return;
}
db()->prepare('UPDATE car_models SET views = views + 1 WHERE id = ?')->execute([$car['id']]);
$specs = $car['specs'];
$images = $car['images'] ?: ($car['main_image'] ? [[
    'path' => $car['main_image'], 'alt' => $car['brand'] . ' ' . $car['name'],
]] : []);
$faq = faq_items($car['faq'] ?? null);

seo_set([
    'title'       => $car['seo_title'] ?: "{$car['brand']} {$car['name']} — Specs, Price & Review",
    'description' => $car['meta_description'] ?: "{$car['brand']} {$car['name']} ({$car['year_start']}): " . excerpt_text($car['overview'] ?? $car['tagline'] ?? '', 150),
    'canonical'   => $car['canonical_url'] ?: null,
    'robots'      => $car['robots'] ?: 'index, follow',
    'og_type'     => 'product',
    'og_title'    => $car['og_title'] ?: ($car['seo_title'] ?: "{$car['brand']} {$car['name']}"),
    'og_desc'     => $car['og_description'] ?: ($car['meta_description'] ?: excerpt_text($car['overview'] ?? '', 155)),
    'og_image'    => $car['og_image'] ?: ($car['main_image'] ?: 'uploads/general/hero.jpg'),
]);
$carSources = get_sources('car', (int)$car['id']);
$carAnswers = get_answer_blocks('car', (int)$car['id']);
render_breadcrumbs([
    ['name' => 'Home', 'url' => ''],
    ['name' => 'Cars', 'url' => 'cars'],
    ['name' => $car['brand'], 'url' => 'brands/' . $car['brand_slug']],
    ['name' => $car['name']],
]);
faq_schema($faq);
seo_jsonld(array_filter([
    '@context'    => 'https://schema.org',
    '@type'       => 'Car',
    'name'        => "{$car['brand']} {$car['name']}",
    'model'       => $car['name'],
    'vehicleModelDate' => (string)$car['year_start'],
    'bodyType'    => $car['body_type'],
    'image'       => abs_url('/' . ltrim($car['main_image'] ?: 'uploads/general/hero.jpg', '/')),
    'description' => excerpt_text((string)($car['quick_answer'] ?: $car['overview'] ?? ''), 200),
    'brand'       => ['@type' => 'Brand', 'name' => $car['brand']],
    'url'         => abs_url('/cars/' . $car['brand_slug'] . '/' . $car['slug']),
], fn($v) => $v !== null && $v !== ''));

$specRows = [ // label => [value, format]
    'Engine'                => [$specs['engine'] ?? '', 'text'],
    'Displacement'          => [$specs['displacement_cc'] ?? '', 'cc'],
    'Fuel type'             => [$specs['fuel_type'] ?? '', 'text'],
    'Power'                 => [$specs['power_hp'] ?? '', 'hp'],
    'Torque'                => [$specs['torque_nm'] ?? '', 'lb-ft'],
    'Transmission'          => [$specs['transmission'] ?? '', 'text'],
    'Drive type'            => [$specs['drive_type'] ?? '', 'text'],
    '0–60 mph'              => [$specs['acceleration_s'] ?? '', 's'],
    'Top speed'             => [$specs['top_speed_kmh'] ?? '', 'mph'],
    'Fuel tank'             => [$specs['fuel_tank_l'] ?? '', 'gal'],
    'City fuel economy'     => [$specs['mileage_city_kml'] ?? '', 'mpg'],
    'Highway fuel economy'  => [$specs['mileage_highway_kml'] ?? '', 'mpg'],
    'Battery'               => [$specs['battery_kwh'] ?? '', 'kWh'],
    'Electric range'        => [$specs['range_km'] ?? '', 'mi'],
    'Length'                => [$specs['length_mm'] ?? '', 'mm'],
    'Width'                 => [$specs['width_mm'] ?? '', 'mm'],
    'Height'                => [$specs['height_mm'] ?? '', 'mm'],
    'Wheelbase'             => [$specs['wheelbase_mm'] ?? '', 'mm'],
    'Ground clearance'      => [$specs['ground_clearance_mm'] ?? '', 'mm'],
    'Curb weight'           => [$specs['curb_weight_kg'] ?? '', 'lbs'],
    'Cargo space'           => [$specs['boot_space_l'] ?? '', 'cu ft'],
    'Seating capacity'      => [$specs['seating'] ?? '', 'seats'],
    'Doors'                 => [$specs['doors'] ?? '', ''],
];
$specGroups = [
    'Engine & Drivetrain' => ['Engine', 'Displacement', 'Fuel type', 'Transmission', 'Drive type'],
    'Performance'         => ['Power', 'Torque', '0–60 mph', 'Top speed'],
    'Fuel & Economy'      => ['Fuel tank', 'City fuel economy', 'Highway fuel economy', 'Battery', 'Electric range'],
    'Dimensions & Weight' => ['Length', 'Width', 'Height', 'Wheelbase', 'Ground clearance', 'Curb weight', 'Cargo space'],
    'Capacity'            => ['Seating capacity', 'Doors'],
];

include __DIR__ . '/../includes/header.php';
?>
<article>
<header class="car-hero">
    <div class="container">
        <?php render_breadcrumbs([
            ['name' => 'Home', 'url' => ''],
            ['name' => 'Cars', 'url' => 'cars'],
            ['name' => $car['brand'], 'url' => 'brands/' . $car['brand_slug']],
            ['name' => $car['name']],
        ]); ?>
        <div class="car-title-row">
            <h1><?= e($car['name']) ?></h1>
            <?php if ($car['status'] === 'upcoming'): ?><span class="tag">Upcoming</span><?php endif; ?>
        </div>
        <div class="article-header-meta">
            <span class="brand-chip"><img src="<?= e(img_url($car['brand_logo'], 'assets/images/brands/default.svg')) ?>" alt=""><?= e($car['brand']) ?></span>
            <span><?= e(implode(' · ', array_filter([$car['generation'], $car['year_start'] ? $car['year_start'] : null]))) ?></span>
            <span><?= e($car['body_type']) ?><?= $car['segment'] ? ' · ' . e($car['segment']) : '' ?></span>
            <span><?= (int)$car['views'] ?> views</span>
            <?php if ($car['last_verified_at']): ?><span title="Specifications last verified">✓ Verified <?= e(format_date($car['last_verified_at'])) ?></span><?php endif; ?>
        </div>
    </div>
</header>

<div class="section">
    <div class="container">
        <div class="grid-2 car-detail-grid">
            <div>
                <?php if ($images): ?>
                <div class="gallery" data-gallery>
                    <figure class="gallery-main" style="margin:0">
                        <?php $gm = media_meta((string)$images[0]['path']); ?>
                        <img id="gallery-main-img" src="<?= e(img_url($images[0]['path'])) ?>" alt="<?= e($images[0]['alt'] ?: ($gm['alt'] ?: $car['brand'] . ' ' . $car['name'])) ?>" width="960" height="570" fetchpriority="high">
                    </figure>
                    <?php if (count($images) > 1): ?>
                    <div class="gallery-thumbs" role="tablist" aria-label="Gallery">
                        <?php foreach ($images as $i => $im): ?>
                        <button type="button" class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>" data-full="<?= e(img_url($im['path'])) ?>" data-alt="<?= e($im['alt'] ?: $car['brand'] . ' ' . $car['name']) ?>" aria-label="View image <?= $i + 1 ?>">
                            <img src="<?= e(img_url($im['path'])) ?>" alt="" loading="lazy" width="120" height="90">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div>
                <div class="price-box">
                    <span class="price"><?= format_price($car['price'], $car['price_note']) ?></span>
                    <?php if ($car['price_note'] && $car['price'] > 0): ?><span class="price-note"><?= e($car['price_note']) ?></span><?php endif; ?>
                </div>
                <div class="key-specs">
                    <?php
                    $keySpecs = [
                        [$specs['power_hp'] ?? '', 'Power', 'hp'],
                        [$specs['torque_nm'] ?? '', 'Torque', 'lb-ft'],
                        [$specs['engine'] ?? '', 'Engine', ''],
                        [$specs['transmission'] ?? '', 'Gearbox', ''],
                        [$specs['seating'] ?? '', 'Seats', ''],
                        [$specs['mileage_city_kml'] ?? '', 'City', 'mpg'],
                    ];
                    foreach ($keySpecs as [$v, $l, $u]):
                        if ($v === '' || $v === null) continue; ?>
                        <div class="key-spec"><b><?= e(spec_num($v)) ?><?= $u ? ' ' . e($u) : '' ?></b><span><?= e($l) ?></span></div>
                    <?php endforeach; ?>
                </div>
                <div class="cta-stack">
                    <a href="<?= e(url('compare')) ?>?ids=<?= (int)$car['id'] ?>" class="btn btn-primary btn-block">Compare this car</a>
                    <a href="<?= e(url('search')) ?>?q=<?= e($car['brand'] . ' ' . $car['name']) ?>" class="btn btn-outline btn-block">Related reviews &amp; news</a>
                </div>
            </div>
        </div>

        <?php if ($car['quick_answer']): ?>
        <section class="quick-answer" aria-label="Quick answer">
            <h2><?= e($car['brand'] . ' ' . $car['name']) ?> — Quick Answer</h2>
            <p><?= e($car['quick_answer']) ?></p>
            <?php if ($car['last_verified_at']): ?><span class="verified-badge">✓ Specs verified <?= e(format_date($car['last_verified_at'])) ?></span><?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($carAnswers): ?>
        <section class="section" style="padding-block:1.5rem 0">
            <?php section_head('Quick Answers'); ?>
            <div class="faq-list">
                <?php foreach ($carAnswers as $i => $ab): ?>
                <details class="faq-item" <?= $i === 0 ? 'open' : '' ?>>
                    <summary><?= e($ab['question']) ?></summary>
                    <div class="faq-answer"><p><strong><?= e($ab['short_answer']) ?></strong></p><?= $ab['explanation'] ? '<p>' . e($ab['explanation']) . '</p>' : '' ?></div>
                </details>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($car['overview']): ?>
        <section class="section" style="padding-block:1.5rem">
            <?php section_head('Overview'); ?>
            <div class="prose"><?= $car['overview'] /* admin-authored HTML */ ?></div>
        </section>
        <?php endif; ?>

        <?php ad_slot('article_top'); ?>

        <section>
            <?php section_head('Full Specifications'); ?>
            <?php foreach ($specGroups as $group => $labels): ?>
                <?php $rows = array_filter($specRows, fn($l) => in_array($l, $labels, true) && $specRows[$l][0] !== '' && $specRows[$l][0] !== null, ARRAY_FILTER_USE_KEY);
                if (!$rows) continue; ?>
                <div class="spec-group">
                    <h3><?= e($group) ?></h3>
                    <table class="spec-table">
                        <tbody>
                        <?php foreach ($rows as $label => [$val, $unit]): ?>
                            <tr><th scope="row"><?= e($label) ?></th><td><?= e(spec_num($val)) ?><?= $unit ? ' ' . e($unit) : '' ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </section>

        <?php
        $groupLabels = ['safety' => 'Safety', 'technology' => 'Technology', 'interior' => 'Interior & Comfort', 'exterior' => 'Exterior'];
        foreach ($groupLabels as $gk => $gl):
            if (empty($car['features'][$gk])) continue; ?>
            <section class="spec-group">
                <?php section_head($gl . ' Features'); ?>
                <ul class="feature-list reveal">
                    <?php foreach ($car['features'][$gk] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>

        <?php if ($car['pros'] || $car['cons']):
            $pros = json_decode((string)$car['pros'], true) ?: [];
            $cons = json_decode((string)$car['cons'], true) ?: [];
            if ($pros || $cons): ?>
            <section>
                <?php section_head('Pros & Cons'); ?>
                <div class="pros-cons reveal">
                    <?php if ($pros): ?><div class="pros"><h3>What we like</h3><ul><?php foreach ($pros as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                    <?php if ($cons): ?><div class="cons"><h3>What could be better</h3><ul><?php foreach ($cons as $c): ?><li><?= e($c) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                </div>
            </section>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($faq): ?>
        <section>
            <?php section_head('Frequently Asked Questions'); ?>
            <div class="faq-list reveal">
                <?php foreach ($faq as $i => $item): ?>
                <details class="faq-item" <?= $i === 0 ? 'open' : '' ?>>
                    <summary><?= e($item['q']) ?></summary>
                    <div class="faq-answer"><?= e($item['a']) ?></div>
                </details>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($carSources): ?>
        <section>
            <?php section_head('Sources'); ?>
            <ul class="sources-list reveal">
                <?php foreach ($carSources as $src): ?>
                <li>
                    <span class="source-type"><?= e(source_type_label($src['source_type'])) ?></span>
                    <?php if ($src['url']): ?><a href="<?= e($src['url']) ?>" rel="nofollow noopener" target="_blank"><?= e($src['name']) ?></a><?php else: ?><span><?= e($src['name']) ?></span><?php endif; ?>
                    <small>accessed <?= e(format_date($src['accessed_date'])) ?></small>
                </li>
                <?php endforeach; ?>
            </ul>
            <p style="color:var(--color-muted)">Specifications and pricing on this page are compiled from the sources above. <a href="<?= e(url('review-methodology')) ?>">How we verify data</a>.</p>
        </section>
        <?php endif; ?>

        <?php
        $similar = similar_cars($car, 3);
        if ($similar): ?>
        <section class="section" style="background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:clamp(1.4rem,3vw,2.4rem);margin-top:2.5rem">
            <?php section_head('Similar Cars', 'cars?body=' . rawurlencode($car['body_type']), 'More ' . $car['body_type'] . 's'); ?>
            <div class="grid-cards reveal"><?php foreach ($similar as $s) car_card($s); ?></div>
        </section>
        <?php endif; ?>

        <?php
        $related = articles_query(['brand_slug' => $car['brand_slug'], 'per' => 3])['items'];
        if (!$related) $related = articles_query(['q' => $car['brand'], 'per' => 3])['items'];
        if ($related): ?>
        <section class="section">
            <div class="container" style="padding-inline:0">
                <?php section_head('Related Articles'); ?>
                <div class="grid-cards reveal"><?php foreach ($related as $a) article_card($a); ?></div>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>
</article>
<?php include __DIR__ . '/../includes/footer.php'; ?>
