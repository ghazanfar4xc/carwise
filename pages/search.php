<?php
/** AutoPulse — search results page (Module 11). */
$q = trim((string)($_GET['q'] ?? ''));
$results = $q !== '' ? search_all($q, 20) : ['cars' => [], 'articles' => [], 'brands' => []];
$total = count($results['cars']) + count($results['articles']) + count($results['brands']);

seo_set([
    'title' => $q !== '' ? "Search: {$q}" : 'Search',
    'robots' => 'noindex, follow',
    'description' => 'Search cars, brands and articles.',
]);

include __DIR__ . '/../includes/header.php';
?>
<header class="page-head">
    <div class="container">
        <?php render_breadcrumbs([['name' => 'Home', 'url' => ''], ['name' => 'Search']]); ?>
        <h1>Search</h1>
        <form method="get" action="<?= e(url('search')) ?>" class="search-page-form" role="search" style="display:flex;gap:.6rem;margin-top:1.2rem;max-width:560px">
            <input type="search" name="q" class="input" placeholder="Search cars, brands, articles…" value="<?= e($q) ?>" aria-label="Search query">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>
</header>

<div class="section">
    <div class="container">
        <?php if ($q === ''): ?>
            <div class="empty-state">
                <div class="icon">🔍</div>
                <h3>Start typing to search</h3>
                <p>Look up cars, brands, reviews and news across the whole site.</p>
            </div>
        <?php elseif ($total === 0): ?>
            <div class="empty-state">
                <div class="icon">🚗💨</div>
                <h3>No results for “<?= e($q) ?>”</h3>
                <p>Check the spelling or try a broader term (e.g. a brand name).</p>
                <a class="btn btn-outline" href="<?= e(url('cars')) ?>">Browse all cars</a>
            </div>
        <?php else: ?>
            <p class="result-count"><?= (int)$total ?> results for “<strong><?= e($q) ?></strong>”</p>

            <?php if ($results['cars']): ?>
                <?php section_head('Cars'); ?>
                <div class="grid-cards"><?php foreach ($results['cars'] as $car) car_card($car); ?></div>
            <?php endif; ?>

            <?php if ($results['brands']): ?>
                <?php section_head('Brands'); ?>
                <div class="grid-cards" style="grid-template-columns:repeat(auto-fill,minmax(min(100%,150px),1fr))">
                    <?php foreach ($results['brands'] as $b) brand_tile($b + ['model_count' => 0]); ?>
                </div>
            <?php endif; ?>

            <?php if ($results['articles']): ?>
                <?php section_head('Articles'); ?>
                <div class="grid-cards"><?php foreach ($results['articles'] as $a) article_card($a + ['category' => null, 'category_slug' => null, 'published_at' => null]); ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
