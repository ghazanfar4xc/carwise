<?php
/** AutoPulse admin — CMS page editor. */
require __DIR__ . '/includes/bootstrap.php';
require_admin();

$id = get_int('id', 0);
$page = null;
if ($id) {
    $st = db()->prepare('SELECT * FROM pages WHERE id = ?');
    $st->execute([$id]);
    $page = $st->fetch();
    if (!$page) { flash_set('error', 'Page not found.'); redirect('admin/pages.php'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mb_substr(post('title'), 0, 150);
    if ($title === '') { flash_set('error', 'Title is required.'); redirect('admin/page-edit.php' . ($id ? '?id=' . $id : '')); }
    $slug = unique_slug(db(), 'pages', slugify(post('slug') ?: $title), $id);
    $data = [
        'title'            => $title,
        'slug'             => $slug,
        'content'          => (string)($_POST['content'] ?? ''),
        'status'           => post('status') === 'draft' ? 'draft' : 'published',
        'show_in_footer'   => post('show_in_footer') === '1' ? 1 : 0,
        'sort_order'       => (int)post('sort_order'),
        'seo_title'        => mb_substr(post('seo_title'), 0, 150) ?: null,
        'meta_description' => mb_substr(post('meta_description'), 0, 300) ?: null,
    ];
    if ($id) {
        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
        db()->prepare("UPDATE pages SET $set WHERE id = ?")->execute([...array_values($data), $id]);
        flash_set('success', 'Page updated.');
    } else {
        db()->prepare('INSERT INTO pages (' . implode(', ', array_keys($data)) . ') VALUES (' . implode(', ', array_fill(0, count($data), '?')) . ')')->execute(array_values($data));
        $id = (int)db()->lastInsertId();
        flash_set('success', 'Page created.');
    }
    cache_forget('footer_pages');
    cache_forget('sitemap');
    redirect('admin/page-edit.php?id=' . $id);
}

$ADMIN_ACTIVE = 'pages';
$ADMIN_TITLE = $page ? 'Edit page: ' . $page['title'] : 'New page';
include __DIR__ . '/includes/header.php';
?>
<form method="post" class="admin-form">
    <?= csrf_field() ?>
    <div class="edit-layout">
        <div class="edit-main">
            <section class="panel">
                <div class="form-field">
                    <label for="p-title">Title *</label>
                    <input type="text" id="p-title" name="title" class="input input-lg" required value="<?= fv('title', $page['title'] ?? '') ?>" data-slug-source="#p-slug">
                </div>
                <div class="form-row cols-2">
                    <div class="form-field">
                        <label for="p-slug">Slug</label>
                        <input type="text" id="p-slug" name="slug" class="input" value="<?= fv('slug', $page['slug'] ?? '') ?>">
                        <p class="hint">URL: /<span class="slug-preview"><?= e($page['slug'] ?? 'your-slug') ?></span></p>
                    </div>
                    <div class="form-field">
                        <label for="p-sort">Footer sort order</label>
                        <input type="number" id="p-sort" name="sort_order" class="input" value="<?= fv('sort_order', $page['sort_order'] ?? 0) ?>">
                    </div>
                </div>
                <div class="form-field">
                    <label for="p-content">Content (HTML allowed)</label>
                    <textarea id="p-content" name="content" class="textarea" rows="16" style="font-family:ui-monospace,monospace;font-size:.9rem"><?= e($_POST['content'] ?? $page['content'] ?? '') ?></textarea>
                </div>
            </section>
            <section class="panel">
                <div class="panel-head"><h2>SEO</h2></div>
                <div class="form-field"><label for="p-seo">SEO title</label><input type="text" id="p-seo" name="seo_title" class="input" value="<?= fv('seo_title', $page['seo_title'] ?? '') ?>"></div>
                <div class="form-field"><label for="p-meta">Meta description</label><textarea id="p-meta" name="meta_description" class="textarea" rows="2"><?= fv('meta_description', $page['meta_description'] ?? '') ?></textarea></div>
            </section>
        </div>
        <aside class="edit-side">
            <section class="panel">
                <div class="panel-head"><h2>Publish</h2></div>
                <div class="form-field">
                    <label for="p-status">Status</label>
                    <select id="p-status" name="status" class="select">
                        <?php $cur = (string)($_POST['status'] ?? $page['status'] ?? 'published'); ?>
                        <option value="published" <?= $cur === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= $cur === 'draft' ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>
                <label class="check"><input type="checkbox" name="show_in_footer" value="1" <?= ($_POST['show_in_footer'] ?? $page['show_in_footer'] ?? 0) ? 'checked' : '' ?>> Show link in footer</label>
                <button type="submit" class="btn btn-primary btn-block"><?= $page ? 'Save page' : 'Create page' ?></button>
            </section>
        </aside>
    </div>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>
