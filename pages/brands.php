<?php
/** AutoPulse — brand index (Module 13). */
$brands = get_brands();
seo_set([
    'title' => 'Car Brands',
    'description' => 'Every car brand we cover — click a brand to see its lineup, prices and latest news.',
]);

include __DIR__ . '/../includes/header.php';
?>
<header class="page-head">
    <div class="container">
        <?php render_breadcrumbs([['name' => 'Home', 'url' => ''], ['name' => 'Brands']]); ?>
        <h1>Car Brands</h1>
        <p><?= count($brands) ?> brands, from budget hatchbacks to luxury performance cars.</p>
    </div>
</header>

<div class="section">
    <div class="container">
        <?php if (!$brands): ?>
            <div class="empty-state"><div class="icon">🏭</div><h3>No brands yet</h3><p>Brands will appear here once added.</p></div>
        <?php else: ?>
            <div class="grid-cards" style="grid-template-columns:repeat(auto-fill,minmax(min(100%,170px),1fr))">
                <?php foreach ($brands as $b) brand_tile($b); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
