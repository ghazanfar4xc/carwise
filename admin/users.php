<?php
/** AutoPulse admin — user management (admin role only). */
require __DIR__ . '/includes/bootstrap.php';
require_admin('users');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');
    if ($action === 'save') {
        $id = (int)post('id');
        $username = mb_substr(post('username'), 0, 50);
        $email = mb_substr(post('email'), 0, 190);
        $displayName = mb_substr(trim(post('display_name')), 0, 80);
        $bio = mb_substr(trim((string)($_POST['bio'] ?? '')), 0, 2000);
        $expertise = mb_substr(trim(post('expertise')), 0, 255);
        $socialTwitter = rtrim(trim(post('social_twitter')), '/');
        $socialLinkedin = rtrim(trim(post('social_linkedin')), '/');
        if ($socialTwitter !== '' && !preg_match('#^https://#', $socialTwitter)) $socialTwitter = '';
        if ($socialLinkedin !== '' && !preg_match('#^https://#', $socialLinkedin)) $socialLinkedin = '';
        $avatar = trim((string)($_POST['avatar'] ?? ''));
        if ($avatar !== '' && !preg_match('#^(uploads|assets)/[\w\-./]+$#', $avatar)) $avatar = '';
        $slug = slugify($displayName ?: $username);
        $role = post('role') === 'admin' ? 'admin' : 'editor';
        $password = (string)($_POST['password'] ?? '');
        if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_set('error', 'Valid username and email are required.');
        } elseif ($id === 0 && mb_strlen($password) < 8) {
            flash_set('error', 'Password must be at least 8 characters.');
        } else {
            $dupSlug = db()->prepare('SELECT COUNT(*) FROM users WHERE slug = ?' . ($id ? ' AND id != ?' : ''));
            $dupSlug->execute($id ? [$slug, $id] : [$slug]);
            if ((int)$dupSlug->fetchColumn() > 0) $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 3); // profile URL stays unique
            $dup = db()->prepare('SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?)' . ($id ? ' AND id != ?' : ''));
            $dup->execute($id ? [$username, $email, $id] : [$username, $email]);
            if ((int)$dup->fetchColumn() > 0) {
                flash_set('error', 'Username or email already in use.');
            } else {
                $data = [$username, $displayName, $slug, $bio, $avatar, $expertise, $socialTwitter, $socialLinkedin, $email, $role];
                if ($id) {
                    if ($password !== '') { $data[] = password_hash($password, PASSWORD_DEFAULT); }
                    $data[] = $id;
                    db()->prepare('UPDATE users SET username = ?, display_name = ?, slug = ?, bio = ?, avatar = ?, expertise = ?, social_twitter = ?, social_linkedin = ?, email = ?, role = ?'
                        . ($password !== '' ? ', password_hash = ?' : '') . ' WHERE id = ?')->execute($data);
                    flash_set('success', 'User updated.');
                } else {
                    $data[] = password_hash($password, PASSWORD_DEFAULT); // required on create (validated above)
                    db()->prepare('INSERT INTO users (username, display_name, slug, bio, avatar, expertise, social_twitter, social_linkedin, email, role, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                        ->execute($data); // password appended above
                    flash_set('success', 'User created.');
                }
            }
        }
    } elseif ($action === 'delete' && (int)post('id')) {
        if ((int)post('id') === (int)current_user()['id']) {
            flash_set('error', 'You cannot delete your own account.');
        } else {
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([(int)post('id')]);
            flash_set('success', 'User deleted.');
        }
    }
    redirect('admin/users.php');
}

$editing = null;
if (get_int('edit', 0)) {
    $st = db()->prepare('SELECT id, username, display_name, slug, bio, avatar, expertise, social_twitter, social_linkedin, email, role, status, last_login FROM users WHERE id = ?');
    $st->execute([get_int('edit', 0)]);
    $editing = $st->fetch() ?: null;
}
$users = db()->query('SELECT id, username, display_name, email, role, status, last_login FROM users ORDER BY id')->fetchAll();

