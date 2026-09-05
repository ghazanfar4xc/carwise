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
    } elseif ($action === 'robots_save') {
        save_settings(['robots_custom' => (string)($_POST['robots_custom'] ?? '')]);
        flash_set('success', 'robots.txt override saved. Leave empty to use the recommended default.');
    } elseif ($action === 'robots_reset') {
        save_settings(['robots_custom' => '']);
        flash_set('success', 'robots.txt reset to recommended defaults.');
    } elseif ($action === 'org_save') {
        save_settings([
            'org_description'    => mb_substr(post('org_description'), 0, 400),
            'social_facebook'    => rtrim(trim(post('social_facebook')), '/'),
            'social_twitter'     => rtrim(trim(post('social_twitter')), '/'),
            'social_instagram'   => rtrim(trim(post('social_instagram')), '/'),
            'social_youtube'     => rtrim(trim(post('social_youtube')), '/'),
        ]);
        flash_set('success', 'Organization & social profiles saved (used in Organization schema).');
    }
    redirect('admin/seo.php' . (post('tab') !== '' ? '?tab=' . urlencode(post('tab')) : ''));
}

$redirects = db()->query('SELECT * FROM redirects ORDER BY created_at DESC')->fetchAll();

$tab = in_array($_GET['tab'] ?? '', ['overview', 'global', 'redirects', 'robots'], true) ? ($_GET['tab'] ?? 'overview') : 'overview';

/* SEO dashboard data */
$stat = fn(string $sql) => (int)db()->query($sql)->fetchColumn();
$seoStats = [
    'articles'        => $stat('SELECT COUNT(*) FROM articles a WHERE ' . ARTICLE_LIVE),
    'articles_no_meta' => $stat('SELECT COUNT(*) FROM articles a WHERE ' . ARTICLE_LIVE . " AND (meta_description IS NULL OR meta_description = '')"),
    'articles_no_qa'  => $stat('SELECT COUNT(*) FROM articles a WHERE ' . ARTICLE_LIVE . " AND (quick_answer IS NULL OR quick_answer = '')"),
    'cars'            => $stat("SELECT COUNT(*) FROM car_models WHERE status = 'published'"),
    'cars_no_meta'    => $stat("SELECT COUNT(*) FROM car_models WHERE status = 'published' AND (meta_description IS NULL OR meta_description = '')"),
    'cars_no_qa'      => $stat("SELECT COUNT(*) FROM car_models WHERE status = 'published' AND (quick_answer IS NULL OR quick_answer = '')"),
    'pages'           => $stat('SELECT COUNT(*) FROM pages WHERE status = "published"'),
    'media_no_alt'    => $stat("SELECT COUNT(*) FROM media WHERE alt IS NULL OR alt = ''"),
    'redirects'       => $stat('SELECT COUNT(*) FROM redirects'),
    'sources'         => $stat('SELECT COUNT(*) FROM content_sources'),
    'answer_blocks'   => $stat('SELECT COUNT(*) FROM answer_blocks'),
];
$dupMeta = db()->query("SELECT meta_description, COUNT(*) c FROM articles WHERE meta_description IS NOT NULL AND meta_description != '' GROUP BY meta_description HAVING c > 1")->fetchAll();
$dupTitles = db()->query("SELECT title, COUNT(*) c FROM articles GROUP BY title HAVING c > 1")->fetchAll();
$audit = [
    ['Canonical site URL set', setting('site_url') !== '', 'Set it in Global SEO so canonicals/sitemap use your HTTPS domain', 'global'],
    ['Google Search Console verification', setting('google_verification') !== '', 'Add verification to own your Search Console data', 'global'],
    ['Bing Webmaster verification', setting('bing_verification') !== '', 'Optional but recommended', 'global'],
    ['Default social image', setting('og_image') !== '', 'Used for shares without their own image', 'global'],
    ['Organization description', setting('org_description') !== '', 'Feeds Organization structured data', 'global'],
    ['robots.txt override', true, 'Managed in the Robots tab; default blocks only private areas', 'robots'],
    ['Redirects in place', true, $seoStats['redirects'] . ' redirects managed; slug changes create them automatically', 'redirects'],
];

$ADMIN_ACTIVE = 'seo';

