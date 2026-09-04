<?php
/** AutoPulse admin — database backup/export (admin only). Generates a full SQL dump as a download. */
require __DIR__ . '/includes/bootstrap.php';
require_admin('backup');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cache'])) {
    cache_clear_all();
    flash_set('success', 'Cache cleared.');
    redirect('admin/backup.php');
}

if (isset($_GET['export'])) {
    $dump = "-- AutoPulse database backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\nSET NAMES utf8mb4;\nSET foreign_key_checks = 0;\n\n";
    $tables = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $create = db()->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        $dump .= "DROP TABLE IF EXISTS `$table`;\n" . $create[1] . ";\n\n";
        $st = db()->query("SELECT * FROM `$table`");
        $rows = [];
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $vals = array_map(fn($v) => $v === null ? 'NULL' : db()->quote((string)$v), array_values($row));
            $rows[] = '(' . implode(', ', $vals) . ')';
            if (count($rows) >= 200) {
                $dump .= "INSERT INTO `$table` VALUES\n" . implode(",\n", $rows) . ";\n";
                $rows = [];
            }
        }
        if ($rows) $dump .= "INSERT INTO `$table` VALUES\n" . implode(",\n", $rows) . ";\n";
        $dump .= "\n";
    }
    $dump .= "SET foreign_key_checks = 1;\n";
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="autopulse-backup-' . date('Ymd-His') . '.sql"');
    echo $dump;
    exit;
}

$ADMIN_ACTIVE = 'backup';
$ADMIN_TITLE = 'Backup';
include __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2>Database backup</h2></div>
    <p>Download a complete SQL dump of every table (structure + data). Restore by importing the file in phpMyAdmin.</p>
    <a class="btn btn-primary" href="<?= e(admin_url('backup.php?export=1')) ?>">⬇ Download .sql backup</a>
</section>
<section class="panel">
    <div class="panel-head"><h2>Cache</h2></div>
    <p>Settings, menus, sitemap and popular queries are file-cached for speed. Clear the cache after big changes if anything looks stale.</p>
    <form method="post">
        <?= csrf_field() ?>
        <button class="btn btn-outline" name="clear_cache" value="1">Clear cache</button>
    </form>
</section>
<?php include __DIR__ . '/includes/footer.php';
