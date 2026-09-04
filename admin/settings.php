<?php
/** AutoPulse admin — site settings (Modules 18, 19): general, homepage sections, contact, footer, scripts, system. */
require __DIR__ . '/includes/bootstrap.php';
require_admin('settings');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');
    if ($action === 'general') {
        save_settings([
            'site_name'  => mb_substr(post('site_name'), 0, 100) ?: 'AutoPulse',
            'tagline'    => mb_substr(post('tagline'), 0, 150),
            'currency'   => mb_substr(post('currency'), 0, 8) ?: 'PKR',
            'hero_image' => post('hero_image'),
            'logo'       => post('logo'),
            'favicon'    => post('favicon'),
        ]);
        flash_set('success', 'General settings saved.');
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
    } elseif ($action === 'contact') {
        save_settings([
            'contact_email'   => mb_substr(post('contact_email'), 0, 190),
            'contact_phone'   => mb_substr(post('contact_phone'), 0, 40),
            'contact_address' => mb_substr(post('contact_address'), 0, 300),
            'social_facebook'  => mb_substr(post('social_facebook'), 0, 255),
            'social_twitter'   => mb_substr(post('social_twitter'), 0, 255),
            'social_instagram' => mb_substr(post('social_instagram'), 0, 255),
            'social_youtube'   => mb_substr(post('social_youtube'), 0, 255),
        ]);
        flash_set('success', 'Contact & social settings saved.');
    } elseif ($action === 'footer') {
        save_settings([
            'footer_about'   => mb_substr(post('footer_about'), 0, 500),
            'copyright_text' => mb_substr(post('copyright_text'), 0, 200),
        ]);
        flash_set('success', 'Footer settings saved.');
    } elseif ($action === 'scripts') {
        save_settings([
            'header_scripts' => (string)($_POST['header_scripts'] ?? ''),
            'footer_scripts' => (string)($_POST['footer_scripts'] ?? ''),
            'custom_css'     => (string)($_POST['custom_css'] ?? ''),
        ]);
        flash_set('success', 'Custom code saved.');
    } elseif ($action === 'system') {
        save_settings([
            'maintenance_mode' => post('maintenance_mode') === '1' ? '1' : '0',
            'comments_enabled' => post('comments_enabled') === '1' ? '1' : '0',
        ]);
        flash_set('success', 'System settings saved.');
    }
    redirect('admin/settings.php');
}

$sections = json_decode(setting('homepage_sections'), true) ?: [];
$ADMIN_ACTIVE = 'settings';
$ADMIN_TITLE = 'Settings';
include __DIR__ . '/includes/header.php';

function img_picker_setting(string $name, string $label, string $current): void
{
    ?>
    <div class="form-field">
        <label><?= e($label) ?></label>
        <div class="image-picker" data-input="<?= e($name) ?>">
            <img class="picker-preview" src="<?= e(img_url($current)) ?>" alt="">
            <input type="hidden" name="<?= e($name) ?>" value="<?= e($current) ?>">
            <div class="picker-actions">
                <button type="button" class="btn btn-sm btn-outline picker-open">Choose</button>
                <button type="button" class="btn btn-sm btn-ghost picker-clear">Clear</button>
            </div>
        </div>
    </div>
    <?php
}
?>
<div class="tabs" role="tablist">
    <button type="button" class="tab active" data-tab="general">General</button>
    <button type="button" class="tab" data-tab="homepage">Homepage</button>
    <button type="button" class="tab" data-tab="contact">Contact &amp; Social</button>
    <button type="button" class="tab" data-tab="footer">Footer</button>
    <button type="button" class="tab" data-tab="scripts">Custom code</button>
    <button type="button" class="tab" data-tab="system">System</button>
</div>

<div class="tab-panel" data-panel="general">
    <form method="post" class="admin-form panel">
        <?= csrf_field() ?><input type="hidden" name="action" value="general">
        <div class="form-row cols-2">
            <div class="form-field"><label for="g-name">Website name</label><input type="text" id="g-name" name="site_name" class="input" value="<?= e(setting('site_name')) ?>"></div>
            <div class="form-field"><label for="g-tagline">Tagline</label><input type="text" id="g-tagline" name="tagline" class="input" value="<?= e(setting('tagline')) ?>"></div>
            <div class="form-field"><label for="g-currency">Currency label</label><input type="text" id="g-currency" name="currency" class="input" value="<?= e(setting('currency', DEFAULT_CURRENCY)) ?>"></div>
        </div>
        <div class="form-row cols-3">
            <?php img_picker_setting('logo', 'Logo', setting('logo')); ?>
            <?php img_picker_setting('favicon', 'Favicon', setting('favicon')); ?>
            <?php img_picker_setting('hero_image', 'Hero image', setting('hero_image')); ?>
        </div>
        <button type="submit" class="btn btn-primary">Save general settings</button>
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

