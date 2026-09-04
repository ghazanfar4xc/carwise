<?php
/** AutoPulse — contact page (Module 29). AJAX submission to /api/contact.php. */
seo_set([
    'title' => 'Contact Us',
    'description' => 'Get in touch with the ' . setting('site_name', 'AutoPulse') . ' team — questions, corrections, partnerships and feedback.',
]);

include __DIR__ . '/../includes/header.php';
?>
<header class="page-head">
    <div class="container">
        <?php render_breadcrumbs([['name' => 'Home', 'url' => ''], ['name' => 'Contact']]); ?>
        <h1>Contact Us</h1>
        <p>Questions, corrections or partnership ideas — we read everything.</p>
    </div>
</header>

<div class="section">
    <div class="container contact-layout">
        <div>
            <form id="contact-form" novalidate>
                <?= csrf_field() ?>
                <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
                <div class="form-row cols-2">
                    <div class="form-field">
                        <label for="ct-name">Name</label>
                        <input type="text" id="ct-name" name="name" class="input" required minlength="2" maxlength="80">
                        <p class="error-text">Please enter your name.</p>
                    </div>
                    <div class="form-field">
                        <label for="ct-email">Email</label>
                        <input type="email" id="ct-email" name="email" class="input" required maxlength="190">
                        <p class="error-text">Please enter a valid email address.</p>
                    </div>
                </div>
                <div class="form-field">
                    <label for="ct-subject">Subject</label>
                    <input type="text" id="ct-subject" name="subject" class="input" required maxlength="150">
                    <p class="error-text">Please add a subject.</p>
                </div>
                <div class="form-field">
                    <label for="ct-message">Message</label>
                    <textarea id="ct-message" name="message" class="textarea" required minlength="10" maxlength="5000"></textarea>
                    <p class="error-text">Please write a message (at least 10 characters).</p>
                </div>
                <button type="submit" class="btn btn-primary btn-lg">Send message</button>
            </form>
        </div>
        <aside class="contact-info">
            <?php ad_slot('sidebar'); ?>
            <div class="key-spec" style="text-align:left">
                <span>Editorial contact</span>
                <b style="font-size:var(--fs-md)"><?= e(setting('contact_email', 'hello@example.com')) ?></b>
            </div>
            <?php if (setting('contact_phone')): ?>
            <div class="key-spec" style="text-align:left"><span>Phone</span><b style="font-size:var(--fs-md)"><?= e(setting('contact_phone')) ?></b></div>
            <?php endif; ?>
            <?php if (setting('contact_address')): ?>
            <div class="key-spec" style="text-align:left"><span>Address</span><b style="font-size:var(--fs-md);font-weight:500"><?= e(setting('contact_address')) ?></b></div>
            <?php endif; ?>
            <p style="color:var(--color-muted);font-size:var(--fs-sm)">We usually reply within 2–3 working days. For corrections, please include the page URL.</p>
        </aside>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
