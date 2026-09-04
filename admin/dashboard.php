<?php
/** AutoPulse admin — dashboard (Module 15). */
require __DIR__ . '/includes/bootstrap.php';
require_admin();

$stats = ['articles' => 0, 'published' => 0, 'cars' => 0, 'brands' => 0, 'categories' => 0, 'comments' => 0, 'pendingComments' => 0, 'messages' => 0, 'newMessages' => 0, 'users' => 0, 'subscribers' => 0, 'views' => 0];
try {
    $stats['articles']       = (int)db()->query('SELECT COUNT(*) FROM articles')->fetchColumn();
    $stats['published']      = (int)db()->query('SELECT COUNT(*) FROM articles WHERE status = "published"')->fetchColumn();
    $stats['cars']           = (int)db()->query('SELECT COUNT(*) FROM car_models')->fetchColumn();
    $stats['brands']         = (int)db()->query('SELECT COUNT(*) FROM brands')->fetchColumn();
    $stats['categories']     = (int)db()->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    $stats['comments']       = (int)db()->query('SELECT COUNT(*) FROM comments')->fetchColumn();
    $stats['pendingComments']= (int)db()->query('SELECT COUNT(*) FROM comments WHERE status = "pending"')->fetchColumn();
    $stats['messages']       = (int)db()->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn();
    $stats['newMessages']    = (int)db()->query('SELECT COUNT(*) FROM contact_messages WHERE status = "new"')->fetchColumn();
    $stats['users']          = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $stats['subscribers']    = (int)db()->query('SELECT COUNT(*) FROM newsletter_subscribers')->fetchColumn();
    $stats['views']          = (int)db()->query('SELECT COALESCE(SUM(views),0) FROM articles')->fetchColumn()
                             + (int)db()->query('SELECT COALESCE(SUM(views),0) FROM car_models')->fetchColumn();
    $recentArticles = db()->query('SELECT a.id, a.title, a.status, a.published_at, a.views, u.username AS author FROM articles a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.updated_at DESC LIMIT 6')->fetchAll();
    $recentCars     = db()->query('SELECT c.id, c.name, c.views, c.updated_at, b.name AS brand FROM car_models c JOIN brands b ON b.id = c.brand_id ORDER BY c.updated_at DESC LIMIT 5')->fetchAll();
    $recentComments = db()->query('SELECT c.id, c.author_name, c.body, c.status, c.created_at, a.title FROM comments c LEFT JOIN articles a ON a.id = c.article_id ORDER BY c.created_at DESC LIMIT 5')->fetchAll();
} catch (Throwable $e) {
    flash_set('error', 'Database error: ' . $e->getMessage());
    $recentArticles = $recentCars = $recentComments = [];
}

$ADMIN_ACTIVE = 'dashboard';
$ADMIN_TITLE = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>
<div class="stat-grid">
    <div class="stat-card"><span>Published articles</span><strong><?= $stats['published'] ?></strong><small><?= $stats['articles'] ?> total</small></div>
    <div class="stat-card"><span>Cars</span><strong><?= $stats['cars'] ?></strong><small><?= $stats['brands'] ?> brands</small></div>
    <div class="stat-card"><span>Content views</span><strong><?= number_format($stats['views']) ?></strong><small>articles + cars</small></div>
    <div class="stat-card"><span>Pending comments</span><strong><?= $stats['pendingComments'] ?></strong><small><?= $stats['comments'] ?> total</small></div>
    <div class="stat-card"><span>New messages</span><strong><?= $stats['newMessages'] ?></strong><small><?= $stats['messages'] ?> in inbox</small></div>
    <div class="stat-card"><span>Newsletter</span><strong><?= $stats['subscribers'] ?></strong><small>subscribers</small></div>
</div>

<div class="panel-grid">
    <section class="panel">
        <div class="panel-head"><h2>Recent articles</h2><a class="link-arrow" href="<?= e(admin_url('articles.php')) ?>">Manage →</a></div>
        <table class="admin-table">
            <thead><tr><th>Title</th><th>Status</th><th>Views</th></tr></thead>
            <tbody>
            <?php foreach ($recentArticles as $a): ?>
                <tr>
                    <td><a href="<?= e(admin_url('article-edit.php?id=' . (int)$a['id'])) ?>"><?= e($a['title']) ?></a><small><?= e($a['author']) ?></small></td>
                    <td><span class="status status-<?= e($a['status']) ?>"><?= e($a['status']) ?></span></td>
                    <td><?= (int)$a['views'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="panel">
        <div class="panel-head"><h2>Recently updated cars</h2><a class="link-arrow" href="<?= e(admin_url('cars.php')) ?>">Manage →</a></div>
        <table class="admin-table">
            <thead><tr><th>Car</th><th>Views</th><th>Updated</th></tr></thead>
            <tbody>
            <?php foreach ($recentCars as $c): ?>
                <tr>
                    <td><a href="<?= e(admin_url('car-edit.php?id=' . (int)$c['id'])) ?>"><?= e($c['brand'] . ' ' . $c['name']) ?></a></td>
                    <td><?= (int)$c['views'] ?></td>
                    <td><?= e(time_ago($c['updated_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="panel">
        <div class="panel-head"><h2>Latest comments</h2><a class="link-arrow" href="<?= e(admin_url('comments.php')) ?>">Moderate →</a></div>
        <table class="admin-table">
            <thead><tr><th>Author</th><th>On</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recentComments as $c): ?>
                <tr>
                    <td><strong><?= e($c['author_name']) ?></strong><small><?= e(excerpt_text((string)$c['body'], 60)) ?></small></td>
                    <td><?= e(excerpt_text((string)$c['title'], 40)) ?></td>
                    <td><span class="status status-<?= e($c['status']) ?>"><?= e($c['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="panel">
        <div class="panel-head"><h2>Quick actions</h2></div>
        <div class="quick-actions">
            <a class="btn btn-outline" href="<?= e(admin_url('article-edit.php')) ?>">✍ New article</a>
            <a class="btn btn-outline" href="<?= e(admin_url('car-edit.php')) ?>">🚗 New car</a>
            <a class="btn btn-outline" href="<?= e(admin_url('brands.php')) ?>?edit=new">🏭 New brand</a>
            <a class="btn btn-outline" href="<?= e(admin_url('media.php')) ?>">🖼 Upload media</a>
            <a class="btn btn-outline" href="<?= e(admin_url('seo.php')) ?>">🔍 SEO settings</a>
            <a class="btn btn-outline" href="<?= e(admin_url('backup.php')) ?>">💾 Backup DB</a>
        </div>
        <div class="panel-head" style="margin-top:1.6rem"><h2>System</h2></div>
        <table class="admin-table">
            <tbody>
                <tr><th>PHP version</th><td><?= e(PHP_VERSION) ?></td></tr>
                <tr><th>Database</th><td><?= e(db()->getAttribute(PDO::ATTR_SERVER_VERSION) ?: 'MySQL') ?></td></tr>
                <tr><th>GD image support</th><td><?= function_exists('imagecreatetruecolor') ? 'Yes (auto-resize active)' : 'No' ?></td></tr>
                <tr><th>Environment</th><td><?= e(APP_ENV) ?></td></tr>
            </tbody>
        </table>
    </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