$ADMIN_ACTIVE = 'users';
$ADMIN_TITLE = 'Users';
include __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2><?= $editing ? 'Edit user: ' . e($editing['username']) : 'Add user' ?></h2>
        <?php if ($editing): ?><a class="link-arrow" href="<?= e(admin_url('users.php')) ?>">+ New instead →</a><?php endif; ?>
    </div>
    <form method="post" class="admin-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
        <div class="form-row cols-2">
            <div class="form-field"><label for="u-name">Username *</label><input type="text" id="u-name" name="username" class="input" required value="<?= fv('username', $editing['username'] ?? '') ?>"></div>
            <div class="form-field"><label for="u-email">Email *</label><input type="email" id="u-email" name="email" class="input" required value="<?= fv('email', $editing['email'] ?? '') ?>"></div>
        </div>
        <div class="form-row cols-2">
            <div class="form-field">
                <label for="u-dname">Display name <span class="hint" style="display:inline">— shown as the article byline</span></label>
                <input type="text" id="u-dname" name="display_name" class="input" maxlength="80" value="<?= fv('display_name', $editing['display_name'] ?? '') ?>" placeholder="e.g. Emily Carter">
            </div>
            <div class="form-field">
                <label for="u-avatar">Avatar image path</label>
                <input type="text" id="u-avatar" name="avatar" class="input" value="<?= fv('avatar', $editing['avatar'] ?? '') ?>" placeholder="uploads/team/emily.jpg (from Media)">
            </div>
            <div class="form-field">
                <label for="u-role">Role</label>
                <select id="u-role" name="role" class="select">
                    <option value="editor" <?= fv('role', $editing['role'] ?? 'editor') === 'editor' ? 'selected' : '' ?>>Editor (content only)</option>
                    <option value="admin" <?= fv('role', $editing['role'] ?? 'editor') === 'admin' ? 'selected' : '' ?>>Administrator (full access)</option>
                </select>
            </div>
            <div class="form-field">
                <label for="u-pass">Password <?= $editing ? '(leave empty to keep current)' : '*' ?></label>
                <input type="password" id="u-pass" name="password" class="input" <?= $editing ? '' : 'required' ?> minlength="8" autocomplete="new-password">
            </div>
        </div>
        <div class="form-field">
            <label for="u-bio">Author bio <span class="hint" style="display:inline">&mdash; shown on the public profile page (/author/<?= e($editing['slug'] ?? 'your-name') ?>)</span></label>
            <textarea id="u-bio" name="bio" class="textarea" rows="3" maxlength="2000" placeholder="e.g. Senior editor covering SUVs and trucks. Based in Austin, Texas."><?= fv('bio', $editing['bio'] ?? '') ?></textarea>
        </div>
        <div class="form-row cols-3">
            <div class="form-field">
                <label for="u-exp">Expertise <span class="hint" style="display:inline">(comma-separated, Person schema)</span></label>
                <input type="text" id="u-exp" name="expertise" class="input" value="<?= fv('expertise', $editing['expertise'] ?? '') ?>" placeholder="SUVs, trucks, pricing">
            </div>
            <div class="form-field">
                <label for="u-tw">X / Twitter URL</label>
                <input type="url" id="u-tw" name="social_twitter" class="input" value="<?= fv('social_twitter', $editing['social_twitter'] ?? '') ?>" placeholder="https://x.com/…">
            </div>
            <div class="form-field">
                <label for="u-li">LinkedIn URL</label>
                <input type="url" id="u-li" name="social_linkedin" class="input" value="<?= fv('social_linkedin', $editing['social_linkedin'] ?? '') ?>" placeholder="https://linkedin.com/in/…">
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save user' : 'Create user' ?></button>
    </form>
</section>

<section class="panel">
    <table class="admin-table">
        <thead><tr><th>User</th><th>Role</th><th>Last login</th><th class="th-actions">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><strong><?= e($u['display_name'] ?: $u['username']) ?></strong> <small><?= e($u['username']) ?> &middot; <?= e($u['email']) ?></small></td>
                <td><span class="status status-<?= $u['role'] ?>"><?= e($u['role']) ?></span></td>
                <td><small><?= e($u['last_login'] ? time_ago($u['last_login']) : 'never') ?></small></td>
                <td class="td-actions">
                    <form method="post" class="inline-form">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <a class="btn btn-sm btn-outline" href="<?= e(admin_url('users.php?edit=' . (int)$u['id'])) ?>">Edit</a>
                        <?php if ((int)$u['id'] !== (int)current_user()['id']): ?>
                            <button class="btn btn-sm btn-danger" data-confirm="Delete this user?">Delete</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
