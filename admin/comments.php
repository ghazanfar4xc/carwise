<?php
/** AutoPulse admin — comment moderation (Module 29 admin). */
require __DIR__ . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)post('id');
    $st = db()->prepare('UPDATE comments SET status = ? WHERE id = ?');
    if (post('action') === 'approve') $st->execute(['approved', $id]);
    elseif (post('action') === 'spam') $st->execute(['spam', $id]);
    elseif (post('action') === 'pending') $st->execute(['pending', $id]);
    elseif (post('action') === 'delete') { db()->prepare('DELETE FROM comments WHERE id = ?')->execute([$id]); }
    flash_set('success', 'Comment updated.');
    redirect('admin/comments.php' . (post('back') ? '?status=' . rawurlencode(post('back')) : ''));
}

$status = in_array($_GET['status'] ?? '', ['pending', 'approved', 'spam'], true) ? $_GET['status'] : '';
$where = $status ? 'WHERE c.status = ?' : '';
$params = $status ? [$status] : [];
$comments = db()->prepare("SELECT c.*, a.title AS article, a.slug AS article_slug
                           FROM comments c LEFT JOIN articles a ON a.id = c.article_id
                           $where ORDER BY c.created_at DESC LIMIT 100");
$comments->execute($params);
$comments = $comments->fetchAll();
$counts = db()->query("SELECT status, COUNT(*) n FROM comments GROUP BY status")->fetchAll();
$countBy = ['pending' => 0, 'approved' => 0, 'spam' => 0];
foreach ($counts as $c) $countBy[$c['status']] = (int)$c['n'];

$ADMIN_ACTIVE = 'comments';
$ADMIN_TITLE = 'Comments';
include __DIR__ . '/includes/header.php';
?>
<div class="tabs" role="tablist">
    <a href="<?= e(admin_url('comments.php')) ?>" class="tab <?= $status === '' ? 'active' : '' ?>">All (<?= array_sum($countBy) ?>)</a>
    <a href="<?= e(admin_url('comments.php?status=pending')) ?>" class="tab <?= $status === 'pending' ? 'active' : '' ?>">Pending (<?= $countBy['pending'] ?>)</a>
    <a href="<?= e(admin_url('comments.php?status=approved')) ?>" class="tab <?= $status === 'approved' ? 'active' : '' ?>">Approved (<?= $countBy['approved'] ?>)</a>
    <a href="<?= e(admin_url('comments.php?status=spam')) ?>" class="tab <?= $status === 'spam' ? 'active' : '' ?>">Spam (<?= $countBy['spam'] ?>)</a>
</div>

<section class="panel">
    <?php if (!$comments): ?><p class="empty-cell">No comments here.</p><?php endif; ?>
    <?php foreach ($comments as $c): ?>
        <div class="comment-row">
            <div class="comment-row-head">
                <strong><?= e($c['author_name']) ?></strong>
                <span class="hint"><?= e($c['author_email']) ?> · <?= e(time_ago($c['created_at'])) ?> ·
                    on <a href="<?= e(url('articles/' . $c['article_slug'])) ?>#comments" target="_blank"><?= e(excerpt_text((string)$c['article'], 50)) ?></a>
                </span>
                <span class="status status-<?= e($c['status']) ?>"><?= e($c['status']) ?></span>
            </div>
            <p class="comment-row-body"><?= nl2br(e($c['body'])) ?></p>
            <form method="post" class="inline-form">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="back" value="<?= e($status) ?>">
                <?php if ($c['status'] !== 'approved'): ?><button class="btn btn-sm btn-outline" name="action" value="approve">Approve</button><?php endif; ?>
                <?php if ($c['status'] !== 'pending'): ?><button class="btn btn-sm btn-outline" name="action" value="pending">Pending</button><?php endif; ?>
                <?php if ($c['status'] !== 'spam'): ?><button class="btn btn-sm btn-outline" name="action" value="spam">Spam</button><?php endif; ?>
                <button class="btn btn-sm btn-danger" name="action" value="delete" data-confirm="Delete this comment?">Delete</button>
            </form>
        </div>
    <?php endforeach; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
