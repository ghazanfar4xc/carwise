<?php
/** AutoPulse admin — brands list + editor (Module 13 admin). */
require __DIR__ . '/includes/bootstrap.php';
require_admin();

$editId = ($_GET['edit'] ?? '') === 'new' ? 'new' : get_int('edit', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('action') === 'delete' && post('id')) {
        db()->prepare('DELETE FROM brands WHERE id = ?')->execute([(int)post('id')]);
        flash_set('success', 'Brand deleted (its cars were removed with it).');
        redirect('admin/brands.php');
    }
    $id = (int)post('id');
    $name = mb_substr(post('name'), 0, 80);
    if ($name === '') { flash_set('error', 'Brand name is required.'); redirect('admin/brands.php'); }
    $slug = unique_slug(db(), 'brands', slugify(post('slug') ?: $name), $id);
    $data = [
        'name' => $name,
        'slug' => $slug,
        'country' => mb_substr(post('country'), 0, 60) ?: null,
        'founded_year' => (int)post('founded_year') ?: null,
        'logo' => post('logo') ?: null,
        'description' => (string)($_POST['description'] ?? ''),
        'featured' => post('featured') === '1' ? 1 : 0,
        'sort_order' => (int)post('sort_order'),
        'status' => post('status') === '0' ? 0 : 1,
        'seo_title' => mb_substr(post('seo_title'), 0, 150) ?: null,
        'meta_description' => mb_substr(post('meta_description'), 0, 300) ?: null,
    ];
    if ($id) {
        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
        db()->prepare("UPDATE brands SET $set WHERE id = ?")->execute([...array_values($data), $id]);
        flash_set('success', 'Brand updated.');
    } else {
        $cols = implode(', ', array_keys($data));
        $marks = implode(', ', array_fill(0, count($data), '?'));
        db()->prepare("INSERT INTO brands ($cols) VALUES ($marks)")->execute(array_values($data));
        flash_set('success', 'Brand created.');
    }
    cache_forget('sitemap');
    redirect('admin/brands.php');
}

$editing = null;
if ($editId === 'new' || $editId > 0) {
    if ($editId === 'new') {
        $editing = [];
    } else {
        $st = db()->prepare('SELECT * FROM brands WHERE id = ?');
        $st->execute([$editId]);
        $editing = $st->fetch() ?: null;
        if (!$editing) { flash_set('error', 'Brand not found.'); redirect('admin/brands.php'); }
    }
}

$brands = db()->query('SELECT b.*, (SELECT COUNT(*) FROM car_models c WHERE c.brand_id = b.id) AS cars FROM brands b ORDER BY b.sort_order, b.name')->fetchAll();

$ADMIN_ACTIVE = 'brands';
$ADMIN_TITLE = 'Brands';
include __DIR__ . '/includes/header.php';
?>
<?php if ($editing !== null): ?>
<section class="panel">
    <div class="panel-head"><h2><?= $editing ? 'Edit brand: ' . e($editing['name']) : 'New brand' ?></h2><a class="link-arrow" href="<?= e(admin_url('brands.php')) ?>">Cancel →</a></div>
    <form method="post" class="admin-form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
        <div class="form-row cols-2">
            <div class="form-field"><label for="b-name">Name *</label><input type="text" id="b-name" name="name" class="input" required value="<?= fv('name', $editing['name'] ?? '') ?>"></div>
            <div class="form-field"><label for="b-slug">Slug</label><input type="text" id="b-slug" name="slug" class="input" value="<?= fv('slug', $editing['slug'] ?? '') ?>"></div>
            <div class="form-field"><label for="b-country">Country</label><input type="text" id="b-country" name="country" class="input" value="<?= fv('country', $editing['country'] ?? '') ?>"></div>
            <div class="form-field"><label for="b-founded">Founded year</label><input type="number" id="b-founded" name="founded_year" class="input" min="1800" max="2100" value="<?= fv('founded_year', $editing['founded_year'] ?? '') ?>"></div>
            <div class="form-field"><label for="b-sort">Sort order</label><input type="number" id="b-sort" name="sort_order" class="input" value="<?= fv('sort_order', $editing['sort_order'] ?? 0) ?>"></div>
            <div class="form-field"><label for="b-status">Status</label>
                <select id="b-status" name="status" class="select">
                    <option value="1">Visible</option>
                    <option value="0" <?= fv('status', (string)($editing['status'] ?? '1')) === '0' ? 'selected' : '' ?>>Hidden</option>
                </select>
            </div>
        </div>
        <label class="check"><input type="checkbox" name="featured" value="1" <?= ($_POST['featured'] ?? $editing['featured'] ?? 0) ? 'checked' : '' ?>> Featured brand</label>
        <div class="form-field">
            <label for="b-desc">Description (HTML allowed)</label>
            <textarea id="b-desc" name="description" class="textarea" rows="5"><?= e($_POST['description'] ?? $editing['description'] ?? '') ?></textarea>
        </div>
        <div class="image-picker" data-input="logo">
            <img class="picker-preview" src="<?= e(img_url($_POST['logo'] ?? $editing['logo'] ?? '', 'assets/images/brands/default.svg')) ?>" alt="">
            <input type="hidden" name="logo" value="<?= fv('logo', $editing['logo'] ?? '') ?>">
            <div class="picker-actions">
                <button type="button" class="btn btn-sm btn-outline picker-open">Choose logo</button>
                <button type="button" class="btn btn-sm btn-ghost picker-clear">Clear</button>
            </div>
        </div>
        <div class="form-row cols-2">
            <div class="form-field"><label for="b-seo">SEO title</label><input type="text" id="b-seo" name="seo_title" class="input" value="<?= fv('seo_title', $editing['seo_title'] ?? '') ?>"></div>
            <div class="form-field"><label for="b-meta">Meta description</label><input type="text" id="b-meta" name="meta_description" class="input" value="<?= fv('meta_description', $editing['meta_description'] ?? '') ?>"></div>
        </div>
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save brand' : 'Create brand' ?></button>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <table class="admin-table">
        <thead><tr><th>Brand</th><th>Slug</th><th>Cars</th><th>Status</th><th class="th-actions">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($brands as $b): ?>
            <tr>
                <td><span class="brand-cell"><img src="<?= e(img_url($b['logo'], 'assets/images/brands/default.svg')) ?>" alt=""><?= e($b['name']) ?></span></td>
                <td><small><?= e($b['slug']) ?></small></td>
                <td><?= (int)$b['cars'] ?></td>
                <td><span class="status status-<?= $b['status'] ? 'published' : 'draft' ?>"><?= $b['status'] ? 'visible' : 'hidden' ?></span></td>
                <td class="td-actions">
                    <form method="post" class="inline-form">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                        <a class="btn btn-sm btn-outline" href="<?= e(admin_url('brands.php?edit=' . (int)$b['id'])) ?>">Edit</a>
                        <a class="btn btn-sm btn-outline" href="<?= e(url('brands/' . $b['slug'])) ?>" target="_blank">View</a>
                        <button class="btn btn-sm btn-danger" name="action" value="delete" data-confirm="Delete this brand and ALL its cars, specs and features?">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
