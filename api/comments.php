<?php
/** AutoPulse — comment submission: POST /api/comments.php (JSON). Comments are moderated. */
require dirname(__DIR__) . '/includes/api_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['success' => false, 'message' => 'Method not allowed'], 405);
if (setting('comments_enabled', '1') !== '1') json_out(['success' => false, 'message' => 'Comments are disabled.']);
if (post('website') !== '') json_out(['success' => false, 'message' => 'Request rejected.']);
if (!rate_limit('comment', 3, 600)) json_out(['success' => false, 'message' => 'You are commenting too quickly. Please wait a few minutes.'], 429);

$articleId = (int)post('article_id');
$name   = post('author_name');
$email  = post('author_email');
$body   = post('body');

$st = db()->prepare('SELECT id FROM articles a WHERE a.id = ? AND ' . ARTICLE_LIVE);
$st->execute([$articleId]);
if (!$st->fetch()) json_out(['success' => false, 'message' => 'Article not found.'], 404);

$errors = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 60)      $errors['author_name'] = 'Please enter your name.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))          $errors['author_email'] = 'Please enter a valid email.';
if (mb_strlen($body) < 4 || mb_strlen($body) > 2000)     $errors['body'] = 'Comment must be 4–2000 characters.';
if ($errors) json_out(['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors], 422);

$st = db()->prepare('INSERT INTO comments (article_id, author_name, author_email, body, ip) VALUES (?, ?, ?, ?, ?)');
$st->execute([$articleId, $name, $email, $body, client_ip()]);

json_out(['success' => true, 'message' => 'Thank you! Your comment was submitted and will appear after moderation.']);
