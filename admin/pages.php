<?php
/** AutoPulse admin — CMS pages list (Module 45). */
require __DIR__ . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'delete') {
    db()->prepare('DELETE FROM pages WHERE id = ?')->execute([(int)post('id')]);
    cache_forget('footer_pages');
    flash_set('success', 'Page deleted.');
    redirect('admin/pages.php');
}

$pages = db()->query('SELECT p.*, (SELECT COUNT(*) FROM pages) AS _ FROM pages p ORDER BY p.sort_order, p.title')->fetchAll();

$ADMIN_ACTIVE = 'pages';
$ADMIN_TITLE = 'Pages';
include __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <table class="admin-table">
        <thead><tr><th>Page</th><th>Slug</th><th>Footer</th><th>Status</th><th>Updated</th><th class="th-actions">Actions</th></tr></thead>
        <tbody>
        <?php if (!$pages): ?><tr><td colspan="6" class="empty-cell">No pages yet.</td></tr><?php endif; ?>
        <?php foreach ($pages as $p): ?>
            <tr>
                <td><a class="row-title" href="<?= e(admin_url('page-edit.php?id=' . (int)$p['id'])) ?>"><?= e($p['title']) ?></a></td>
                <td><small>/<?= e($p['slug']) ?></small></td>
                <td><?= $p['show_in_footer'] ? '✓' : '—' ?></td>
                <td><span class="status status-<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                <td><small><?= e(time_ago($p['updated_at'])) ?></small></td>
                <td class="td-actions">
                    <form method="post" class="inline-form">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <a class="btn btn-sm btn-outline" href="<?= e(admin_url('page-edit.php?id=' . (int)$p['id'])) ?>">Edit</a>
                        <a class="btn btn-sm btn-outline" href="<?= e(url($p['slug'])) ?>" target="_blank">View</a>
                        <button class="btn btn-sm btn-danger" name="action" value="delete" data-confirm="Delete this page?">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