<div class="tab-panel" data-panel="contact" hidden>
    <form method="post" class="admin-form panel">
        <?= csrf_field() ?><input type="hidden" name="action" value="contact">
        <div class="form-row cols-2">
            <div class="form-field"><label for="c-email">Contact email</label><input type="email" id="c-email" name="contact_email" class="input" value="<?= e(setting('contact_email')) ?>"></div>
            <div class="form-field"><label for="c-phone">Phone</label><input type="text" id="c-phone" name="contact_phone" class="input" value="<?= e(setting('contact_phone')) ?>"></div>
        </div>
        <div class="form-field"><label for="c-address">Address</label><input type="text" id="c-address" name="contact_address" class="input" value="<?= e(setting('contact_address')) ?>"></div>
        <div class="form-row cols-2">
            <div class="form-field"><label for="c-fb">Facebook URL</label><input type="url" id="c-fb" name="social_facebook" class="input" value="<?= e(setting('social_facebook')) ?>"></div>
            <div class="form-field"><label for="c-tw">X / Twitter URL</label><input type="url" id="c-tw" name="social_twitter" class="input" value="<?= e(setting('social_twitter')) ?>"></div>
            <div class="form-field"><label for="c-ig">Instagram URL</label><input type="url" id="c-ig" name="social_instagram" class="input" value="<?= e(setting('social_instagram')) ?>"></div>
            <div class="form-field"><label for="c-yt">YouTube URL</label><input type="url" id="c-yt" name="social_youtube" class="input" value="<?= e(setting('social_youtube')) ?>"></div>
        </div>
        <button type="submit" class="btn btn-primary">Save contact settings</button>
    </form>
</div>

<div class="tab-panel" data-panel="footer" hidden>
    <form method="post" class="admin-form panel">
        <?= csrf_field() ?><input type="hidden" name="action" value="footer">
        <div class="form-field"><label for="f-about">Footer about text</label><textarea id="f-about" name="footer_about" class="textarea" rows="3" maxlength="500"><?= e(setting('footer_about')) ?></textarea></div>
        <div class="form-field"><label for="f-copy">Copyright text</label><input type="text" id="f-copy" name="copyright_text" class="input" value="<?= e(setting('copyright_text')) ?>"></div>
        <p class="hint">Footer links (Privacy, Terms…) are managed under <a href="<?= e(admin_url('pages.php')) ?>">Pages</a> — tick “Show link in footer”.</p>
        <button type="submit" class="btn btn-primary">Save footer settings</button>
    </form>
</div>

<div class="tab-panel" data-panel="scripts" hidden>
    <form method="post" class="admin-form panel">
        <?= csrf_field() ?><input type="hidden" name="action" value="scripts">
        <div class="form-field"><label for="sc-head">Header scripts (&lt;head&gt;)</label><textarea id="sc-head" name="header_scripts" class="textarea" rows="4" style="font-family:ui-monospace,monospace;font-size:.85rem"><?= e(setting('header_scripts')) ?></textarea></div>
        <div class="form-field"><label for="sc-foot">Footer scripts (before &lt;/body&gt;)</label><textarea id="sc-foot" name="footer_scripts" class="textarea" rows="4" style="font-family:ui-monospace,monospace;font-size:.85rem"><?= e(setting('footer_scripts')) ?></textarea></div>
        <div class="form-field"><label for="sc-css">Custom CSS (injected into every page)</label><textarea id="sc-css" name="custom_css" class="textarea" rows="5" style="font-family:ui-monospace,monospace;font-size:.85rem"><?= e(setting('custom_css')) ?></textarea></div>
        <button type="submit" class="btn btn-primary">Save custom code</button>
    </form>
</div>

<div class="tab-panel" data-panel="system" hidden>
    <form method="post" class="admin-form panel">
        <?= csrf_field() ?><input type="hidden" name="action" value="system">
        <label class="check"><input type="checkbox" name="maintenance_mode" value="1" <?= setting('maintenance_mode') === '1' ? 'checked' : '' ?>> Maintenance mode (visitors see a “be right back” page; admins stay logged in)</label>
        <label class="check"><input type="checkbox" name="comments_enabled" value="1" <?= setting('comments_enabled', '1') === '1' ? 'checked' : '' ?>> Allow new comments</label>
        <button type="submit" class="btn btn-primary">Save system settings</button>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
