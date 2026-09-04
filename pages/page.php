<?php
/** AutoPulse — CMS page (Module 45): /about-us, /privacy-policy, … */
$pg = get_page($params['slug'] ?? '');
if (!$pg) {
    http_response_code(404);
    $SEO = ['title' => 'Page not found', 'robots' => 'noindex, follow'];
    include __DIR__ . '/404.php';
    return;
}
[$content] = content_ids($pg['content']);
seo_set([
    'title'       => $pg['seo_title'] ?: $pg['title'],
    'description' => $pg['meta_description'] ?: excerpt_text((string)$pg['content'], 155),
]);
render_breadcrumbs([['name' => 'Home', 'url' => ''], ['name' => $pg['title']]]);

include __DIR__ . '/../includes/header.php';
?>
<header class="page-head">
    <div class="container">
        <?php render_breadcrumbs([['name' => 'Home', 'url' => ''], ['name' => $pg['title']]]); ?>
        <h1><?= e($pg['title']) ?></h1>
    </div>
</header>
<div class="section">
    <div class="container">
        <article class="prose" style="max-width:78ch;margin-inline:auto"><?= $content /* admin-authored */ ?></article>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
