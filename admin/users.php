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
        $role = post('role') === 'admin' ? 'admin' : 'editor';
        $password = (string)($_POST['password'] ?? '');
        if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_set('error', 'Valid username and email are required.');
        } elseif ($id === 0 && mb_strlen($password) < 8) {
            flash_set('error', 'Password must be at least 8 characters.');
        } else {
            $dup = db()->prepare('SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?)' . ($id ? ' AND id != ?' : ''));
            $dup->execute($id ? [$username, $email, $id] : [$username, $email]);
            if ((int)$dup->fetchColumn() > 0) {
                flash_set('error', 'Username or email already in use.');
            } elseif ($id) {
                if ($password !== '') {
                    db()->prepare('UPDATE users SET username = ?, email = ?, role = ?, password_hash = ? WHERE id = ?')
                        ->execute([$username, $email, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
                } else {
                    db()->prepare('UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?')->execute([$username, $email, $role, $id]);
                }
                flash_set('success', 'User updated.');
            } else {
                db()->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)')
                    ->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                flash_set('success', 'User created.');
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
    $st = db()->prepare('SELECT id, username, email, role, status, last_login FROM users WHERE id = ?');
    $st->execute([get_int('edit', 0)]);
    $editing = $st->fetch() ?: null;
}
$users = db()->query('SELECT id, username, email, role, status, last_login FROM users ORDER BY id')->fetchAll();

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
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save user' : 'Create user' ?></button>
    </form>
</section>

<section class="panel">
    <table class="admin-table">
        <thead><tr><th>User</th><th>Role</th><th>Last login</th><th class="th-actions">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><strong><?= e($u['username']) ?></strong> <small><?= e($u['email']) ?></small></td>
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
