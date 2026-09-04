<?php
/** AutoPulse admin — advertisement slots (Module 28). Ad code lives only here. */
require __DIR__ . '/includes/bootstrap.php';
require_admin('ads');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ((array)($_POST['slots'] ?? []) as $position => $data) {
        $code = (string)($data['code'] ?? '');
        $enabled = !empty($data['enabled']) ? 1 : 0;
        db()->prepare('UPDATE ad_slots SET code = ?, enabled = ? WHERE position = ?')->execute([$code, $enabled, $position]);
    }
    cache_forget('ad_slots');
    flash_set('success', 'Ad slots updated.');
    redirect('admin/ads.php');
}

$slots = db()->query('SELECT * FROM ad_slots ORDER BY id')->fetchAll();

$ADMIN_ACTIVE = 'ads';
$ADMIN_TITLE = 'Advertisements';
include __DIR__ . '/includes/header.php';
?>
<form method="post" class="admin-form">
    <?= csrf_field() ?>
    <p class="result-count">Paste your AdSense (or any) code into the slots you use — it renders only where the slot appears in the layout. Keep unused slots disabled for a fast, clean site.</p>
    <div class="ads-grid">
        <?php foreach ($slots as $s): ?>
            <section class="panel">
                <div class="panel-head">
                    <h2><?= e($s['name']) ?></h2>
                    <label class="switch"><input type="checkbox" name="slots[<?= e($s['position']) ?>][enabled]" value="1" <?= $s['enabled'] ? 'checked' : '' ?>><span>Enabled</span></label>
                </div>
                <div class="form-field">
                    <label for="ad-<?= e($s['position']) ?>">Ad code (HTML / script)</label>
                    <textarea id="ad-<?= e($s['position']) ?>" name="slots[<?= e($s['position']) ?>][code]" class="textarea" rows="4" style="font-family:ui-monospace,monospace;font-size:.85rem" placeholder="<!-- paste ad code here -->"><?= e($s['code']) ?></textarea>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
    <button type="submit" class="btn btn-primary">Save ad slots</button>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>
