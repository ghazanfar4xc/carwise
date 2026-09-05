<?php
/** AutoPulse — public HTML sitemap (/sitemap), organized for humans. */
seo_set([
    'title'       => 'Sitemap — Every Car, Brand & Article',
    'description' => 'Browse every page on ' . setting('site_name', 'AutoPulse') . ': cars by brand, categories, articles, authors and information pages.',
]);

$brandsList  = db()->query('SELECT b.id, b.name, b.slug FROM brands b WHERE b.status = 1 ORDER BY b.name')->fetchAll();
$carsByBrand = [];
foreach (db()->query("SELECT c.id, c.name, c.slug, c.brand_id, c.year_start FROM car_models c WHERE c.status = 'published' ORDER BY c.year_start DESC, c.name")->fetchAll() as $c) {
    $carsByBrand[$c['brand_id']][] = $c;
}
$catList  = db()->query('SELECT name, slug, type FROM categories WHERE status = 1 ORDER BY type, sort_order')->fetchAll();
$artList  = db()->query('SELECT a.title, a.slug, a.published_at FROM articles a WHERE ' . ARTICLE_LIVE . ' ORDER BY a.published_at DESC')->fetchAll();
$pageList = footer_pages();
$authorRows = db()->query("SELECT COALESCE(NULLIF(display_name,''), username) AS name, slug FROM users WHERE status = 1 AND slug IS NOT NULL")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<header class="page-head">
    <div class="container">
        <?php render_breadcrumbs([['name' => 'Home', 'url' => ''], ['name' => 'Sitemap']]); ?>
        <h1>Sitemap</h1>
        <p>Every page on <?= e(setting('site_name', 'AutoPulse')) ?>, organized by section.</p>
    </div>
</header>

<div class="section sitemap-page">
    <div class="container">
        <section>
            <?php section_head('Cars by Brand'); ?>
            <?php foreach ($brandsList as $b): ?>
                <h3 class="tag"><a href="<?= e(url('brands/' . $b['slug'])) ?>" style="text-decoration:none"><?= e($b['name']) ?></a></h3>
                <?php if (!empty($carsByBrand[$b['id']])): ?>
                <ul>
                    <?php foreach ($carsByBrand[$b['id']] as $c): ?>
                    <li><a href="<?= e(url('cars/' . $b['slug'] . '/' . $c['slug'])) ?>"><?= e($c['year_start'] . ' ' . $b['name'] . ' ' . $c['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            <?php endforeach; ?>
        </section>

        <section>
            <?php section_head('Categories'); ?>
            <ul>
                <?php foreach ($catList as $c): ?>
                <li><a href="<?= e(category_url($c)) ?>"><?= e($c['name']) ?> <small>(<?= $c['type'] === 'car' ? 'cars' : 'articles' ?>)</small></a></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section>
            <?php section_head('Articles'); ?>
            <ul>
                <?php foreach ($artList as $a): ?>
                <li><a href="<?= e(url('articles/' . $a['slug'])) ?>"><?= e($a['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section>
            <?php section_head('Authors'); ?>
            <ul>
                <?php foreach ($authorRows as $au): ?>
                <li><a href="<?= e(url('author/' . $au['slug'])) ?>"><?= e($au['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section>
            <?php section_head('Information Pages'); ?>
            <ul>
                <?php foreach ($pageList as $p): ?>
                <li><a href="<?= e(url($p['slug'])) ?>"><?= e($p['title']) ?></a></li>
                <?php endforeach; ?>
                <li><a href="<?= e(url('contact')) ?>">Contact Us</a></li>
                <li><a href="<?= e(url('sitemap.xml')) ?>">XML sitemap (for search engines)</a></li>
            </ul>
        </section>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
