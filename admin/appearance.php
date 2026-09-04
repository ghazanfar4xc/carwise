<?php
/** AutoPulse admin — Appearance: theme colors/shape/mode, homepage sections, custom code. */
require __DIR__ . '/includes/bootstrap.php';
require_admin('settings');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');
    if ($action === 'theme') {
        if (post('reset') === '1') {
            save_settings(['theme_primary' => '', 'theme_secondary' => '', 'theme_radius' => '',
                           'theme_chamfer' => '1', 'theme_mode' => 'auto', 'theme_font_scale' => '']);
            flash_set('success', 'Theme reset to the stock AutoPulse design.');
        } else {
            save_settings([
                'theme_primary'     => normalize_hex(post('theme_primary')),
                'theme_secondary'   => normalize_hex(post('theme_secondary')),
                'theme_radius'      => in_array(post('theme_radius'), ['sharp', 'standard', 'rounded'], true) ? post('theme_radius') : 'standard',
                'theme_chamfer'     => post('theme_chamfer') === '0' ? '0' : '1',
                'theme_mode'        => in_array(post('theme_mode'), ['auto', 'light', 'dark'], true) ? post('theme_mode') : 'auto',
                'theme_font_scale'  => in_array(post('theme_font_scale'), ['compact', 'normal', 'large'], true) ? post('theme_font_scale') : 'normal',
            ]);
            flash_set('success', 'Theme saved — the whole site now uses your colors.');
        }
    } elseif ($action === 'homepage') {
        $sections = [];
        foreach ((array)($_POST['section_key'] ?? []) as $i => $key) {
            $sections[] = [
                'key'   => preg_replace('/[^a-z_]/', '', $key),
                'label' => mb_substr((string)($_POST['section_label'][$i] ?? ''), 0, 60),
                'enabled' => !empty($_POST['section_enabled'][$i]) ? 1 : 0,
                'head'  => mb_substr((string)($_POST['section_head'][$i] ?? ''), 0, 100),
            ];
        }
        save_settings([
            'homepage_sections' => json_encode(array_values($sections), JSON_UNESCAPED_UNICODE),
            'hero_title'     => mb_substr(post('hero_title'), 0, 150),
            'hero_text'      => mb_substr(post('hero_text'), 0, 300),
            'hero_cta_label' => mb_substr(post('hero_cta_label'), 0, 60),
            'hero_cta_url'   => mb_substr(post('hero_cta_url'), 0, 190),
            'hero_cta2_label' => mb_substr(post('hero_cta2_label'), 0, 60),
            'hero_cta2_url'   => mb_substr(post('hero_cta2_url'), 0, 190),
            'compare_cta_title' => mb_substr(post('compare_cta_title'), 0, 150),
            'compare_cta_text'  => mb_substr(post('compare_cta_text'), 0, 300),
            'compare_cta_btn'   => mb_substr(post('compare_cta_btn'), 0, 60),
            'newsletter_title' => mb_substr(post('newsletter_title'), 0, 150),
            'newsletter_text'  => mb_substr(post('newsletter_text'), 0, 300),
        ]);
        flash_set('success', 'Homepage content saved.');
    } elseif ($action === 'scripts') {
        save_settings([
            'header_scripts' => (string)($_POST['header_scripts'] ?? ''),
            'footer_scripts' => (string)($_POST['footer_scripts'] ?? ''),
            'custom_css'     => (string)($_POST['custom_css'] ?? ''),
        ]);
        flash_set('success', 'Custom code saved.');
    }
    redirect('admin/appearance.php');
}

$sections = json_decode(setting('homepage_sections'), true) ?: [];
$ADMIN_ACTIVE = 'appearance';
$ADMIN_TITLE = 'Appearance';
include __DIR__ . '/includes/header.php';
?>
<div class="tabs" role="tablist">
    <button type="button" class="tab active" data-tab="theme">Theme &amp; Colors</button>
    <button type="button" class="tab" data-tab="homepage">Homepage</button>
    <button type="button" class="tab" data-tab="scripts">Custom code</button>
</div>

