<?php
/** AutoPulse admin — car list (Module 17). */
require __DIR__ . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)post('id');
    if (post('action') === 'delete' && $id) {
        db()->prepare('DELETE FROM car_models WHERE id = ?')->execute([$id]);
        flash_set('success', 'Car deleted.');
    }
    redirect('admin/cars.php');
}

$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, get_int('page', 1));
$where = $q !== '' ? 'WHERE c.name LIKE ? OR b.name LIKE ?' : '';
$params = $q !== '' ? ["%$q%", "%$q%"] : [];

$count = db()->prepare("SELECT COUNT(*) FROM car_models c JOIN brands b ON b.id = c.brand_id $where");
$count->execute($params);
$total = (int)$count->fetchColumn();
$pages = max(1, (int)ceil($total / 15));
$offset = min($page, $pages) * 15 - 15;

$st = db()->prepare("SELECT c.id, c.name, c.slug, c.body_type, c.price, c.status, c.is_popular, c.views, c.updated_at, b.name AS brand, b.slug AS brand_slug
                     FROM car_models c JOIN brands b ON b.id = c.brand_id $where
                     ORDER BY c.updated_at DESC LIMIT 15 OFFSET $offset");
$st->execute($params);
$cars = $st->fetchAll();

$ADMIN_ACTIVE = 'cars';
$ADMIN_TITLE = 'Cars';
include __DIR__ . '/includes/header.php';
?>
<div class="toolbar">
    <form method="get" class="toolbar-search" action="<?= e(admin_url('cars.php')) ?>">
        <input type="search" name="q" class="input" placeholder="Search cars…" value="<?= e($q) ?>">
        <button class="btn btn-outline" type="submit">Search</button>
    </form>
    <span class="result-count"><?= $total ?> cars</span>
</div>

<section class="panel">
    <table class="admin-table">
        <thead><tr><th>Car</th><th>Body</th><th>Price</th><th>Status</th><th>Views</th><th>Updated</th><th class="th-actions">Actions</th></tr></thead>
        <tbody>
        <?php if (!$cars): ?><tr><td colspan="7" class="empty-cell">No cars found.</td></tr><?php endif; ?>
        <?php foreach ($cars as $c): ?>
            <tr>
                <td>
                    <a class="row-title" href="<?= e(admin_url('car-edit.php?id=' . (int)$c['id'])) ?>"><?= e($c['brand'] . ' ' . $c['name']) ?></a>
                    <?php if ($c['is_popular']): ?><span class="badge badge-hot">Popular</span><?php endif; ?>
                    <small>/cars/<?= e($c['brand_slug'] . '/' . $c['slug']) ?></small>
                </td>
                <td><?= e($c['body_type']) ?></td>
                <td><?= format_price($c['price']) ?></td>
                <td><span class="status status-<?= e($c['status']) ?>"><?= e($c['status']) ?></span></td>
                <td><?= (int)$c['views'] ?></td>
                <td><small><?= e(time_ago($c['updated_at'])) ?></small></td>
                <td class="td-actions">
                    <form method="post" class="inline-form">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <a class="btn btn-sm btn-outline" href="<?= e(admin_url('car-edit.php?id=' . (int)$c['id'])) ?>">Edit</a>
                        <a class="btn btn-sm btn-outline" href="<?= e(url('cars/' . $c['brand_slug'] . '/' . $c['slug'])) ?>" target="_blank">View</a>
                        <button class="btn btn-sm btn-danger" name="action" value="delete" data-confirm="Delete this car and all its specs, features and images?">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php
if ($pages > 1) {
    echo '<nav class="pagination">';
    for ($i = 1; $i <= $pages; $i++)
        echo '<a class="' . ($i === $page ? 'active' : '') . '" href="' . e(admin_url('cars.php?page=' . $i . ($q !== '' ? '&q=' . rawurlencode($q) : ''))) . '">' . $i . '</a> ';
    echo '</nav>';
}
include __DIR__ . '/includes/footer.php';
