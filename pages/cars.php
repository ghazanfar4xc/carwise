<?php
/** AutoPulse — car catalogue with server-side filters (Module 08). */
$filters = [
    'brand'  => trim((string)($_GET['brand'] ?? '')),
    'body'   => trim((string)($_GET['body'] ?? '')),
    'fuel'   => trim((string)($_GET['fuel'] ?? '')),
    'q'      => trim((string)($_GET['q'] ?? '')),
    'sort'   => trim((string)($_GET['sort'] ?? '')),
    'page'   => get_int('page', 1),
];
$result = cars_query(array_filter($filters, fn($v) => $v !== '') + ['per' => 12]);
seo_set([
    'title' => 'Cars — Specifications & Prices',
    'description' => 'Browse every car in our database: full specifications, prices, fuel economy and features. Filter by brand, body type and fuel.',
]);
if ($filters['brand']) seo_set(['title' => ($result['items'][0]['brand'] ?? ucfirst($filters['brand'])) . ' Cars — Specs & Prices']);

include __DIR__ . '/../includes/header.php';
?>
<header class="page-head">
    <div class="container">
        <?php render_breadcrumbs([['name' => 'Home', 'url' => ''], ['name' => 'Cars']]); ?>
        <h1>Car Catalogue</h1>
        <p><?= (int)$result['total'] ?> models with verified specifications and current prices.</p>
    </div>
</header>

<div class="section">
    <div class="container">
        <form class="filter-bar" method="get" action="<?= e(url('cars')) ?>" role="search">
            <div class="form-field" style="margin:0">
                <label for="f-q">Search</label>
                <input type="search" id="f-q" name="q" class="input" placeholder="Model name…" value="<?= e($filters['q']) ?>">
            </div>
            <div class="form-field" style="margin:0">
                <label for="f-brand">Brand</label>
                <select id="f-brand" name="brand" class="select">
                    <option value="">All brands</option>
                    <?php foreach (get_brands() as $b): ?>
                        <option value="<?= e($b['slug']) ?>" <?= $filters['brand'] === $b['slug'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field" style="margin:0">
                <label for="f-body">Body type</label>
                <select id="f-body" name="body" class="select">
                    <option value="">Any body</option>
                    <?php $bodies = db()->query('SELECT DISTINCT body_type FROM car_models WHERE body_type IS NOT NULL AND status = "published" ORDER BY body_type')->fetchAll(PDO::FETCH_COLUMN); ?>
                    <?php foreach ($bodies as $bt): ?>
                        <option value="<?= e($bt) ?>" <?= $filters['body'] === $bt ? 'selected' : '' ?>><?= e($bt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field" style="margin:0">
                <label for="f-sort">Sort by</label>
                <select id="f-sort" name="sort" class="select">
                    <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Newest first</option>
                    <option value="popular" <?= $filters['sort'] === 'popular' ? 'selected' : '' ?>>Most popular</option>
                    <option value="price_asc" <?= $filters['sort'] === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
                    <option value="price_desc" <?= $filters['sort'] === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Apply</button>
        </form>

        <?php ad_slot('home_mid'); ?>

        <?php if (!$result['items']): ?>
            <div class="empty-state">
                <div class="icon">🔎</div>
                <h3>No cars match those filters</h3>
                <p>Try removing a filter or searching for a different model.</p>
                <a class="btn btn-outline" href="<?= e(url('cars')) ?>">Reset filters</a>
            </div>
        <?php else: ?>
            <div class="grid-cards">
                <?php foreach ($result['items'] as $car) car_card($car); ?>
            </div>
            <?php render_pagination($result['page'], $result['pages'], 'cars', array_filter($filters, fn($v) => $v !== '', ARRAY_FILTER_USE_KEY)); ?>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