$ADMIN_TITLE = 'SEO';
include __DIR__ . '/includes/header.php';
?>
<nav class="tab-nav" style="display:flex;gap:.4rem;margin-bottom:1.2rem;flex-wrap:wrap" aria-label="SEO sections">
    <?php foreach (['overview' => 'SEO Dashboard', 'global' => 'Global SEO & Organization', 'redirects' => 'Redirects', 'robots' => 'Robots.txt'] as $k => $lbl): ?>
        <a class="btn btn-sm <?= $tab === $k ? 'btn-primary' : 'btn-outline' ?>" href="<?= e(admin_url('seo.php?tab=' . $k)) ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
</nav>

<?php if ($tab === 'overview'): ?>
<section class="panel">
    <div class="panel-head"><h2>SEO Dashboard</h2></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.8rem">
        <div class="key-spec" style="text-align:left"><span>Published articles</span><b><?= $seoStats['articles'] ?></b></div>
        <div class="key-spec" style="text-align:left"><span>Published cars</span><b><?= $seoStats['cars'] ?></b></div>
        <div class="key-spec" style="text-align:left"><span>CMS pages</span><b><?= $seoStats['pages'] ?></b></div>
        <div class="key-spec" style="text-align:left"><span>Redirects</span><b><?= $seoStats['redirects'] ?></b></div>
        <div class="key-spec" style="text-align:left"><span>Sources (GEO)</span><b><?= $seoStats['sources'] ?></b></div>
        <div class="key-spec" style="text-align:left"><span>Answer blocks (AEO)</span><b><?= $seoStats['answer_blocks'] ?></b></div>
    </div>
    <h3 style="margin:1.4rem 0 .5rem">Content gaps</h3>
    <ul class="seo-checklist">
        <li class="<?= $seoStats['articles_no_meta'] ? 'todo' : 'ok' ?>"><?= $seoStats['articles_no_meta'] ? '○' : '✓' ?> Articles missing meta description: <b><?= $seoStats['articles_no_meta'] ?></b></li>
        <li class="<?= $seoStats['articles_no_qa'] ? 'todo' : 'ok' ?>"><?= $seoStats['articles_no_qa'] ? '○' : '✓' ?> Articles missing a Quick Answer (AEO): <b><?= $seoStats['articles_no_qa'] ?></b></li>
        <li class="<?= $seoStats['cars_no_meta'] ? 'todo' : 'ok' ?>"><?= $seoStats['cars_no_meta'] ? '○' : '✓' ?> Cars missing meta description: <b><?= $seoStats['cars_no_meta'] ?></b></li>
        <li class="<?= $seoStats['cars_no_qa'] ? 'todo' : 'ok' ?>"><?= $seoStats['cars_no_qa'] ? '○' : '✓' ?> Cars missing a Quick Answer (AEO): <b><?= $seoStats['cars_no_qa'] ?></b></li>
        <li class="<?= $seoStats['media_no_alt'] ? 'todo' : 'ok' ?>"><?= $seoStats['media_no_alt'] ? '○' : '✓' ?> Images missing alt text: <b><?= $seoStats['media_no_alt'] ?></b> (<a href="<?= e(admin_url('media.php')) ?>">Media</a>)</li>
    </ul>
    <?php if ($dupTitles || $dupMeta): ?>
    <h3 style="margin:1.4rem 0 .5rem">Potential duplicates</h3>
    <ul class="seo-checklist">
        <?php foreach ($dupTitles as $d): ?><li class="todo">○ Duplicate article title (<?= (int)$d['c'] ?>×): <?= e($d['title']) ?></li><?php endforeach; ?>
        <?php foreach ($dupMeta as $d): ?><li class="todo">○ Duplicate meta description (<?= (int)$d['c'] ?>×): <?= e(mb_substr($d['meta_description'], 0, 60)) ?>…</li><?php endforeach; ?>
    </ul>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel-head"><h2>Technical Audit</h2></div>
    <ul class="seo-checklist">
        <?php foreach ($audit as [$label, $ok, $hint, $goTab]): ?>
        <li class="<?= $ok ? 'ok' : 'todo' ?>"><?= $ok ? '✓' : '○' ?> <?= e($label) ?>
            — <small><?= e($hint) ?></small><?php if (!$ok): ?> <a class="btn btn-sm btn-outline" href="<?= e(admin_url('seo.php?tab=' . $goTab)) ?>">Fix</a><?php endif; ?></li>
        <?php endforeach; ?>
        <li class="ok">✓ Sitemap is dynamic — <a href="<?= e(abs_url('sitemap.xml')) ?>" target="_blank">view</a> (new content appears automatically)</li>
        <li class="ok">✓ robots.txt is dynamic — <a href="<?= e(abs_url('robots.txt')) ?>" target="_blank">view</a></li>
        <li class="ok">✓ Public HTML sitemap — <a href="<?= e(abs_url('sitemap')) ?>" target="_blank">view</a></li>
        <li class="ok">✓ Structured data: Organization, WebSite+SearchAction, Article, Car, BreadcrumbList, FAQPage, Person, ItemList (comparisons)</li>
    </ul>
