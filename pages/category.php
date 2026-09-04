<?php
/** AutoPulse — dynamic category page (Module 14): /category/{slug}. */
$category = get_category($params['slug'] ?? '');
if (!$category) {
    http_response_code(404);
    $SEO = ['title' => 'Category not found', 'robots' => 'noindex, follow'];
    include __DIR__ . '/404.php';
    return;
}
$page = get_int('page', 1);
$articles = articles_query(['category' => $category['slug'], 'page' => $page, 'per' => 10]);
$cars = $category['type'] === 'car' ? cars_query(['category' => $category['slug'], 'page' => $page, 'per' => 12]) : ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$useCarPagination = $category['type'] === 'car' && $cars['total'] > 0;

seo_set([
    'title'       => $category['seo_title'] ?: $category['name'],
    'description' => $category['meta_description'] ?: ($category['description'] ?: "All {$category['name']} content on " . setting('site_name', 'AutoPulse') . '.'),
]);
render_breadcrumbs([
    ['name' => 'Home', 'url' => ''],
    ['name' => $category['name']],
]);

include __DIR__ . '/../includes/header.php';
?>
<header class="page-head">
    <div class="container">
        <?php render_breadcrumbs([
            ['name' => 'Home', 'url' => ''],
            ['name' => $category['name']],
        ]); ?>
        <h1><?= e($category['name']) ?></h1>
        <p><?= e($category['description'] ?: 'Everything filed under ' . $category['name'] . '.') ?></p>
    </div>
</header>

<div class="section">
    <div class="container">
        <?php if ($category['type'] === 'car' && $cars['items']): ?>
            <?php section_head('Cars', 'cars', 'Browse all'); ?>
            <div class="grid-cards"><?php foreach ($cars['items'] as $c) car_card($c); ?></div>
            <?php if ($useCarPagination) render_pagination($cars['page'], $cars['pages'], 'category/' . $category['slug']); ?>
        <?php endif; ?>

        <?php if ($articles['items']): ?>
            <?php section_head($category['type'] === 'car' ? 'Related Articles' : 'Latest Articles'); ?>
            <div class="grid-cards"><?php foreach ($articles['items'] as $a) article_card($a); ?></div>
            <?php if (!$useCarPagination) render_pagination($articles['page'], $articles['pages'], 'category/' . $category['slug']); ?>
        <?php elseif (!$cars['items']): ?>
            <div class="empty-state">
                <div class="icon">🗂️</div>
                <h3>Nothing here yet</h3>
                <p>New content for <?= e($category['name']) ?> is coming soon.</p>
                <a class="btn btn-outline" href="<?= e(url('articles')) ?>">Browse all articles</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
