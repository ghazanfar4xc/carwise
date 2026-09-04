<?php
/** AutoPulse admin — article editor (Module 16): publish, schedule, SEO, FAQ, tags. */
require __DIR__ . '/includes/bootstrap.php';
require_admin();

$id = get_int('id', 0);
$article = null;
if ($id) {
    $st = db()->prepare('SELECT * FROM articles WHERE id = ?');
    $st->execute([$id]);
    $article = $st->fetch();
    if (!$article) { flash_set('error', 'Article not found.'); redirect('admin/articles.php'); }
}

$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = post('title');
    $slugInput = post('slug');
    $slug = slugify($slugInput !== '' ? $slugInput : $title);
    if ($slug === 'item') $slug = 'article-' . time();
    $slug = unique_slug(db(), 'articles', $slug, $id);

    $publishedAt = post('published_at');
    $status = in_array(post('status'), ['draft', 'published', 'scheduled'], true) ? post('status') : 'draft';
    if ($status === 'published' && $publishedAt === '') $publishedAt = date('Y-m-d H:i:s');

    $faq = [];
    foreach (($_POST['faq_q'] ?? []) as $i => $qq) {
        $qq = trim((string)$qq); $aa = trim((string)($_POST['faq_a'][$i] ?? ''));
        if ($qq !== '' && $aa !== '') $faq[] = ['q' => $qq, 'a' => $aa];
    }

    $data = [
        'title'            => mb_substr($title, 0, 190),
        'slug'             => $slug,
        'excerpt'          => mb_substr(post('excerpt'), 0, 400),
        'content'          => (string)($_POST['content'] ?? ''),
        'featured_image'   => post('featured_image'),
        'status'           => $status,
        'is_featured'      => post('is_featured') === '1' ? 1 : 0,
        'allow_comments'   => post('allow_comments') === '1' ? 1 : 0,
        'published_at'     => $publishedAt ?: null,
        'faq'              => $faq ? json_encode($faq, JSON_UNESCAPED_UNICODE) : null,
        'seo_title'        => mb_substr(post('seo_title'), 0, 150) ?: null,
        'meta_description' => mb_substr(post('meta_description'), 0, 300) ?: null,
        'canonical_url'    => mb_substr(post('canonical_url'), 0, 255) ?: null,
        'user_id'          => (int)post('user_id') ?: ($article['user_id'] ?? current_user()['id']),
        'brand_id'         => (int)post('brand_id') ?: null,
        'category_id'      => (int)post('category_id') ?: null,
    ];

    if ($id) {
        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
        db()->prepare("UPDATE articles SET $set WHERE id = ?")->execute([...array_values($data), $id]);
        flash_set('success', 'Article updated.');
    } else {
        $cols = implode(', ', array_keys($data));
        $marks = implode(', ', array_fill(0, count($data), '?'));
        db()->prepare("INSERT INTO articles ($cols) VALUES ($marks)")->execute(array_values($data));
        $id = (int)db()->lastInsertId();
        flash_set('success', 'Article created.');
    }

    // Extra categories + tags
    db()->prepare('DELETE FROM article_categories WHERE article_id = ?')->execute([$id]);
    $stCat = db()->prepare('INSERT IGNORE INTO article_categories (article_id, category_id) VALUES (?, ?)');
    foreach ((array)($_POST['categories'] ?? []) as $cid) if (ctype_digit((string)$cid)) $stCat->execute([$id, (int)$cid]);
    if ($data['category_id']) $stCat->execute([$id, $data['category_id']]);

    db()->prepare('DELETE FROM article_tags WHERE article_id = ?')->execute([$id]);
    $stTag = db()->prepare('INSERT IGNORE INTO article_tags (article_id, tag_id) SELECT ?, id FROM tags WHERE id = ?');
    foreach ((array)($_POST['tags'] ?? []) as $tid) if (ctype_digit((string)$tid)) $stTag->execute([$id, (int)$tid]);

    cache_forget('sitemap');
    redirect('admin/article-edit.php?id=' . $id);
}

