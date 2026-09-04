<?php
/** AutoPulse admin — menu manager (Module 19): main navigation items, 2 levels. */
require __DIR__ . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');
    if ($action === 'save') {
        $id = (int)post('id');
        $data = [
            'parent_id'  => (int)post('parent_id') ?: null,
            'label'      => mb_substr(post('label'), 0, 80),
            'url'        => mb_substr(post('url'), 0, 255),
            'sort_order' => (int)post('sort_order'),
            'status'     => post('status') === '0' ? 0 : 1,
        ];
        if ($data['label'] === '' || $data['url'] === '') {
            flash_set('error', 'Label and URL are required.');
        } elseif ($id) {
            if ((int)post('parent_id') === $id) $data['parent_id'] = null; // no self-parenting
            $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
            db()->prepare("UPDATE menu_items SET $set WHERE id = ?")->execute([...array_values($data), $id]);
            flash_set('success', 'Menu item updated.');
        } else {
            $menuId = (int)db()->query("SELECT id FROM menus WHERE slug = 'main'")->fetchColumn();
            db()->prepare('INSERT INTO menu_items (menu_id, parent_id, label, url, sort_order, status) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$menuId, $data['parent_id'], $data['label'], $data['url'], $data['sort_order'], $data['status']]);
            flash_set('success', 'Menu item added.');
        }
    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM menu_items WHERE id = ? OR parent_id = ?')->execute([(int)post('id'), (int)post('id')]);
        flash_set('success', 'Menu item removed.');
    }
    cache_forget('menu_main');
    redirect('admin/menus.php');
}

$editing = null;
if (get_int('edit', 0)) {
    $st = db()->prepare('SELECT * FROM menu_items WHERE id = ?');
    $st->execute([get_int('edit', 0)]);
    $editing = $st->fetch() ?: null;
}
$items = db()->query('SELECT mi.*, p.label AS parent_label FROM menu_items mi
                      LEFT JOIN menu_items p ON p.id = mi.parent_id
                      WHERE mi.menu_id = (SELECT id FROM menus WHERE slug = "main")
                      ORDER BY COALESCE(mi.parent_id, mi.id), mi.parent_id IS NOT NULL, mi.sort_order, mi.id')->fetchAll();

$ADMIN_ACTIVE = 'menus';
$ADMIN_TITLE = 'Menus';
include __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2><?= $editing ? 'Edit item: ' . e($editing['label']) : 'Add menu item' ?></h2>
        <?php if ($editing): ?><a class="link-arrow" href="<?= e(admin_url('menus.php')) ?>">+ New instead →</a><?php endif; ?>
    </div>
    <form method="post" class="admin-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
        <div class="form-row cols-2">
            <div class="form-field"><label for="m-label">Label *</label><input type="text" id="m-label" name="label" class="input" required value="<?= fv('label', $editing['label'] ?? '') ?>"></div>
            <div class="form-field"><label for="m-url">URL *</label><input type="text" id="m-url" name="url" class="input" required value="<?= fv('url', $editing['url'] ?? '') ?>" placeholder="cars  ·  category/suv  ·  https://…"></div>
            <div class="form-field"><label for="m-parent">Parent item (for dropdowns)</label>
                <select id="m-parent" name="parent_id" class="select">
                    <option value="">— top level —</option>
                    <?php $curParent = (string)($_POST['parent_id'] ?? $editing['parent_id'] ?? '');
                    foreach (db()->query('SELECT id, label FROM menu_items WHERE parent_id IS NULL AND menu_id = (SELECT id FROM menus WHERE slug = "main")')->fetchAll() as $parent): ?>
                        <option value="<?= (int)$parent['id'] ?>" <?= $curParent === (string)$parent['id'] ? 'selected' : '' ?>><?= e($parent['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field"><label for="m-sort">Sort order</label><input type="number" id="m-sort" name="sort_order" class="input" value="<?= fv('sort_order', $editing['sort_order'] ?? 0) ?>"></div>
        </div>
        <label class="check"><input type="checkbox" name="status" value="1" <?= ($_POST['status'] ?? $editing['status'] ?? 1) ? 'checked' : '' ?>> Visible</label>
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save item' : 'Add item' ?></button>
    </form>
</section>

<section class="panel">
    <table class="admin-table">
        <thead><tr><th>Label</th><th>URL</th><th>Level</th><th>Order</th><th>Status</th><th class="th-actions">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><strong><?= $it['parent_id'] ? '↳ ' : '' ?><?= e($it['label']) ?></strong></td>
                <td><small><?= e($it['url']) ?></small></td>
                <td><?= $it['parent_id'] ? 'Sub of ' . e($it['parent_label']) : 'Top level' ?></td>
                <td><?= (int)$it['sort_order'] ?></td>
                <td><span class="status status-<?= $it['status'] ? 'published' : 'draft' ?>"><?= $it['status'] ? 'visible' : 'hidden' ?></span></td>
                <td class="td-actions">
                    <form method="post" class="inline-form">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                        <a class="btn btn-sm btn-outline" href="<?= e(admin_url('menus.php?edit=' . (int)$it['id'])) ?>">Edit</a>
                        <button class="btn btn-sm btn-danger" data-confirm="Remove this menu item (and its sub-items)?">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