</section>
<?php endif; ?>

<?php if ($tab === 'global'): ?>
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
    <div class="panel-head"><h2>Organization &amp; social profiles</h2></div>
    <form method="post" class="admin-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="org_save">
        <input type="hidden" name="tab" value="global">
        <div class="form-field">
            <label for="org-desc">Organization description <span class="hint" style="display:inline">(Organization structured data)</span></label>
            <textarea id="org-desc" name="org_description" class="textarea" rows="2" maxlength="400"><?= e(setting('org_description')) ?></textarea>
            <p class="hint">Name, logo and URL come from Settings → General and the logo setting.</p>
        </div>
        <div class="form-row cols-2">
            <div class="form-field"><label for="so-fb">Facebook URL</label><input type="url" id="so-fb" name="social_facebook" class="input" value="<?= e(setting('social_facebook')) ?>"></div>
            <div class="form-field"><label for="so-tw">X / Twitter URL</label><input type="url" id="so-tw" name="social_twitter" class="input" value="<?= e(setting('social_twitter')) ?>"></div>
        </div>
        <div class="form-row cols-2">
            <div class="form-field"><label for="so-ig">Instagram URL</label><input type="url" id="so-ig" name="social_instagram" class="input" value="<?= e(setting('social_instagram')) ?>"></div>
            <div class="form-field"><label for="so-yt">YouTube URL</label><input type="url" id="so-yt" name="social_youtube" class="input" value="<?= e(setting('social_youtube')) ?>"></div>
        </div>
        <button type="submit" class="btn btn-primary">Save organization</button>
    </form>
</section>
<?php endif; ?>

<?php if ($tab === 'robots'): ?>
<section class="panel">
    <div class="panel-head"><h2>Sitemap &amp; robots</h2></div>
    <p class="result-count">
        Your sitemap and robots.txt are generated dynamically from the database:
        <a href="<?= e(abs_url('sitemap.xml')) ?>" target="_blank"><?= e(abs_url('sitemap.xml')) ?></a> ·
        <a href="<?= e(abs_url('robots.txt')) ?>" target="_blank"><?= e(abs_url('robots.txt')) ?></a>
    </p>
    <form method="post" class="admin-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="robots_save">
        <input type="hidden" name="tab" value="robots">
        <div class="form-field">
            <label for="robots-custom">Custom robots.txt override <span class="hint" style="display:inline">(empty = recommended default: allows public content, blocks /admin, /api, /search; includes sitemap)</span></label>
            <textarea id="robots-custom" name="robots_custom" class="textarea" rows="8" style="font-family:monospace" placeholder="User-agent: *
Disallow: /admin/"><?= e(setting('robots_custom')) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Save robots.txt</button>
    </form>
    <form method="post" style="margin-top:.5rem">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="robots_reset">
        <input type="hidden" name="tab" value="robots">
        <button type="submit" class="btn btn-outline" data-confirm="Reset robots.txt to recommended defaults?">Reset to defaults</button>
    </form>
    <p class="hint" style="margin-top:.8rem">Live preview: <a href="<?= e(abs_url('robots.txt')) ?>" target="_blank"><?= e(abs_url('robots.txt')) ?></a> — the sitemap line is always appended automatically.</p>
</section>
<?php endif; ?>

<?php if ($tab === 'redirects'): ?>
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
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
<style>
.seo-checklist{list-style:none;padding:0;display:grid;gap:.35rem}
.seo-checklist li{padding:.4rem .65rem;border-radius:8px;background:#eef0f4;font-size:.88rem;display:flex;gap:.4rem;align-items:center;flex-wrap:wrap}
.seo-checklist li.ok{color:#1a7f4e}.seo-checklist li.todo{color:#b7791f}
</style>
