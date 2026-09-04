<?php
/** AutoPulse admin — SEO: defaults, verification, redirects, sitemap (Module 20). */
require __DIR__ . '/includes/bootstrap.php';
require_admin('seo');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');
    if ($action === 'gsc') {
        // Accept a full meta tag OR just the content value
        $meta = post('google_verification');
        if (stripos($meta, 'google-site-verification') !== false && preg_match('/content=["\']([^"\']+)["\']/i', $meta, $m)) {
            $meta = $m[1];
        }
        $file = preg_replace('/[^A-Za-z0-9._\-]/', '', post('gsc_file_name')); // case matters to Google
        if ($file !== '' && !str_ends_with($file, '.html')) $file .= '.html';
        save_settings([
            'google_verification' => mb_substr(trim($meta), 0, 150),
            'gsc_file_name'       => $file,
        ]);
        cache_forget('sitemap');
        flash_set('success', 'Google Search Console settings saved.');
    } elseif ($action === 'defaults') {
        save_settings([
            'meta_description'    => mb_substr(post('meta_description'), 0, 300),
            'og_image'            => post('og_image'),
            'site_url'            => rtrim(trim(post('site_url')), '/'),
            'google_verification' => mb_substr(post('google_verification'), 0, 100),
            'bing_verification'   => mb_substr(post('bing_verification'), 0, 100),
            'google_analytics_id' => mb_substr(post('google_analytics_id'), 0, 30),
        ]);
        cache_forget('sitemap');
        flash_set('success', 'SEO defaults saved.');
    } elseif ($action === 'redirect_save') {
        // store paths WITHOUT a leading slash (router looks them up that way)
        $old = trim(trim(post('old_path')), '/');
        $new = trim(trim(post('new_path')), '/');
        if ($old !== '' && $new !== '') {
            db()->prepare('INSERT INTO redirects (old_path, new_path, status_code) VALUES (?, ?, ?)
                           ON DUPLICATE KEY UPDATE new_path = VALUES(new_path), status_code = VALUES(status_code)')
                ->execute([$old, ltrim($new, '/'), (int)post('status_code') === 302 ? 302 : 301]);
            cache_forget('sitemap');
            flash_set('success', 'Redirect saved.');
        } else {
            flash_set('error', 'Old path and new path are required.');
        }
    } elseif ($action === 'redirect_delete') {
        db()->prepare('DELETE FROM redirects WHERE id = ?')->execute([(int)post('id')]);
        flash_set('success', 'Redirect removed.');
    }
    redirect('admin/seo.php');
}

$redirects = db()->query('SELECT * FROM redirects ORDER BY created_at DESC')->fetchAll();

$ADMIN_ACTIVE = 'seo';
$ADMIN_TITLE = 'SEO';
include __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2>Connect to Google Search Console</h2></div>
    <ol class="gsc-steps">
        <li>Open <a href="https://search.google.com/search-console" target="_blank" rel="noopener">search.google.com/search-console</a> → <strong>Add property</strong> → “URL prefix” → enter <strong><?= e(abs_url('/')) ?></strong></li>
        <li>Verify ownership — either method works:
            <ul>
                <li><strong>HTML tag</strong> → paste the whole <code>&lt;meta …&gt;</code> tag (or just its content) in the field below and Save.</li>
                <li><strong>HTML file</strong> → enter the file name Google gives you (e.g. <code>google1a2b3c.html</code>) below and Save — the site will serve it automatically at your root.</li>
            </ul>
        </li>
        <li>Back in Search Console press <strong>Verify</strong>.</li>
        <li>Submit your sitemap: in the left menu open <strong>Sitemaps</strong> and enter <code><?= e(ltrim(parse_url(abs_url('sitemap.xml'), PHP_URL_PATH), '/')) ?></code></li>
    </ol>
    <form method="post" class="admin-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="gsc">
        <div class="form-row cols-2">
            <div class="form-field">
                <label for="g-meta">Verification meta tag or content value</label>
                <input type="text" id="g-meta" name="google_verification" class="input" value="<?= e(setting('google_verification')) ?>" placeholder='<meta name="google-site-verification" content="…"> or the content string'>
                <?php if (setting('google_verification')): ?>
                    <p class="hint">Live tag: <code>&lt;meta name="google-site-verification" content="<?= e(setting('google_verification')) ?>"&gt;</code></p>
                <?php endif; ?>
            </div>
            <div class="form-field">
                <label for="g-file">Verification file name (HTML file method)</label>
                <input type="text" id="g-file" name="gsc_file_name" class="input" value="<?= e(setting('gsc_file_name')) ?>" placeholder="google1a2b3c.html">
                <?php if (setting('gsc_file_name')): ?>
                    <p class="hint">Served at: <a href="<?= e(abs_url(setting('gsc_file_name'))) ?>" target="_blank"><?= e(abs_url(setting('gsc_file_name'))) ?></a> ✓</p>
                <?php endif; ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save Search Console settings</button>
    </form>
