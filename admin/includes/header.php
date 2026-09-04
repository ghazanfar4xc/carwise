<?php
/**
 * AutoPulse admin — layout header (sidebar navigation).
 * Set  $ADMIN_ACTIVE = 'articles'  before including.
 */
if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }
$user = current_user();
$ADMIN_ACTIVE = $ADMIN_ACTIVE ?? '';

function admin_icon(string $name): string
{
    $p = [
        'dash'    => 'M3 13h8V3H3v10zm10 8h8V11h-8v10zM3 21h8v-6H3v6zM13 9h8V3h-8v6z',
        'article' => 'M4 4h16v16H4zM8 9h8M8 13h8M8 17h5',
        'car'     => 'M5 16 6.5 10h11L19 16M3 16h18v4H3zM7 20v2M17 20v2',
        'brand'   => 'M12 3 3 7l9 4 9-4-9-4zM3 12l9 4 9-4M3 17l9 4 9-4',
        'tag'     => 'M3 3h8l10 10-8 8L3 11V3zM8 8h.01',
        'page'    => 'M6 2h9l5 5v15H6zM15 2v5h5',
        'media'   => 'M3 5h18v14H3zM3 15l5-5 4 4 3-3 6 6',
        'menu'    => 'M4 6h16M4 12h16M4 18h10',
        'comment' => 'M4 4h16v12H8l-4 4V4z',
        'inbox'   => 'M3 11h6l2 3h2l2-3h6M3 11l3-7h12l3 7v9H3v-9z',
        'ads'     => 'M3 6h18v12H3zM9 6v12M15 6v12',
        'seo'     => 'M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14zM20 20l-3.5-3.5',
        'users'   => 'M8 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM2 21c0-4 3-6 6-6s6 2 6 6M16 4a3 3 0 0 1 0 6M17 15c2.5.5 4 2.5 4 6',
        'gear'    => 'M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6zM12 2v3m0 14v3M2 12h3m14 0h3M5 5l2 2m10 10 2 2M19 5l-2 2M7 17l-2 2',
        'db'      => 'M4 4c0-1 3.6-2 8-2s8 1 8 2-3.6 2-8 2-8-1-8-2zM4 4v16c0 1 3.6 2 8 2s8-1 8-2V4M4 12c0 1 3.6 2 8 2s8-1 8-2',
        'logout'  => 'M9 21H4V3h5M16 8l4 4-4 4M20 12H10',
        'view'    => 'M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
    ][$name] ?? '';
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . $p . '"/></svg>';
}

$nav = array_filter([
    ['dashboard', 'Dashboard', 'dash', ''],
    ['articles',  'Articles',  'article', ''],
    ['cars',      'Cars',      'car', ''],
    ['brands',    'Brands',    'brand', ''],
    ['taxonomy',  'Categories & Tags', 'tag', ''],
    ['pages',     'Pages',     'page', ''],
    ['media',     'Media',     'media', ''],
    ['menus',     'Menus',     'menu', ''],
    ['comments',  'Comments',  'comment', ''],
    ['messages',  'Inbox',     'inbox', ''],
    ['ads',       'Ads',       'ads', 'ads'],
    ['seo',       'SEO',       'seo', 'seo'],
    ['users',     'Users',     'users', 'users'],
    ['settings',  'Settings',  'gear', 'settings'],
    ['backup',    'Backup',    'db', 'backup'],
], fn($n) => $n[3] === '' || admin_can($n[3]));

$newMessages = 0;
$newComments = 0;
try {
    $newMessages = (int)db()->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
    $newComments = (int)db()->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
} catch (Throwable $e) { /* settings page handles db errors */ }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($ADMIN_TITLE ?? 'Admin') ?> — <?= e(setting('site_name', 'AutoPulse')) ?></title>
<link rel="icon" href="<?= e(url(setting('favicon') ?: 'assets/images/favicon.svg')) ?>" type="image/svg+xml">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-brand">
            <a href="<?= e(url('')) ?>" target="_blank" title="View site"><?= e(setting('site_name', 'AutoPulse')) ?></a>
            <button type="button" class="icon-btn admin-burger" id="admin-burger" aria-label="Toggle menu">☰</button>
        </div>
        <nav class="admin-nav" aria-label="Admin navigation">
            <?php foreach ($nav as [$key, $label, $icon, $cap]): ?>
                <a href="<?= e(admin_url(($key === 'dashboard' ? 'dashboard' : $key) . '.php')) ?>"
                   class="<?= $key === $ADMIN_ACTIVE ? 'active' : '' ?>">
                    <?= admin_icon($icon) ?><span><?= e($label) ?></span>
                    <?php if ($key === 'messages' && $newMessages): ?><em class="nav-count"><?= $newMessages ?></em><?php endif; ?>
                    <?php if ($key === 'comments' && $newComments): ?><em class="nav-count"><?= $newComments ?></em><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-side-foot">
            <div class="admin-user">
                <span class="avatar"><?= e(strtoupper(mb_substr($user['username'], 0, 1))) ?></span>
                <div><strong><?= e($user['username']) ?></strong><small><?= e($user['role']) ?></small></div>
            </div>
            <a class="btn btn-ghost btn-sm" href="<?= e(admin_url('logout.php')) ?>"><?= admin_icon('logout') ?> Log out</a>
        </div>
    </aside>
    <div class="admin-backdrop" id="admin-backdrop" hidden></div>

    <div class="admin-main">
        <header class="admin-topbar">
            <h1><?= e($ADMIN_TITLE ?? 'Dashboard') ?></h1>
            <div class="admin-topbar-actions">
                <?php if (empty($ADMIN_NO_ACTIONS)): ?>
                    <?php if ($ADMIN_ACTIVE === 'articles'): ?>
                        <a class="btn btn-primary btn-sm" href="<?= e(admin_url('article-edit.php')) ?>">+ New article</a>
                    <?php elseif ($ADMIN_ACTIVE === 'cars'): ?>
                        <a class="btn btn-primary btn-sm" href="<?= e(admin_url('car-edit.php')) ?>">+ New car</a>
                    <?php elseif ($ADMIN_ACTIVE === 'pages'): ?>
                        <a class="btn btn-primary btn-sm" href="<?= e(admin_url('page-edit.php')) ?>">+ New page</a>
                    <?php elseif ($ADMIN_ACTIVE === 'brands'): ?>
                        <a class="btn btn-primary btn-sm" href="<?= e(admin_url('brands.php')) ?>?edit=new">+ New brand</a>
                    <?php endif; ?>
                <?php endif; ?>
                <a class="btn btn-outline btn-sm" href="<?= e(url('')) ?>" target="_blank">View site ↗</a>
            </div>
        </header>
        <main class="admin-content">
        <?= flash_render() ?>