$categories = get_categories(null);
$carCats = get_categories('car');
$articleCats = get_categories('article');
$brands = get_brands();
$users = db()->query('SELECT id, username, display_name FROM users WHERE status = 1')->fetchAll();
$allTags = db()->query('SELECT id, name FROM tags ORDER BY name')->fetchAll();
$currentCatIds = $id ? db()->prepare('SELECT category_id FROM article_categories WHERE article_id = ?') : null;
if ($currentCatIds) { $currentCatIds->execute([$id]); $currentCatIds = $currentCatIds->fetchAll(PDO::FETCH_COLUMN); }
$currentTagIds = null;
if ($id) {
    $st = db()->prepare('SELECT tag_id FROM article_tags WHERE article_id = ?');
    $st->execute([$id]);
    $currentTagIds = $st->fetchAll(PDO::FETCH_COLUMN);
}
$faq = $article ? faq_items($article['faq']) : [];

$ADMIN_ACTIVE = 'articles';
$ADMIN_TITLE = $article ? 'Edit article' : 'New article';
include __DIR__ . '/includes/header.php';
?>
<form method="post" class="admin-form" novalidate>
    <?= csrf_field() ?>
    <div class="edit-layout">
        <div class="edit-main">
            <section class="panel">
                <div class="form-field">
                    <label for="a-title">Title *</label>
                    <input type="text" id="a-title" name="title" class="input input-lg" required maxlength="190"
                           value="<?= fv('title', $article['title'] ?? '') ?>" data-slug-source="#a-slug">
                </div>
                <div class="form-row cols-2">
                    <div class="form-field">
                        <label for="a-slug">Slug (URL)</label>
                        <input type="text" id="a-slug" name="slug" class="input" value="<?= fv('slug', $article['slug'] ?? '') ?>" pattern="[a-z0-9\-]*">
                        <p class="hint">URL: /articles/<span class="slug-preview"><?= e($article['slug'] ?? 'your-slug') ?></span></p>
                    </div>
                    <div class="form-field">
                        <label for="a-author">Author</label>
                        <select id="a-author" name="user_id" class="select">
                            <?php foreach ($users as $u): ?>
                                <option value="<?= (int)$u['id'] ?>" <?= (int)fv_raw('user_id', $article['user_id'] ?? null) === (int)$u['id'] ? 'selected' : '' ?>><?= e(($u['display_name'] ?? '') ?: $u['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-field">
                    <label for="a-excerpt">Excerpt</label>
                    <textarea id="a-excerpt" name="excerpt" class="textarea" rows="2" maxlength="400"><?= fv('excerpt', $article['excerpt'] ?? '') ?></textarea>
                    <p class="hint">Short summary shown on cards and in search results.</p>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><h2>Content</h2></div>
                <div class="editor-toolbar" data-target="a-content">
                    <button type="button" data-wrap="b"><b>B</b></button>
                    <button type="button" data-wrap="i"><i>I</i></button>
                    <button type="button" data-block="h2">H2</button>
                    <button type="button" data-block="h3">H3</button>
                    <button type="button" data-block="blockquote">❝</button>
                    <button type="button" data-list="ul">• List</button>
                    <button type="button" data-link="1">🔗 Link</button>
                    <button type="button" data-media="1">🖼 Image</button>
                    <button type="button" data-block="p">¶</button>
                </div>
                <textarea id="a-content" name="content" class="textarea editor" rows="18" placeholder="Write the article in HTML…" style="font-family:ui-monospace,monospace;font-size:.9rem"><?= e($_POST['content'] ?? $article['content'] ?? '') ?></textarea>
                <p class="hint">HTML is allowed. Use the toolbar to insert tags around selected text.</p>
            </section>

            <section class="panel">
                <div class="panel-head"><h2>FAQ (optional)</h2><button type="button" class="btn btn-sm btn-outline faq-add">+ Add question</button></div>
                <div class="faq-rows">
                    <?php foreach ($faq as $i => $f): ?>
                        <div class="faq-row">
                            <input type="text" name="faq_q[]" class="input" placeholder="Question" value="<?= e($f['q']) ?>">
                            <textarea name="faq_a[]" class="textarea" rows="2" placeholder="Answer"><?= e($f['a']) ?></textarea>
                            <button type="button" class="btn btn-sm btn-ghost faq-remove">Remove</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><h2>SEO</h2></div>
                <div class="form-field">
                    <label for="a-seo-title">SEO title</label>
                    <input type="text" id="a-seo-title" name="seo_title" class="input" maxlength="150" value="<?= fv('seo_title', $article['seo_title'] ?? '') ?>">
                    <p class="hint">Google shows ~60 characters.</p>
                </div>
                <div class="form-field">
                    <label for="a-meta">Meta description</label>
                    <textarea id="a-meta" name="meta_description" class="textarea" rows="2" maxlength="300"><?= fv('meta_description', $article['meta_description'] ?? '') ?></textarea>
                </div>
                <div class="form-field">
                    <label for="a-canonical">Canonical URL (optional)</label>
                    <input type="url" id="a-canonical" name="canonical_url" class="input" value="<?= fv('canonical_url', $article['canonical_url'] ?? '') ?>" placeholder="https://…">
                </div>
            </section>
        </div>

        <aside class="edit-side">
            <section class="panel">
                <div class="panel-head"><h2>Publish</h2></div>
                <div class="form-field">
                    <label for="a-status">Status</label>
                    <select id="a-status" name="status" class="select">
                        <?php $curStatus = $_POST['status'] ?? ($article['status'] ?? 'draft'); ?>
                        <option value="draft" <?= $curStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= $curStatus === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="scheduled" <?= $curStatus === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="a-date">Publish date</label>
                    <input type="datetime-local" id="a-date" name="published_at" class="input"
                           value="<?= e($_POST['published_at'] ?? ($article['published_at'] ? date('Y-m-d\TH:i', strtotime($article['published_at'])) : '')) ?>">
                    <p class="hint">For Scheduled articles this is when it goes live.</p>
                </div>
                <label class="check"><input type="checkbox" name="is_featured" value="1" <?= ($_POST['is_featured'] ?? $article['is_featured'] ?? 0) ? 'checked' : '' ?>> Featured on homepage</label>
                <label class="check"><input type="checkbox" name="allow_comments" value="1" <?= ($_POST['allow_comments'] ?? $article['allow_comments'] ?? 1) ? 'checked' : '' ?>> Allow comments</label>
                <button type="submit" class="btn btn-primary btn-block"><?= $article ? 'Save changes' : 'Create article' ?></button>
                <?php if ($article): ?>
                    <a class="btn btn-outline btn-block" style="margin-top:.5rem" href="<?= e(url('articles/' . $article['slug'])) ?>" target="_blank">View article ↗</a>
                <?php endif; ?>
            </section>

            <section class="panel">
                <div class="panel-head"><h2>Featured image</h2></div>
                <div class="image-picker" data-input="featured_image">
                    <img class="picker-preview" src="<?= e(img_url($_POST['featured_image'] ?? $article['featured_image'] ?? '')) ?>" alt="">
                    <input type="hidden" name="featured_image" value="<?= fv('featured_image', $article['featured_image'] ?? '') ?>">
                    <div class="picker-actions">
                        <button type="button" class="btn btn-sm btn-outline picker-open">Choose image</button>
                        <button type="button" class="btn btn-sm btn-ghost picker-clear">Clear</button>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><h2>Category</h2></div>
                <div class="form-field">
                    <label for="a-category">Primary category</label>
                    <select id="a-category" name="category_id" class="select">
                        <option value="">— none —</option>
                        <?php $curCat = (int)($_POST['category_id'] ?? $article['category_id'] ?? 0); ?>
                        <?php foreach ($articleCats as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= $curCat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label>Additional categories</label>
                    <?php foreach ($categories as $c): ?>
                        <label class="check"><input type="checkbox" name="categories[]" value="<?= (int)$c['id'] ?>"
                            <?= in_array((string)$c['id'], array_map('strval', $currentCatIds ?? []), true) && (int)$c['id'] !== $curCat ? 'checked' : '' ?>> <?= e($c['name']) ?></label>
                    <?php endforeach; ?>
                </div>
                <div class="form-field">
                    <label for="a-brand">Related brand</label>
                    <select id="a-brand" name="brand_id" class="select">
                        <option value="">— none —</option>
                        <?php $curBrand = (int)($_POST['brand_id'] ?? $article['brand_id'] ?? 0); ?>
                        <?php foreach ($brands as $b): ?>
                            <option value="<?= (int)$b['id'] ?>" <?= $curBrand === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label>Tags</label>
                    <?php foreach ($allTags as $t): ?>
                        <label class="check"><input type="checkbox" name="tags[]" value="<?= (int)$t['id'] ?>"
                            <?= in_array((string)$t['id'], array_map('strval', $currentTagIds ?? []), true) ? 'checked' : '' ?>> <?= e($t['name']) ?></label>
                    <?php endforeach; ?>
                </div>
            </section>
        </aside>
    </div>
</form>
<?php
// helper used above: raw (unescaped) form value
function fv_raw(string $key, $rowValue = null)
{
    return $_POST[$key] ?? $rowValue;
}
include __DIR__ . '/includes/footer.php';
