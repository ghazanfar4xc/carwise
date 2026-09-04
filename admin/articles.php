<?php
/** AutoPulse admin — articles list: status filter, search, quick actions (Module 16). */
require __DIR__ . '/includes/bootstrap.php';
require_admin();

// Bulk actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)post('id');
    $action = post('action');
    if ($id && ctype_digit((string)$id)) {
        if ($action === 'delete') {
            db()->prepare('DELETE FROM articles WHERE id = ?')->execute([$id]);
            flash_set('success', 'Article deleted.');
        } elseif ($action === 'duplicate') {
            $st = db()->prepare('SELECT * FROM articles WHERE id = ?');
            $st->execute([$id]);
            if ($src = $st->fetch()) {
                unset($src['id'], $src['created_at'], $src['updated_at']);
                $src['title'] .= ' (copy)';
                $src['slug'] = unique_slug(db(), 'articles', slugify($src['slug'] . '-copy'));
                $src['status'] = 'draft';
                $cols = implode(', ', array_keys($src));
                $marks = implode(', ', array_fill(0, count($src), '?'));
                db()->prepare("INSERT INTO articles ($cols) VALUES ($marks)")->execute(array_values($src));
                flash_set('success', 'Article duplicated as a draft.');
            }
        } else {
            $map = ['publish' => ['published', 'published'], 'unpublish' => ['draft', 'draft'], 'feature' => ['featured', 1], 'unfeature' => ['featured', 0]];
            if (isset($map[$action])) {
                [$col, $val] = $map[$action];
                $sql = $col === 'published'
                    ? 'UPDATE articles SET status = ?, published_at = COALESCE(published_at, NOW()) WHERE id = ?'
                    : "UPDATE articles SET is_featured = ? WHERE id = ?";
                db()->prepare($sql)->execute([$val, $id]);
                flash_set('success', 'Article updated.');
            }
        }
    }
    redirect('admin/articles.php' . (post('back') ? '?status=' . rawurlencode(post('back')) : ''));
}

$status = in_array($_GET['status'] ?? '', ['published', 'draft', 'scheduled'], true) ? $_GET['status'] : '';
$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, get_int('page', 1));

$where = ['1=1'];
$params = [];
if ($status) { $where[] = 'a.status = ?'; $params[] = $status; }
if ($q !== '') { $where[] = 'a.title LIKE ?'; $params[] = "%$q%"; }
$whereSql = implode(' AND ', $where);

$count = db()->prepare("SELECT COUNT(*) FROM articles a WHERE $whereSql");
$count->execute($params);
$total = (int)$count->fetchColumn();
$pages = (int)ceil($total / 15);
$offset = ($page - 1) * 15;

$st = db()->prepare("SELECT a.id, a.title, a.slug, a.status, a.is_featured, a.views, a.published_at, a.updated_at,
                            u.username AS author, c.name AS category
                     FROM articles a LEFT JOIN users u ON u.id = a.user_id LEFT JOIN categories c ON c.id = a.category_id
                     WHERE $whereSql ORDER BY a.updated_at DESC LIMIT 15 OFFSET $offset");
$st->execute($params);
$articles = $st->fetchAll();

$ADMIN_ACTIVE = 'articles';
$ADMIN_TITLE = 'Articles';
include __DIR__ . '/includes/header.php';
?>
<div class="toolbar">
    <form method="get" class="toolbar-search" action="<?= e(admin_url('articles.php')) ?>">
        <input type="search" name="q" class="input" placeholder="Search articles…" value="<?= e($q) ?>">
        <input type="hidden" name="status" value="<?= e($status) ?>">
        <button class="btn btn-outline" type="submit">Search</button>
    </form>
    <div class="tabs" role="tablist">
        <?php foreach (['' => 'All', 'published' => 'Published', 'draft' => 'Drafts', 'scheduled' => 'Scheduled'] as $s => $label): ?>
            <a href="<?= e(admin_url('articles.php' . ($s !== '' ? '?status=' . $s : ''))) ?>" class="tab <?= $status === $s ? 'active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>
</div>

<section class="panel">
    <table class="admin-table">
        <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Views</th><th>Date</th><th class="th-actions">Actions</th></tr></thead>
        <tbody>
        <?php if (!$articles): ?>
            <tr><td colspan="6" class="empty-cell">No articles found.</td></tr>
        <?php endif; ?>
        <?php foreach ($articles as $a): ?>
            <tr>
                <td>
                    <a class="row-title" href="<?= e(admin_url('article-edit.php?id=' . (int)$a['id'])) ?>"><?= e($a['title']) ?></a>
                    <?php if ($a['is_featured']): ?><span class="badge badge-hot">Featured</span><?php endif; ?>
                    <small>/articles/<?= e($a['slug']) ?> · by <?= e($a['author']) ?></small>
                </td>
                <td><?= e($a['category'] ?: '—') ?></td>
                <td><span class="status status-<?= e($a['status']) ?>"><?= e($a['status']) ?></span></td>
                <td><?= (int)$a['views'] ?></td>
                <td><small><?= e(format_date($a['published_at'] ?: $a['updated_at'])) ?></small></td>
                <td class="td-actions">
                    <form method="post" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                        <input type="hidden" name="back" value="<?= e($status) ?>">
                        <div class="action-menu">
                            <button class="btn btn-sm btn-outline" name="action" value="<?= $a['status'] === 'published' ? 'unpublish' : 'publish' ?>" formnovalidate><?= $a['status'] === 'published' ? 'Unpublish' : 'Publish' ?></button>
                            <button class="btn btn-sm btn-outline" name="action" value="<?= $a['is_featured'] ? 'unfeature' : 'feature' ?>" formnovalidate><?= $a['is_featured'] ? 'Unfeature' : 'Feature' ?></button>
                            <button class="btn btn-sm btn-outline" name="action" value="duplicate" formnovalidate>Duplicate</button>
                            <a class="btn btn-sm btn-outline" href="<?= e(url('articles/' . $a['slug'])) ?>" target="_blank">View</a>
                            <button class="btn btn-sm btn-danger" name="action" value="delete" formnovalidate data-confirm="Delete this article permanently?">Delete</button>
                        </div>
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
    for ($i = 1; $i <= $pages; $i++) {
        echo '<a class="' . ($i === $page ? 'active' : '') . '" href="' . e(admin_url('articles.php?page=' . $i . ($status ? '&status=' . $status : '') . ($q !== '' ? '&q=' . rawurlencode($q) : ''))) . '">' . $i . '</a> ';
    }
    echo '</nav>';
}
include __DIR__ . '/includes/footer.php';
?>