</section>

<section class="panel">
    <div class="panel-head"><h2>Default SEO &amp; verification</h2></div>
    <form method="post" class="admin-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="defaults">
        <div class="form-row cols-2">
            <div class="form-field">
                <label for="s-url">Canonical site URL</label>
                <input type="url" id="s-url" name="site_url" class="input" value="<?= e(setting('site_url')) ?>" placeholder="https://yoursite.com">
                <p class="hint">Used for canonical tags, sitemap and Open Graph. Leave empty to auto-detect.</p>
            </div>
            <div class="form-field">
                <label for="s-og">Default Open Graph image</label>
                <div class="image-picker" data-input="og_image">
                    <img class="picker-preview" src="<?= e(img_url(setting('og_image'))) ?>" alt="">
                    <input type="hidden" name="og_image" value="<?= e(setting('og_image')) ?>">
                    <div class="picker-actions">
                        <button type="button" class="btn btn-sm btn-outline picker-open">Choose</button>
                        <button type="button" class="btn btn-sm btn-ghost picker-clear">Clear</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-field">
            <label for="s-meta">Default meta description</label>
            <textarea id="s-meta" name="meta_description" class="textarea" rows="2" maxlength="300"><?= e(setting('meta_description')) ?></textarea>
        </div>
        <div class="form-row cols-3">
            <div class="form-field"><label for="s-ga">Google Analytics ID</label><input type="text" id="s-ga" name="google_analytics_id" class="input" value="<?= e(setting('google_analytics_id')) ?>" placeholder="G-XXXXXXX"></div>
            <div class="form-field"><label for="s-bv">Bing verification</label><input type="text" id="s-bv" name="bing_verification" class="input" value="<?= e(setting('bing_verification')) ?>"></div>
        </div>
        <button type="submit" class="btn btn-primary">Save defaults</button>
    </form>
</section>

<section class="panel">
    <div class="panel-head"><h2>Sitemap &amp; robots</h2></div>
    <p class="result-count">
        Your sitemap and robots.txt are generated dynamically from the database:
        <a href="<?= e(abs_url('sitemap.xml')) ?>" target="_blank"><?= e(abs_url('sitemap.xml')) ?></a> ·
        <a href="<?= e(abs_url('robots.txt')) ?>" target="_blank"><?= e(abs_url('robots.txt')) ?></a>
    </p>
</section>

<section class="panel">
    <div class="panel-head"><h2>Add redirect</h2></div>
    <form method="post" class="admin-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="redirect_save">
        <div class="form-row cols-3">
            <div class="form-field"><label for="r-old">Old path</label><input type="text" id="r-old" name="old_path" class="input" placeholder="/blog" required></div>
            <div class="form-field"><label for="r-new">New path</label><input type="text" id="r-new" name="new_path" class="input" placeholder="articles" required></div>
            <div class="form-field"><label for="r-code">Type</label>
                <select id="r-code" name="status_code" class="select">
                    <option value="301">301 — permanent</option>
                    <option value="302">302 — temporary</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save redirect</button>
    </form>
</section>

<section class="panel">
    <table class="admin-table">
        <thead><tr><th>Old path</th><th>→ New path</th><th>Type</th><th>Added</th><th class="th-actions">Actions</th></tr></thead>
        <tbody>
        <?php if (!$redirects): ?><tr><td colspan="5" class="empty-cell">No redirects.</td></tr><?php endif; ?>
        <?php foreach ($redirects as $r): ?>
            <tr>
                <td><small>/<?= e($r['old_path']) ?></small></td>
                <td><small>→ <?= e(url($r['new_path'])) ?></small></td>
                <td><?= (int)$r['status_code'] ?></td>
                <td><small><?= e(format_date($r['created_at'])) ?></small></td>
                <td class="td-actions">
                    <form method="post" class="inline-form">
                        <?= csrf_field() ?><input type="hidden" name="action" value="redirect_delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn btn-sm btn-danger" data-confirm="Remove this redirect?">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
