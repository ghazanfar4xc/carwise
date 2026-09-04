<?php
/** AutoPulse — newsletter signup: POST /api/newsletter.php (JSON). */
require dirname(__DIR__) . '/includes/api_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['success' => false, 'message' => 'Method not allowed'], 405);
if (post('website') !== '') json_out(['success' => false, 'message' => 'Request rejected.']);
if (!rate_limit('newsletter', 5, 600)) json_out(['success' => false, 'message' => 'Too many attempts. Try later.'], 429);

$email = post('email');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
    json_out(['success' => false, 'message' => 'Please enter a valid email address.', 'errors' => ['email' => 'Invalid email']], 422);
}

$st = db()->prepare('INSERT INTO newsletter_subscribers (email) VALUES (?) ON DUPLICATE KEY UPDATE email = email');
$st->execute([$email]);

json_out(['success' => true, 'message' => 'Subscribed! You will hear from us soon.']);
