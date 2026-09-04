<?php
/** AutoPulse — single article (Module 10, 43). */
$article = get_article($params['slug'] ?? '');
if (!$article) {
    http_response_code(404);
    $SEO = ['title' => 'Article not found', 'robots' => 'noindex, follow'];
    include __DIR__ . '/404.php';
    return;
}
db()->prepare('UPDATE articles SET views = views + 1 WHERE id = ?')->execute([$article['id']]);

[$content, $toc] = content_ids($article['content']);
$faq = faq_items($article['faq']);
$comments = setting('comments_enabled', '1') === '1' ? get_comments((int)$article['id']) : [];

seo_set([
    'title'       => $article['seo_title'] ?: $article['title'],
    'description' => $article['meta_description'] ?: excerpt_text((string)$article['excerpt'], 155),
    'og_type'     => 'article',
    'og_image'    => $article['featured_image'] ?: 'uploads/general/hero.jpg',
    'og_title'    => $article['title'],
    'og_desc'     => $article['meta_description'] ?: excerpt_text((string)$article['excerpt'], 155),
]);
render_breadcrumbs([
    ['name' => 'Home', 'url' => ''],
    ['name' => 'Articles', 'url' => 'articles'],
    ...($article['category'] ? [['name' => $article['category'], 'url' => 'category/' . $article['category_slug']]] : []),
    ['name' => $article['title']],
]);
faq_schema($faq);
seo_jsonld([
    '@context'      => 'https://schema.org',
    '@type'         => 'Article',
    'headline'      => $article['title'],
    'description'   => excerpt_text((string)$article['excerpt'], 200),
    'image'         => abs_url('/' . ltrim($article['featured_image'] ?: 'uploads/general/hero.jpg', '/')),
    'datePublished' => date('c', strtotime($article['published_at'])),
    'dateModified'  => date('c', strtotime($article['updated_at'])),
    'author'        => ['@type' => 'Person', 'name' => $article['author'] ?: setting('site_name', 'AutoPulse')],
    'publisher'     => ['@type' => 'Organization', 'name' => setting('site_name', 'AutoPulse')],
    'mainEntityOfPage' => abs_url('/articles/' . $article['slug']),
]);
$related = related_articles($article, 3);

