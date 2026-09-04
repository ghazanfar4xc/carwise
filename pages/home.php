<?php
/** AutoPulse — homepage (Module 07). Sections are ordered/toggled from Admin → Settings. */
seo_default_schema();

$sections = json_decode(setting('homepage_sections'), true) ?: [
    ['key' => 'hero', 'label' => 'Hero', 'enabled' => 1, 'head' => ''],
    ['key' => 'latest_cars', 'label' => 'Latest Cars', 'enabled' => 1, 'head' => 'Latest Cars'],
    ['key' => 'latest_articles', 'label' => 'Latest Articles', 'enabled' => 1, 'head' => 'Latest Articles'],
    ['key' => 'popular_cars', 'label' => 'Popular Cars', 'enabled' => 1, 'head' => 'Popular Cars'],
    ['key' => 'brands', 'label' => 'Brands', 'enabled' => 1, 'head' => 'Browse by Brand'],
    ['key' => 'compare_cta', 'label' => 'Compare CTA', 'enabled' => 1, 'head' => ''],
    ['key' => 'news', 'label' => 'News', 'enabled' => 1, 'head' => 'Latest Automotive News'],
    ['key' => 'guides', 'label' => 'Buying Guides', 'enabled' => 1, 'head' => 'Buying Guides'],
    ['key' => 'newsletter', 'label' => 'Newsletter', 'enabled' => 1, 'head' => ''],
];

$featured = articles_query(['featured' => 1, 'per' => 3])['items'];
$latestCars = cars_query(['per' => 6])['items'];
$popular = popular_cars(6);
$latestArticles = articles_query(['per' => 6])['items'];
$news = articles_query(['category' => 'car-news', 'per' => 3])['items'];
$guides = articles_query(['category' => 'buying-guides', 'per' => 3])['items'];
$brands = get_brands();
$totalCars = cars_query(['per' => 1])['total'];

include __DIR__ . '/../includes/header.php';

foreach ($sections as $sec):
    if (empty($sec['enabled'])) continue;
    $head = $sec['head'] ?? '';
    switch ($sec['key']):

    case 'hero': ?>
<section class="hero">
    <div class="hero-bg"><img src="<?= e(img_url(setting('hero_image') ?: 'uploads/general/hero.jpg')) ?>" alt="" width="1920" height="1080" fetchpriority="high"></div>
    <div class="container hero-inner">
        <span class="hero-kicker"><?= e(setting('tagline', 'Cars · Specs · Prices · Reviews')) ?></span>
        <h1>Find your next car with confidence.</h1>
        <p>Independent specifications, honest reviews and up-to-date prices — everything you need before you visit the showroom.</p>
        <div class="hero-cta">
            <a href="<?= e(url('cars')) ?>" class="btn btn-primary btn-lg">Browse Cars</a>
            <a href="<?= e(url('compare')) ?>" class="btn btn-lg" style="background:rgba(255,255,255,.12);color:#fff">Compare Models</a>
        </div>
        <div class="hero-stats">
            <div class="hero-stat"><b><?= (int)$totalCars ?>+</b><span>Models catalogued</span></div>
            <div class="hero-stat"><b><?= count($brands) ?></b><span>Brands covered</span></div>
            <div class="hero-stat"><b><?= number_format((int)(db()->query('SELECT COALESCE(SUM(views),0) FROM articles')->fetchColumn())) ?></b><span>Readers served</span></div>
        </div>
    </div>
</section>
<?php if ($featured): ?>
<section class="section" aria-label="Featured article">
    <div class="container">
        <?php article_card(array_merge($featured[0], ['excerpt' => $featured[0]['excerpt']]), false, 'article-card-featured'); ?>
    </div>
</section>
<?php endif; ?>
<?php ad_slot('home_top', 'container'); ?>
<?php break;

    case 'latest_cars': ?>
<section class="section">
    <div class="container">
        <?php section_head($head ?: 'Latest Cars', 'cars'); ?>
        <div class="grid-cards reveal">
            <?php foreach (array_slice($latestCars, 0, 6) as $car) car_card($car); ?>
        </div>
    </div>
</section>
<?php break;

    case 'latest_articles': ?>
<section class="section section-tint">
    <div class="container">
        <?php section_head($head ?: 'Latest Articles', 'articles'); ?>
        <div class="grid-cards reveal">
            <?php foreach (array_slice($latestArticles, 0, 3) as $a) article_card($a); ?>
        </div>
    </div>
</section>
<?php ad_slot('home_mid', 'container'); ?>
<?php break;

    case 'popular_cars': ?>
<section class="section">
    <div class="container">
        <?php section_head($head ?: 'Popular Cars', 'cars?sort=popular', 'Most viewed'); ?>
        <div class="grid-cards reveal">
            <?php foreach (array_slice($popular, 0, 6) as $car) car_card($car); ?>
        </div>
    </div>
</section>
<?php break;

    case 'brands': ?>
<section class="section section-tint">
    <div class="container">
        <?php section_head($head ?: 'Browse by Brand', 'brands'); ?>
        <div class="grid-cards reveal" style="grid-template-columns:repeat(auto-fill,minmax(min(100%,150px),1fr))">
            <?php foreach ($brands as $b) brand_tile($b); ?>
        </div>
    </div>
</section>
<?php break;

    case 'compare_cta': ?>
<section class="section">
    <div class="container">
        <div class="compare-cta reveal">
            <div>
                <h2>Can't decide? Let the specs decide.</h2>
                <p>Put up to three cars side by side — price, power, economy and dimensions — and see the real differences highlighted automatically.</p>
            </div>
            <a href="<?= e(url('compare')) ?>" class="btn btn-primary btn-lg">Start Comparing</a>
        </div>
    </div>
</section>
<?php break;

    case 'news': ?>
<section class="section section-tint">
    <div class="container">
        <?php section_head($head ?: 'Latest Automotive News', 'category/car-news'); ?>
        <div class="grid-cards reveal">
            <?php foreach ($news as $a) article_card($a); ?>
        </div>
    </div>
</section>
<?php break;

    case 'guides': ?>
<section class="section">
    <div class="container">
        <?php section_head($head ?: 'Buying Guides', 'category/buying-guides'); ?>
        <div class="grid-cards reveal">
            <?php foreach ($guides as $a) article_card($a); ?>
        </div>
    </div>
</section>
<?php break;

    case 'newsletter': ?>
<section class="section section-tint">
    <div class="container">
        <div class="newsletter-box reveal">
            <div>
                <h2>Never miss an update</h2>
                <p>New models, price changes and buying guides — straight to your inbox. No spam, unsubscribe anytime.</p>
            </div>
            <form class="newsletter-form" id="newsletter-form" novalidate>
                <label class="sr-only" for="newsletter-email">Email address</label>
                <input type="email" id="newsletter-email" name="email" class="input" placeholder="you@example.com" required>
                <button type="submit" class="btn btn-primary">Subscribe</button>
            </form>
        </div>
    </div>
</section>
<?php break;
    endswitch;
endforeach;

include __DIR__ . '/../includes/footer.php';
