<?php
/** AutoPulse — 404 / 403 page (Module 30). */
$SEO = $SEO ?? ['title' => 'Page Not Found', 'robots' => 'noindex, follow'];
$code = http_response_code() ?: 404;
seo_set($SEO + ['robots' => 'noindex, follow']);
include __DIR__ . '/../includes/header.php';
?>
<div class="container error-page">
    <div class="code"><?= $code === 403 ? '403' : '404' ?></div>
    <h1><?= $code === 403 ? 'Access forbidden' : 'This road leads nowhere' ?></h1>
    <p style="max-width:46ch;margin-inline:auto">
        <?= $code === 403
            ? 'You do not have permission to view this page.'
            : 'The page you are looking for may have been renamed, moved or never existed.' ?>
    </p>
    <div class="actions">
        <a class="btn btn-primary" href="<?= e(url('')) ?>">Go home</a>
        <a class="btn btn-outline" href="<?= e(url('cars')) ?>">Browse cars</a>
        <a class="btn btn-outline" href="<?= e(url('articles')) ?>">Latest articles</a>
    </div>
    <form method="get" action="<?= e(url('search')) ?>" role="search" style="display:flex;gap:.6rem;max-width:440px;margin:2.4rem auto 0">
        <input type="search" name="q" class="input" placeholder="Or search for it…" aria-label="Search query">
        <button type="submit" class="btn btn-dark">Search</button>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
