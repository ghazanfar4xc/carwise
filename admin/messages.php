<?php
/** AutoPulse admin — contact inbox (Module 29 admin). */
require __DIR__ . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)post('id');
    if (post('action') === 'read') db()->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?")->execute([$id]);
    elseif (post('action') === 'unread') db()->prepare("UPDATE contact_messages SET status = 'new' WHERE id = ?")->execute([$id]);
    elseif (post('action') === 'delete') db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
    flash_set('success', 'Inbox updated.');
    redirect('admin/messages.php');
}

$messages = db()->query('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 100')->fetchAll();

$ADMIN_ACTIVE = 'messages';
$ADMIN_TITLE = 'Inbox';
include __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <?php if (!$messages): ?><p class="empty-cell">Inbox is empty.</p><?php endif; ?>
    <?php foreach ($messages as $m): ?>
        <details class="message-row <?= $m['status'] === 'new' ? 'unread' : '' ?>">
            <summary>
                <span class="msg-status" aria-hidden="true"></span>
                <strong><?= e($m['subject']) ?></strong>
                <span class="hint"><?= e($m['name']) ?> &lt;<?= e($m['email']) ?>&gt; · <?= e(time_ago($m['created_at'])) ?></span>
                <?= $m['status'] === 'new' ? '<span class="badge badge-hot">New</span>' : '' ?>
            </summary>
            <div class="message-body">
                <p><?= nl2br(e($m['message'])) ?></p>
                <div class="message-actions">
                    <a class="btn btn-sm btn-outline" href="mailto:<?= e($m['email']) ?>?subject=Re: <?= e($m['subject']) ?>">Reply by email</a>
                    <form method="post" class="inline-form">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                        <button class="btn btn-sm btn-outline" name="action" value="<?= $m['status'] === 'new' ? 'read' : 'unread' ?>"><?= $m['status'] === 'new' ? 'Mark read' : 'Mark unread' ?></button>
                        <button class="btn btn-sm btn-danger" name="action" value="delete" data-confirm="Delete this message?">Delete</button>
                    </form>
                </div>
            </div>
        </details>
    <?php endforeach; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