include __DIR__ . '/../includes/header.php';
?>
<article class="section">
    <div class="container">
        <?php render_breadcrumbs([
            ['name' => 'Home', 'url' => ''],
            ['name' => 'Articles', 'url' => 'articles'],
            ...($article['category'] ? [['name' => $article['category'], 'url' => 'category/' . $article['category_slug']]] : []),
            ['name' => $article['title']],
        ]); ?>

        <div class="article-layout">
            <div>
                <header class="article-header">
                    <?php if ($article['category']): ?>
                        <a class="tag" href="<?= e(category_url(['slug' => $article['category_slug']])) ?>"><?= e($article['category']) ?></a>
                    <?php endif; ?>
                    <h1 style="font-family:var(--font-body);font-weight:800;line-height:1.18;font-size:clamp(1.6rem,1.2rem + 2.2vw,2.5rem)"><?= e($article['title']) ?></h1>
                    <div class="article-header-meta">
                        <span>By <strong><?= e($article['author'] ?: 'Editorial Team') ?></strong></span>
                        <span aria-hidden="true">·</span>
                        <time datetime="<?= e(date('c', strtotime($article['published_at']))) ?>"><?= e(format_date($article['published_at'])) ?></time>
                        <span aria-hidden="true">·</span>
                        <span><?= reading_time($article['content']) ?> min read</span>
                        <span aria-hidden="true">·</span>
                        <span><?= (int)$article['views'] ?> views</span>
                    </div>
                </header>

                <figure class="article-featured" style="margin-inline:0">
                    <img src="<?= e(img_url($article['featured_image'])) ?>" alt="<?= e($article['title']) ?>" width="1200" height="640" fetchpriority="high">
                </figure>

                <?php if ($toc && count($toc) >= 3): ?>
                <nav class="toc" aria-label="Table of contents">
                    <h2>In this article</h2>
                    <ol>
                        <?php foreach ($toc as $t): ?>
                            <li style="margin-left:<?= ($t['level'] - 2) * 1 ?>em"><a href="#<?= e($t['id']) ?>"><?= e($t['title']) ?></a></li>
                        <?php endforeach; ?>
                    </ol>
                </nav>
                <?php endif; ?>

                <?php ad_slot('article_top'); ?>

                <div class="prose" id="article-content">
                    <?= $content /* admin-authored HTML */ ?>
                </div>

                <?php ad_slot('article_bottom'); ?>

                <?php if ($article['tags']): ?>
                <div class="tag-row" style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:1.6rem">
                    <?php foreach ($article['tags'] as $t): ?>
                        <a class="tag" href="<?= e(url('search')) ?>?q=<?= e(rawurlencode($t)) ?>">#<?= e($t) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="share-buttons" data-share-url="<?= e(abs_url('articles/' . $article['slug'])) ?>" data-share-title="<?= e($article['title']) ?>">
                    <span style="align-self:center;font-weight:600;font-size:var(--fs-sm)">Share:</span>
                    <button type="button" class="btn btn-outline btn-sm" data-share="facebook">Facebook</button>
                    <button type="button" class="btn btn-outline btn-sm" data-share="twitter">X / Twitter</button>
                    <button type="button" class="btn btn-outline btn-sm" data-share="whatsapp">WhatsApp</button>
                    <button type="button" class="btn btn-outline btn-sm" data-share="copy">Copy link</button>
                </div>

                <?php if ($faq): ?>
                <section>
                    <?php section_head('FAQ'); ?>
                    <div class="faq-list">
                        <?php foreach ($faq as $i => $item): ?>
                        <details class="faq-item" <?= $i === 0 ? 'open' : '' ?>>
                            <summary><?= e($item['q']) ?></summary>
                            <div class="faq-answer"><?= e($item['a']) ?></div>
                        </details>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
            </div>

            <aside class="article-sidebar" aria-label="Sidebar">
                <?php ad_slot('sidebar'); ?>
                <?php if ($article['brand']): ?>
                <div class="key-spec" style="text-align:left;margin-bottom:1.4rem">
                    <span>About this brand</span>
                    <b><?= e($article['brand']) ?></b>
                    <a class="link-arrow" href="<?= e(url('brands/' . $article['brand_slug'])) ?>">View brand →</a>
                </div>
                <?php endif; ?>
                <?php if ($related): ?>
                <h4 class="sidebar-title">Related reading</h4>
                <?php foreach ($related as $r): ?>
                    <a class="sidebar-article" href="<?= e(article_url($r)) ?>">
                        <img src="<?= e(img_url($r['featured_image'])) ?>" alt="" width="88" height="66" loading="lazy">
                        <span><?= e($r['title']) ?></span>
                    </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </aside>
        </div>

        <?php if ($related): ?>
        <section style="margin-top:2.8rem">
            <?php section_head('More Articles', 'articles'); ?>
            <div class="grid-cards reveal"><?php foreach ($related as $r) article_card($r); ?></div>
        </section>
        <?php endif; ?>

        <?php if (setting('comments_enabled', '1') === '1'): ?>
        <section id="comments" style="margin-top:2.8rem">
            <?php section_head('Comments (' . count($comments) . ')'); ?>
            <div id="comment-list">
                <?php if (!$comments): ?>
                    <p class="result-count">No comments yet — be the first to share your thoughts.</p>
                <?php else: foreach ($comments as $c): ?>
                    <div class="comment">
                        <div class="comment-head">
                            <span class="comment-avatar"><?= e(strtoupper(mb_substr($c['author_name'], 0, 1))) ?></span>
                            <span class="comment-name"><?= e($c['author_name']) ?></span>
                            <span class="comment-date"><?= e(time_ago($c['created_at'])) ?></span>
                        </div>
                        <p class="comment-body"><?= e($c['body']) ?></p>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <form id="comment-form" class="comment-form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
                <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
                <div class="form-row cols-2">
                    <div class="form-field">
                        <label for="c-name">Name</label>
                        <input type="text" id="c-name" name="author_name" class="input" required minlength="2" maxlength="60">
                        <p class="error-text">Please enter your name.</p>
                    </div>
                    <div class="form-field">
                        <label for="c-email">Email (not published)</label>
                        <input type="email" id="c-email" name="author_email" class="input" required>
                        <p class="error-text">Please enter a valid email.</p>
                    </div>
                </div>
                <div class="form-field">
                    <label for="c-body">Comment</label>
                    <textarea id="c-body" name="body" class="textarea" required minlength="4" maxlength="2000"></textarea>
                    <p class="error-text">Please write a comment.</p>
                </div>
                <button type="submit" class="btn btn-primary">Post comment</button>
                <p class="hint" style="color:var(--color-muted);font-size:var(--fs-xs);margin-top:.6rem">Comments are moderated before appearing on the site.</p>
            </form>
        </section>
        <?php endif; ?>
    </div>
</article>
<?php include __DIR__ . '/../includes/footer.php'; ?>
