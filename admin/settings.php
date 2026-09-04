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
    <button type="button" class="tab" data-tab="contact">Contact &amp; Social</button>
    <button type="button" class="tab" data-tab="footer">Footer</button>
    <button type="button" class="tab" data-tab="system">System</button>
    <a class="tab" href="<?= e(admin_url('appearance.php')) ?>" style="color:var(--color-primary)">🎨 Appearance &amp; Homepage →</a>
    <a class="tab" href="<?= e(admin_url('appearance.php')) ?>" style="color:var(--color-primary)">🎨 Appearance &amp; Homepage →</a>
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

<div class="tab-panel" data-panel="system" hidden>
    <form method="post" class="admin-form panel">
        <?= csrf_field() ?><input type="hidden" name="action" value="system">
        <label class="check"><input type="checkbox" name="maintenance_mode" value="1" <?= setting('maintenance_mode') === '1' ? 'checked' : '' ?>> Maintenance mode (visitors see a “be right back” page; admins stay logged in)</label>
        <label class="check"><input type="checkbox" name="comments_enabled" value="1" <?= setting('comments_enabled', '1') === '1' ? 'checked' : '' ?>> Allow new comments</label>
        <button type="submit" class="btn btn-primary">Save system settings</button>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
