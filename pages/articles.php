<?php
/** AutoPulse — blog index (Module 10). */
$page = get_int('page', 1);
$catSlug = trim((string)($_GET['category'] ?? ''));
$result = articles_query(array_filter(['category' => $catSlug, 'page' => $page], fn($v) => $v !== ''));

seo_set([
    'title' => 'Articles, News & Guides',
    'description' => 'Car reviews, automotive news, buying guides and maintenance advice from the ' . setting('site_name', 'AutoPulse') . ' editorial team.',
]);

include __DIR__ . '/../includes/header.php';
?>
<header class="page-head">
    <div class="container">
        <?php render_breadcrumbs([['name' => 'Home', 'url' => ''], ['name' => 'Articles']]); ?>
        <h1>Articles &amp; News</h1>
        <p><?= (int)$result['total'] ?> published stories — reviews, guides and everything happening in the auto world.</p>
    </div>
</header>

<div class="section">
    <div class="container">
        <?php ad_slot('home_mid'); ?>
        <?php if (!$result['items']): ?>
            <div class="empty-state">
                <div class="icon">📰</div>
                <h3>No articles yet</h3>
                <p>New stories are on the way — check back soon.</p>
            </div>
        <?php else: ?>
            <div class="grid-cards">
                <?php foreach ($result['items'] as $a) article_card($a); ?>
            </div>
            <?php render_pagination($result['page'], $result['pages'], 'articles', $catSlug ? ['category' => $catSlug] : []); ?>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
