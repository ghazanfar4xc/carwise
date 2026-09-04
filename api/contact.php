<?php
/** AutoPulse — contact form: POST /api/contact.php (JSON). */
require dirname(__DIR__) . '/includes/api_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['success' => false, 'message' => 'Method not allowed'], 405);

// Honeypot: bots fill the hidden "website" field
if (post('website') !== '') json_out(['success' => false, 'message' => 'Request rejected.']);
if (!rate_limit('contact', 5, 900)) json_out(['success' => false, 'message' => 'Too many messages. Please try again later.'], 429);

$name    = post('name');
$email   = post('email');
$subject = post('subject');
$message = post('message');

$errors = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 80)         $errors['name'] = 'Please enter your name.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))             $errors['email'] = 'Please enter a valid email address.';
if (mb_strlen($subject) < 2 || mb_strlen($subject) > 150)   $errors['subject'] = 'Please add a subject.';
if (mb_strlen($message) < 10 || mb_strlen($message) > 5000) $errors['message'] = 'Message must be between 10 and 5000 characters.';
if ($errors) json_out(['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors], 422);

$st = db()->prepare('INSERT INTO contact_messages (name, email, subject, message, ip) VALUES (?, ?, ?, ?, ?)');
$st->execute([$name, $email, $subject, $message, client_ip()]);

// Best-effort email notification (InfinityFree mail() is unreliable — the admin inbox is the source of truth)
if (setting('contact_email') && function_exists('mail')) {
    @mail(setting('contact_email'), "[Website] {$subject}", "From: {$name} <{$email}>\n\n{$message}");
}

json_out(['success' => true, 'message' => 'Thanks! Your message has been received — we usually reply within 2–3 working days.']);