<div class="tab-panel" data-panel="theme">
    <form method="post" class="admin-form panel">
        <?= csrf_field() ?><input type="hidden" name="action" value="theme">
        <div class="panel-head"><h2>Brand colors</h2>
            <button type="submit" name="reset" value="1" class="btn btn-outline btn-sm">Reset to stock theme</button>
        </div>
        <div class="form-row cols-2">
            <div class="form-field">
                <label for="t-primary">Primary / accent color</label>
                <div class="color-field">
                    <input type="color" id="t-primary" name="theme_primary" value="<?= e(setting('theme_primary') ?: '#d1252f') ?>">
                    <span class="hint">Buttons, links, highlights (default #d1252f)</span>
                </div>
            </div>
            <div class="form-field">
                <label for="t-secondary">Dark / secondary color</label>
                <div class="color-field">
                    <input type="color" id="t-secondary" name="theme_secondary" value="<?= e(setting('theme_secondary') ?: '#0e1b2c') ?>">
                    <span class="hint">Header hero, footers, dark buttons (default #0e1b2c)</span>
                </div>
            </div>
        </div>

        <div class="panel-head"><h2>Shape &amp; style</h2></div>
        <div class="form-row cols-2">
            <div class="form-field">
                <label for="t-radius">Corner roundness</label>
                <select id="t-radius" name="theme_radius" class="select">
                    <?php $r = setting('theme_radius', 'standard'); ?>
                    <option value="sharp" <?= $r === 'sharp' ? 'selected' : '' ?>>Sharp — angular, performance look</option>
                    <option value="standard" <?= $r === 'standard' ? 'selected' : '' ?>>Standard — balanced (default)</option>
                    <option value="rounded" <?= $r === 'rounded' ? 'selected' : '' ?>>Rounded — soft, friendly</option>
                </select>
            </div>
            <div class="form-field">
                <label for="t-chamfer">Primary button shape</label>
                <select id="t-chamfer" name="theme_chamfer" class="select">
                    <?php $c = setting('theme_chamfer', '1'); ?>
                    <option value="1" <?= $c === '1' ? 'selected' : '' ?>>Chamfered corners (signature look)</option>
                    <option value="0" <?= $c === '0' ? 'selected' : '' ?>>Classic rounded</option>
                </select>
            </div>
        </div>

        <div class="panel-head"><h2>Defaults</h2></div>
        <div class="form-row cols-2">
            <div class="form-field">
                <label for="t-mode">Default color mode (first visit)</label>
                <select id="t-mode" name="theme_mode" class="select">
                    <?php $m = setting('theme_mode', 'auto'); ?>
                    <option value="auto" <?= $m === 'auto' ? 'selected' : '' ?>>Auto — follow visitor’s system</option>
                    <option value="light" <?= $m === 'light' ? 'selected' : '' ?>>Always start in light mode</option>
                    <option value="dark" <?= $m === 'dark' ? 'selected' : '' ?>>Always start in dark mode</option>
                </select>
                <p class="hint">Visitors can still toggle; their choice is remembered.</p>
            </div>
            <div class="form-field">
                <label for="t-scale">Text size</label>
                <select id="t-scale" name="theme_font_scale" class="select">
                    <?php $f = setting('theme_font_scale', 'normal'); ?>
                    <option value="compact" <?= $f === 'compact' ? 'selected' : '' ?>>Compact (95%)</option>
                    <option value="normal" <?= $f === 'normal' ? 'selected' : '' ?>>Normal (100%)</option>
                    <option value="large" <?= $f === 'large' ? 'selected' : '' ?>>Large (106%)</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save theme</button>
    </form>
</div>

<div class="tab-panel" data-panel="homepage" hidden>
    <form method="post" class="admin-form panel">
        <?= csrf_field() ?><input type="hidden" name="action" value="homepage">
        <div class="panel-head"><h2>Hero section</h2></div>
        <div class="form-row cols-2">
            <div class="form-field"><label for="h-title">Headline</label><input type="text" id="h-title" name="hero_title" class="input input-lg" value="<?= e(setting('hero_title', 'Find your next car with confidence.')) ?>"></div>
            <div class="form-field"><label for="h-text">Supporting text</label><input type="text" id="h-text" name="hero_text" class="input" value="<?= e(setting('hero_text', 'Independent specifications, honest reviews and up-to-date prices — everything you need before you visit the showroom.')) ?>"></div>
        </div>
        <div class="form-row cols-2">
            <div class="form-field"><label for="h-cta1">Primary button label</label><input type="text" id="h-cta1" name="hero_cta_label" class="input" value="<?= e(setting('hero_cta_label', 'Browse Cars')) ?>"><p class="hint">Leave empty to hide the button.</p></div>
            <div class="form-field"><label for="h-cta1u">Primary button link</label><input type="text" id="h-cta1u" name="hero_cta_url" class="input" value="<?= e(setting('hero_cta_url', 'cars')) ?>"></div>
            <div class="form-field"><label for="h-cta2">Secondary button label</label><input type="text" id="h-cta2" name="hero_cta2_label" class="input" value="<?= e(setting('hero_cta2_label', 'Compare Models')) ?>"></div>
            <div class="form-field"><label for="h-cta2u">Secondary button link</label><input type="text" id="h-cta2u" name="hero_cta2_url" class="input" value="<?= e(setting('hero_cta2_url', 'compare')) ?>"></div>
        </div>
        <p class="hint" style="margin-bottom:1.2rem">Hero background image &amp; kicker text: Settings → General (hero image) and the site tagline.</p>

        <div class="panel-head"><h2>Compare &amp; newsletter sections</h2></div>
        <div class="form-row cols-2">
            <div class="form-field"><label for="cc-title">Compare CTA heading</label><input type="text" id="cc-title" name="compare_cta_title" class="input" value="<?= e(setting('compare_cta_title', "Can't decide? Let the specs decide.")) ?>"></div>
            <div class="form-field"><label for="cc-btn">Compare button label</label><input type="text" id="cc-btn" name="compare_cta_btn" class="input" value="<?= e(setting('compare_cta_btn', 'Start Comparing')) ?>"></div>
            <div class="form-field"><label for="cc-text">Compare CTA text</label><input type="text" id="cc-text" name="compare_cta_text" class="input" value="<?= e(setting('compare_cta_text', 'Put up to three cars side by side — price, power, economy and dimensions — and see the real differences highlighted automatically.')) ?>"></div>
            <div class="form-field"><label for="nl-title">Newsletter heading</label><input type="text" id="nl-title" name="newsletter_title" class="input" value="<?= e(setting('newsletter_title', 'Never miss an update')) ?>"></div>
            <div class="form-field"><label for="nl-text">Newsletter text</label><input type="text" id="nl-text" name="newsletter_text" class="input" value="<?= e(setting('newsletter_text', 'New models, price changes and buying guides — straight to your inbox. No spam, unsubscribe anytime.')) ?>"></div>
        </div>

        <div class="panel-head"><h2>Section order &amp; visibility</h2></div>
        <p class="result-count">Toggle sections on/off, change their order and override headings. Changes apply to the homepage immediately.</p>
        <table class="admin-table">
            <thead><tr><th>Order</th><th>Section</th><th>Custom heading (optional)</th><th>Visible</th></tr></thead>
            <tbody>
            <?php foreach ($sections as $i => $s): ?>
                <tr>
                    <td style="white-space:nowrap">
                        <button type="button" class="btn btn-sm btn-outline section-move" data-dir="-1" aria-label="Move up">↑</button>
                        <button type="button" class="btn btn-sm btn-outline section-move" data-dir="1" aria-label="Move down">↓</button>
                    </td>
                    <td>
                        <input type="hidden" name="section_key[]" value="<?= e($s['key']) ?>">
                        <input type="hidden" name="section_label[]" value="<?= e($s['label']) ?>">
                        <strong><?= e($s['label'] ?: $s['key']) ?></strong>
                    </td>
                    <td><input type="text" name="section_head[]" class="input" value="<?= e($s['head'] ?? '') ?>"></td>
                    <td><label class="switch"><input type="checkbox" name="section_enabled[<?= $i ?>]" value="1" <?= !empty($s['enabled']) ? 'checked' : '' ?>><span></span></label></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" class="btn btn-primary">Save homepage layout</button>
    </form>
</div>

<div class="tab-panel" data-panel="scripts" hidden>
    <form method="post" class="admin-form panel">
        <?= csrf_field() ?><input type="hidden" name="action" value="scripts">
        <div class="form-field"><label for="sc-css">Custom CSS (injected into every page)</label><textarea id="sc-css" name="custom_css" class="textarea" rows="6" style="font-family:ui-monospace,monospace;font-size:.85rem" placeholder="/* e.g. .car-card-title{letter-spacing:.02em} */"><?= e(setting('custom_css')) ?></textarea></div>
        <div class="form-field"><label for="sc-head">Header scripts (&lt;head&gt; — meta tags, trackers)</label><textarea id="sc-head" name="header_scripts" class="textarea" rows="4" style="font-family:ui-monospace,monospace;font-size:.85rem"><?= e(setting('header_scripts')) ?></textarea></div>
        <div class="form-field"><label for="sc-foot">Footer scripts (before &lt;/body&gt; — analytics, chat widgets)</label><textarea id="sc-foot" name="footer_scripts" class="textarea" rows="4" style="font-family:ui-monospace,monospace;font-size:.85rem"><?= e(setting('footer_scripts')) ?></textarea></div>
        <button type="submit" class="btn btn-primary">Save custom code</button>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php';
