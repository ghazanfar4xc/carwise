<?php
/**
 * AutoPulse — reusable card components (Module 42).
 * Every listing reuses these renderers so cards stay consistent site-wide.
 */
if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

function car_card(array $car, bool $lazy = true): void
{
    $img = img_url($car['main_image'] ?? '');
    ?>
    <article class="car-card card-hover">
        <a href="<?= e(car_url($car)) ?>" class="car-card-media" tabindex="-1" aria-hidden="true">
            <img src="<?= e($img) ?>" alt="<?= e(($car['brand'] ?? '') . ' ' . $car['name']) ?>"
                 width="640" height="420" <?= $lazy ? 'loading="lazy"' : '' ?>>
            <?php if (($car['is_popular'] ?? 0) && ($car['is_popular'] ?? 0) == 1): ?><span class="badge badge-hot">Popular</span><?php endif; ?>
            <span class="badge badge-body"><?= e($car['body_type'] ?? '') ?></span>
        </a>
        <div class="car-card-body">
            <p class="car-card-brand"><?= e($car['brand'] ?? '') ?> · <?= e((string)($car['year_start'] ?? '')) ?></p>
            <h3 class="car-card-title"><a href="<?= e(car_url($car)) ?>"><?= e($car['name']) ?></a></h3>
            <p class="car-card-tagline"><?= e($car['tagline'] ?? '') ?></p>
            <div class="car-card-meta">
                <span><?= e($car['power_hp'] ? $car['power_hp'] . ' hp' : ($car['engine'] ?: '—')) ?></span>
                <span><?= e($car['fuel_type'] ?: '—') ?></span>
                <span><?= e($car['transmission'] ?: '—') ?></span>
            </div>
            <div class="car-card-foot">
                <span class="car-card-price"><?= format_price($car['price'] ?? 0, $car['price_note'] ?? '') ?></span>
                <button class="btn btn-sm btn-ghost add-compare" data-id="<?= (int)$car['id'] ?>" type="button">+ Compare</button>
            </div>
        </div>
    </article>
    <?php
}

function article_card(array $a, bool $lazy = true, string $class = ''): void
{
    $img = img_url($a['featured_image'] ?? '');
    ?>
    <article class="article-card card-hover <?= e($class) ?>">
        <a href="<?= e(article_url($a)) ?>" class="article-card-media" tabindex="-1" aria-hidden="true">
            <img src="<?= e($img) ?>" alt="<?= e($a['title']) ?>" width="640" height="400" <?= $lazy ? 'loading="lazy"' : '' ?>>
        </a>
        <div class="article-card-body">
            <?php if (!empty($a['category'])): ?>
                <a class="tag" href="<?= e(category_url(['slug' => $a['category_slug']])) ?>"><?= e($a['category']) ?></a>
            <?php endif; ?>
            <h3 class="article-card-title"><a href="<?= e(article_url($a)) ?>"><?= e($a['title']) ?></a></h3>
            <p class="article-card-excerpt"><?= e(excerpt_text((string)($a['excerpt'] ?? ''), 120)) ?></p>
            <div class="meta">
                <?php if (!empty($a['author'])): ?><span>By <?= e($a['author']) ?></span><span aria-hidden="true">·</span><?php endif; ?>
                <span><?= e(format_date($a['published_at'])) ?></span>
                <span aria-hidden="true">·</span>
                <span><?= reading_time((string)$a['excerpt']) ?> min read</span>
            </div>
        </div>
    </article>
    <?php
}

function brand_tile(array $b): void
{
    ?>
    <a href="<?= e(brand_url($b)) ?>" class="brand-tile card-hover">
        <img src="<?= e(img_url($b['logo'] ?? '', 'assets/images/brands/default.svg')) ?>" alt="<?= e($b['name']) ?> logo" width="72" height="72" loading="lazy">
        <strong><?= e($b['name']) ?></strong>
        <span><?= (int)($b['model_count'] ?? 0) ?> models</span>
    </a>
    <?php
}

function section_head(string $title, ?string $link = null, string $linkLabel = 'View all'): void
{
    echo '<div class="section-head reveal"><h2>' . e($title) . '</h2>';
    if ($link) echo '<a class="link-arrow" href="' . e(url($link)) . '">' . e($linkLabel) . ' <span aria-hidden="true">→</span></a>';
    echo '</div>';
}
