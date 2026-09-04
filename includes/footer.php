<?php
/** AutoPulse — site footer (Module 31). */
if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

$siteName = setting('site_name', 'AutoPulse');
$cats = array_slice(get_categories('article'), 0, 5);
$legal = footer_pages();
$socials = array_filter([
    'Facebook'  => setting('social_facebook'),
    'Twitter'   => setting('social_twitter'),
    'Instagram' => setting('social_instagram'),
    'YouTube'   => setting('social_youtube'),
]);
?>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-col footer-about">
            <?php render_logo('logo-svg logo-footer', 32) ?>
            <p><?= e(setting('footer_about', 'Your trusted source for car specifications, prices, reviews and automotive news.')) ?></p>
            <div class="footer-contact">
                <?php if (setting('contact_email')): ?><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a><?php endif; ?>
                <?php if (setting('contact_phone')): ?><span><?= e(setting('contact_phone')) ?></span><?php endif; ?>
            </div>
        </div>
        <nav class="footer-col" aria-label="Footer navigation">
            <h3>Explore</h3>
            <ul>
                <?php foreach (menu_tree('main') as $item): ?>
                    <li><a href="<?= e(str_starts_with($item['url'], 'http') ? $item['url'] : url($item['url'])) ?>"><?= e($item['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <nav class="footer-col" aria-label="Categories">
            <h3>Categories</h3>
            <ul>
                <?php foreach ($cats as $c): ?>
                    <li><a href="<?= e(category_url($c)) ?>"><?= e($c['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <div class="footer-col">
            <h3>Company</h3>
            <ul>
                <?php foreach ($legal as $p): ?>
                    <li><a href="<?= e(url($p['slug'])) ?>"><?= e($p['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <?php if ($socials): ?>
            <div class="social-links">
                <?php foreach ($socials as $name => $link): ?>
                    <a href="<?= e($link) ?>" rel="noopener" target="_blank" aria-label="<?= e($name) ?>"><?= e($name) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php ad_slot('footer'); ?>
    <div class="footer-bottom">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= e($siteName) ?>. <?= e(setting('copyright_text', 'All rights reserved.')) ?></p>
            <p class="footer-disclaimer">Prices and specifications are for information only and may change without notice.</p>
        </div>
    </div>
</footer>

<div class="toast-region" id="toast-region" aria-live="polite"></div>
<?php if (is_admin()): ?><a href="<?= e(url('admin/dashboard.php')) ?>" class="admin-bar-link">Admin Panel</a><?php endif; ?>

<script src="<?= e(asset('js/main.js')) ?>" defer></script>
<?= setting('footer_scripts') /* admin-controlled */ ?>
<?= flash_render() ?>
</body>
</html>
