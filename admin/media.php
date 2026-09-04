<?php
/** AutoPulse admin — media manager (Module 27). Also runs in picker mode (?picker=1) inside an iframe modal. */
require __DIR__ . '/includes/bootstrap.php';
require_admin();
$picker = isset($_GET['picker']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if (post('action') === 'delete' && post('id')) {
        $st = db()->prepare('SELECT * FROM media WHERE id = ?');
        $st->execute([(int)post('id')]);
        if ($m = $st->fetch()) {
            @unlink(dirname(__DIR__) . '/' . ltrim($m['path'], '/'));
            db()->prepare('DELETE FROM media WHERE id = ?')->execute([(int)post('id')]);
            json_out(['success' => true, 'message' => 'Image deleted from library.']);
        }
        json_out(['success' => false, 'message' => 'Image not found.'], 404);
    }
    if (isset($_FILES['files'])) {
        $uploaded = [];
        $errors = [];
        foreach ((array)$_FILES['files']['name'] as $i => $name) {
            $file = [
                'name'     => $name,
                'type'     => $_FILES['files']['type'][$i],
                'tmp_name' => $_FILES['files']['tmp_name'][$i],
                'error'    => $_FILES['files']['error'][$i],
                'size'     => $_FILES['files']['size'][$i],
            ];
            if ((int)$file['error'] === UPLOAD_ERR_NO_FILE) continue;
            $res = upload_image($file, 'media');
            if ($res['ok']) {
                $size = (int)$file['size'];
                $dims = @getimagesize(dirname(__DIR__) . '/' . $res['file']);
                db()->prepare('INSERT INTO media (filename, path, size_bytes, uploaded_by) VALUES (?, ?, ?, ?)')
                    ->execute([$name, $res['file'], $size, current_user()['id']]);
                $uploaded[] = ['path' => $res['file'], 'url' => url($res['file']), 'name' => $name,
                               'size' => human_size($size), 'w' => $dims[0] ?? 0, 'h' => $dims[1] ?? 0];
            } else {
                $errors[] = $name . ': ' . $res['error'];
            }
        }
        json_out(['success' => count($uploaded) > 0 || !$errors, 'message' => $errors ? implode(' · ', $errors) : '', 'data' => $uploaded]);
    }
    json_out(['success' => false, 'message' => 'Nothing to do.']);
}

$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, get_int('page', 1));
$where = $q !== '' ? 'WHERE filename LIKE ? OR alt LIKE ?' : '';
$params = $q !== '' ? ["%$q%", "%$q%"] : [];
$count = db()->prepare("SELECT COUNT(*) FROM media $where");
$count->execute($params);
$total = (int)$count->fetchColumn();
$pages = max(1, (int)ceil($total / 40));
$st = db()->prepare("SELECT * FROM media $where ORDER BY created_at DESC LIMIT 40 OFFSET " . (min($page, $pages) * 40 - 40));
$st->execute($params);
$images = $st->fetchAll();

if ($picker): // minimal page inside the picker modal iframe ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>"></head>
<body class="picker-body">
<form id="picker-upload" class="picker-upload">
    <?= csrf_field() ?>
    <label class="btn btn-outline btn-sm" for="picker-files">⬆ Upload images</label>
    <input type="file" id="picker-files" name="files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden>
    <span id="picker-status" class="hint"></span>
</form>
<div class="media-grid">
    <?php foreach ($images as $m): ?>
        <button type="button" class="media-item" data-path="<?= e($m['path']) ?>" data-url="<?= e(url($m['path'])) ?>">
            <img src="<?= e(img_url($m['path'])) ?>" alt="<?= e($m['filename']) ?>" loading="lazy">
            <span class="media-name"><?= e($m['filename']) ?></span>
        </button>
    <?php endforeach; ?>
</div>
<script>
document.getElementById('picker-files')?.addEventListener('change', async (e) => {
    const status = document.getElementById('picker-status');
    if (!e.target.files.length) return;
    status.textContent = 'Uploading…';
    const fd = new FormData(document.getElementById('picker-upload'));
    try {
        const res = await fetch(location.pathname, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const json = await res.json();
        if (json.success && json.data?.length) { status.textContent = 'Uploaded ✓ — click to insert'; setTimeout(() => location.reload(), 600); }
        else status.textContent = json.message || 'Upload failed.';
    } catch { status.textContent = 'Upload failed.'; }
});
document.querySelectorAll('.media-item').forEach(b => b.addEventListener('click', () => {
    parent.postMessage({ type: 'media-pick', path: b.dataset.path, url: b.dataset.url }, '*');
}));
</script>
</body></html>
<?php
exit;
endif;

$ADMIN_ACTIVE = 'media';
$ADMIN_TITLE = 'Media';
include __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <h2>Media library (<?= $total ?>)</h2>
        <form class="toolbar-search">
            <input type="search" name="q" class="input" placeholder="Search filenames…" value="<?= e($q) ?>">
            <button class="btn btn-outline" type="submit">Search</button>
        </form>
    </div>
    <form id="media-upload" class="media-upload">
        <?= csrf_field() ?>
        <label class="btn btn-outline" for="media-files">⬆ Upload images</label>
        <input type="file" id="media-files" name="files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden>
        <span id="upload-status" class="hint"></span>
    </form>
    <div class="media-grid" id="media-grid">
        <?php if (!$images): ?><p class="result-count">No images yet — upload some above.</p><?php endif; ?>
        <?php foreach ($images as $m): ?>
            <div class="media-item" data-id="<?= (int)$m['id'] ?>" data-path="<?= e($m['path']) ?>">
                <img src="<?= e(img_url($m['path'])) ?>" alt="<?= e($m['filename']) ?>" loading="lazy">
                <span class="media-name" title="<?= e($m['filename']) ?>"><?= e($m['filename']) ?></span>
                <span class="media-meta"><?= e(human_size((int)$m['size_bytes'])) ?> · <?= e(format_date($m['created_at'])) ?></span>
                <div class="media-actions">
                    <button type="button" class="btn btn-sm btn-outline copy-path" data-path="<?= e(url($m['path'])) ?>">Copy URL</button>
                    <button type="button" class="btn btn-sm btn-ghost view-img" data-src="<?= e(img_url($m['path'])) ?>">View</button>
                    <button type="button" class="btn btn-sm btn-danger media-delete" data-id="<?= (int)$m['id'] ?>">Delete</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($pages > 1): ?>
        <nav class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a class="<?= $i === $page ? 'active' : '' ?>" href="<?= e(admin_url('media.php?page=' . $i . ($q !== '' ? '&q=' . rawurlencode($q) : ''))) ?>"><?= $i ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
