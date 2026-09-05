<?php
/** AutoPulse — author profile page (/author/{slug}). Shows writer bio + all published articles. */
$author = get_author($params['slug'] ?? '');
if (!$author) {
    http_response_code(404);
    $SEO = ['title' => 'Author not found', 'robots' => 'noindex, follow'];
    include __DIR__ . '/404.php';
    return;
}

$authorArticles = articles_query(['author_id' => (int)$author['id'], 'per' => 24]);
$role = $author['role'] === 'admin' ? 'Editor-in-Chief' : 'Senior Editor';

seo_set([
    'title'       => $author['name'] . ' — Author Profile',
    'description' => $author['bio'] ? excerpt_text($author['bio'], 150) : 'Articles and reviews by ' . $author['name'] . '.',
]);

$avatar = $author['avatar'] ?: '';

$personSchema = array_filter([
    '@context'  => 'https://schema.org',
    '@type'     => 'Person',
    'name'      => $author['name'],
    'url'       => author_url(['slug' => $author['slug']]),
    'image'     => $avatar ? abs_url('/' . ltrim($avatar, '/')) : null,
    'jobTitle'  => $author['role'] === 'admin' ? 'Editor-in-Chief' : 'Senior Editor',
    'worksFor'  => ['@type' => 'Organization', 'name' => setting('site_name', 'AutoPulse')],
], fn($v) => $v !== null && $v !== '');
if (!empty($author['bio'])) $personSchema['description'] = $author['bio'];
if (!empty($author['expertise'])) $personSchema['knowsAbout'] = array_map('trim', explode(',', $author['expertise']));
$sameAs = array_values(array_filter(array_diff([$author['social_twitter'] ?? '', $author['social_linkedin'] ?? ''], [''])));
if ($sameAs) $personSchema['sameAs'] = $sameAs;
seo_jsonld($personSchema);

include __DIR__ . '/../includes/header.php';
?>
<header class="page-head">
    <div class="container">
        <?php render_breadcrumbs([['name' => 'Home', 'url' => ''], ['name' => 'Authors'], ['name' => $author['name']]]); ?>
        <div class="author-profile">
            <?php if ($avatar): ?>
                <img class="author-avatar-lg" src="<?= e(img_url($avatar)) ?>" alt="<?= e($author['name']) ?>" width="96" height="96">
            <?php else: ?>
                <span class="author-avatar-lg author-avatar-fallback" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($author['name'], 0, 1))) ?></span>
            <?php endif; ?>
            <div>
                <h1><?= e($author['name']) ?></h1>
                <div class="article-header-meta">
                    <span class="tag"><?= e($role) ?></span>
                    <span><?= (int)$author['article_count'] ?> article<?= (int)$author['article_count'] === 1 ? '' : 's' ?></span>
                    <?php if (!empty($author['expertise'])): ?><span><?= e($author['expertise']) ?></span><?php endif; ?>
                    <span>Joined <?= e(format_date($author['created_at'])) ?></span>
                </div>
            </div>
        </div>
        <?php if ($author['bio']): ?>
            <p class="author-bio"><?= e($author['bio']) ?></p>
        <?php endif; ?>
    </div>
</header>

<div class="section">
    <div class="container">
        <?php section_head('Articles by ' . $author['name']); ?>
        <?php if ($articles = $authorArticles['items']): ?>
            <div class="card-grid">
                <?php foreach ($articles as $a): article_card($a); endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">✍️</div>
                <h3>No published articles yet</h3>
                <p>Articles by <?= e($author['name']) ?> will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
