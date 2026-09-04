<?php
/** AutoPulse — single brand page (Module 13): /brands/{slug}. */
$brand = get_brand($params['slug'] ?? '');
if (!$brand) {
    http_response_code(404);
    $SEO = ['title' => 'Brand not found', 'robots' => 'noindex, follow'];
    include __DIR__ . '/404.php';
    return;
}
$cars = cars_query(['brand' => $brand['slug'], 'per' => 24, 'sort' => 'newest']);
$articles = articles_query(['brand_slug' => $brand['slug'], 'per' => 3])['items'];
$featured = array_values(array_filter($cars['items'], fn($c) => $c['is_popular'] == 1));
if (!$featured) $featured = array_slice($cars['items'], 0, 3);

seo_set([
    'title'       => $brand['seo_title'] ?: "{$brand['name']} Cars — Models, Prices & Reviews",
    'description' => $brand['meta_description'] ?: "{$brand['name']} lineup in our database: models, specifications, prices and the latest {$brand['name']} news.",
    'og_image'    => $brand['logo'] ?: 'uploads/general/hero.jpg',
]);
render_breadcrumbs([
    ['name' => 'Home', 'url' => ''],
    ['name' => 'Brands', 'url' => 'brands'],
    ['name' => $brand['name']],
]);

include __DIR__ . '/../includes/header.php';
?>
<header class="page-head">
    <div class="container brand-hero">
        <?php render_breadcrumbs([
            ['name' => 'Home', 'url' => ''],
            ['name' => 'Brands', 'url' => 'brands'],
            ['name' => $brand['name']],
        ]); ?>
        <div style="display:flex;align-items:center;gap:1.2rem;flex-wrap:wrap">
            <img src="<?= e(img_url($brand['logo'], 'assets/images/brands/default.svg')) ?>" alt="<?= e($brand['name']) ?> logo" width="84" height="84" style="width:84px;height:84px;object-fit:contain;background:#fff;border-radius:var(--radius-md);padding:8px">
            <div>
                <h1 style="margin-bottom:0"><?= e($brand['name']) ?></h1>
                <p><?= e(implode(' · ', array_filter([$brand['country'], $brand['founded_year'] ? 'Founded ' . $brand['founded_year'] : '', (string)$cars['total'] . ' models']))) ?></p>
            </div>
        </div>
    </div>
</header>

<div class="section">
    <div class="container">
        <?php if ($brand['description']): ?>
            <div class="prose" style="max-width:75ch;margin-bottom:2.4rem"><?= $brand['description'] /* admin-authored */ ?></div>
        <?php endif; ?>

        <?php if ($featured): ?>
            <?php section_head('Featured Models'); ?>
            <div class="grid-cards reveal"><?php foreach ($featured as $c) car_card($c); ?></div>
        <?php endif; ?>

        <?php if (count($cars['items']) > 0): ?>
            <?php section_head('All Models'); ?>
            <div class="grid-cards"><?php foreach ($cars['items'] as $c) car_card($c); ?></div>
            <?php render_pagination($cars['page'], $cars['pages'], 'brands/' . $brand['slug']); ?>
        <?php else: ?>
            <div class="empty-state"><div class="icon">🚙</div><h3>No models yet</h3><p>We're working on adding <?= e($brand['name']) ?> models.</p></div>
        <?php endif; ?>

        <?php if ($articles): ?>
            <section class="section section-tint" style="border-radius:var(--radius-lg);margin-top:2.4rem">
                <?php section_head("Latest {$brand['name']} News"); ?>
                <div class="grid-cards reveal"><?php foreach ($articles as $a) article_card($a); ?></div>
            </section>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
