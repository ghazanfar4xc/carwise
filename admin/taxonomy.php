<?php
/** AutoPulse admin — categories & tags manager (Modules 14, 16). */
require __DIR__ . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');
    if ($action === 'save_category') {
        $id = (int)post('id');
        $name = mb_substr(post('name'), 0, 100);
        if ($name === '') { flash_set('error', 'Name is required.'); redirect('admin/taxonomy.php'); }
        $slug = unique_slug(db(), 'categories', slugify(post('slug') ?: $name), $id);
        $data = [
            'name' => $name, 'slug' => $slug,
            'type' => post('type') === 'car' ? 'car' : 'article',
            'description' => mb_substr(post('description'), 0, 500) ?: null,
            'sort_order' => (int)post('sort_order'),
            'status' => post('status') === '0' ? 0 : 1,
            'seo_title' => mb_substr(post('seo_title'), 0, 150) ?: null,
            'meta_description' => mb_substr(post('meta_description'), 0, 300) ?: null,
        ];
        if ($id) {
            $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
            db()->prepare("UPDATE categories SET $set WHERE id = ?")->execute([...array_values($data), $id]);
        } else {
            $cols = implode(', ', array_keys($data));
            db()->prepare("INSERT INTO categories ($cols) VALUES (" . implode(', ', array_fill(0, count($data), '?')) . ')')->execute(array_values($data));
        }
        flash_set('success', 'Category saved.');
    } elseif ($action === 'delete_category') {
        db()->prepare('DELETE FROM categories WHERE id = ?')->execute([(int)post('id')]);
        flash_set('success', 'Category deleted.');
    } elseif ($action === 'add_tag') {
        $name = mb_substr(post('tag_name'), 0, 80);
        if ($name !== '') {
            $slug = unique_slug(db(), 'tags', slugify($name));
            db()->prepare('INSERT INTO tags (name, slug) VALUES (?, ?)')->execute([$name, $slug]);
            flash_set('success', 'Tag added.');
        }
    } elseif ($action === 'delete_tag') {
        db()->prepare('DELETE FROM tags WHERE id = ?')->execute([(int)post('id')]);
        flash_set('success', 'Tag deleted.');
    }
    cache_forget('sitemap');
    redirect('admin/taxonomy.php');
}

$editing = null;
if (get_int('edit', 0)) {
    $st = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $st->execute([get_int('edit', 0)]);
    $editing = $st->fetch() ?: null;
}
$categories = db()->query('SELECT c.*,
    (SELECT COUNT(*) FROM article_categories ac WHERE ac.category_id = c.id) AS articles,
    (SELECT COUNT(*) FROM car_categories cc WHERE cc.category_id = c.id) AS cars
    FROM categories c ORDER BY c.type, c.sort_order, c.name')->fetchAll();
$tags = db()->query('SELECT t.*, (SELECT COUNT(*) FROM article_tags at_ WHERE at_.tag_id = t.id) AS used FROM tags t ORDER BY t.name')->fetchAll();

$ADMIN_ACTIVE = 'taxonomy';
$ADMIN_TITLE = 'Categories & Tags';
include __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2><?= $editing ? 'Edit category: ' . e($editing['name']) : 'Add / edit category' ?></h2>
        <?php if ($editing): ?><a class="link-arrow" href="<?= e(admin_url('taxonomy.php')) ?>">+ New instead →</a><?php endif; ?>
    </div>
    <form method="post" class="admin-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_category">
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
        <div class="form-row cols-2">
            <div class="form-field"><label for="cat-name">Name *</label><input type="text" id="cat-name" name="name" class="input" required value="<?= fv('name', $editing['name'] ?? '') ?>"></div>
            <div class="form-field"><label for="cat-slug">Slug</label><input type="text" id="cat-slug" name="slug" class="input" value="<?= fv('slug', $editing['slug'] ?? '') ?>"></div>
            <div class="form-field"><label for="cat-type">Type</label>
                <select id="cat-type" name="type" class="select">
                    <?php $curType = (string)($_POST['type'] ?? $editing['type'] ?? 'article'); ?>
                    <option value="article" <?= $curType === 'article' ? 'selected' : '' ?>>Article category</option>
                    <option value="car" <?= $curType === 'car' ? 'selected' : '' ?>>Car category</option>
                </select>
            </div>
            <div class="form-field"><label for="cat-sort">Sort order</label><input type="number" id="cat-sort" name="sort_order" class="input" value="<?= fv('sort_order', $editing['sort_order'] ?? 0) ?>"></div>
        </div>
        <div class="form-field"><label for="cat-desc">Description</label><input type="text" id="cat-desc" name="description" class="input" maxlength="500" value="<?= fv('description', $editing['description'] ?? '') ?>"></div>
        <div class="form-row cols-2">
            <div class="form-field"><label for="cat-seo">SEO title</label><input type="text" id="cat-seo" name="seo_title" class="input" value="<?= fv('seo_title', $editing['seo_title'] ?? '') ?>"></div>
            <div class="form-field"><label for="cat-meta">Meta description</label><input type="text" id="cat-meta" name="meta_description" class="input" value="<?= fv('meta_description', $editing['meta_description'] ?? '') ?>"></div>
        </div>
        <label class="check"><input type="checkbox" name="status" value="1" <?= ($_POST['status'] ?? $editing['status'] ?? 1) ? 'checked' : '' ?>> Visible</label>
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save category' : 'Add category' ?></button>
    </form>
</section>

<section class="panel">
    <table class="admin-table">
        <thead><tr><th>Category</th><th>Type</th><th>Slug</th><th>Content</th><th class="th-actions">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($categories as $c): ?>
            <tr>
                <td><strong><?= e($c['name']) ?></strong></td>
                <td><span class="status status-<?= $c['type'] ?>"><?= e($c['type']) ?></span></td>
                <td><small>/category/<?= e($c['slug']) ?></small></td>
                <td><?= (int)$c['articles'] ?> articles · <?= (int)$c['cars'] ?> cars</td>
                <td class="td-actions">
                    <form method="post" class="inline-form">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete_category"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <a class="btn btn-sm btn-outline" href="<?= e(admin_url('taxonomy.php?edit=' . (int)$c['id'])) ?>">Edit</a>
                        <button class="btn btn-sm btn-danger" data-confirm="Delete this category?">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="panel">
    <div class="panel-head"><h2>Tags</h2></div>
    <form method="post" class="toolbar-search" style="max-width:420px;margin-bottom:1rem">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_tag">
        <input type="text" name="tag_name" class="input" placeholder="New tag name…" required>
        <button class="btn btn-outline" type="submit">Add</button>
    </form>
    <table class="admin-table">
        <thead><tr><th>Tag</th><th>Slug</th><th>Used in</th><th class="th-actions">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($tags as $t): ?>
            <tr>
                <td><strong><?= e($t['name']) ?></strong></td>
                <td><small><?= e($t['slug']) ?></small></td>
                <td><?= (int)$t['used'] ?> articles</td>
                <td class="td-actions">
                    <form method="post" class="inline-form">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete_tag"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                        <button class="btn btn-sm btn-danger" data-confirm="Delete this tag?">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
