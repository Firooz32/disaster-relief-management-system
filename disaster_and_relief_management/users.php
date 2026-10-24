<?php
/** Administrator only: add users, change roles, reset passwords, delete users. */
declare(strict_types=1);
require __DIR__ . '/inc/boot.php';
guard_admin();

const ROLES = ['admin', 'editor', 'viewer'];
$me = (int) $_SESSION['uid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $op   = (string) ($_POST['op'] ?? '');
    $id   = (int) ($_POST['id'] ?? 0);
    $role = (string) ($_POST['role'] ?? '');
    $pw   = (string) ($_POST['password'] ?? '');
    try {
        if (!csrf_ok()) {
            throw new RuntimeException('The page expired - please try again.');
        }
        if ($op === 'add') {
            $name = trim((string) ($_POST['username'] ?? ''));
            if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $name)) throw new RuntimeException('User name: 3-50 letters, digits, dot, dash or underscore.');
            if (!in_array($role, ROLES, true)) throw new RuntimeException('Choose a role.');
            if (strlen($pw) < 8) throw new RuntimeException('Password must be at least 8 characters.');
            q('INSERT INTO users (Username, Password_Hash, Role) VALUES (?, ?, ?)', [$name, password_hash($pw, PASSWORD_DEFAULT), $role]);
            audit('add', 'users', $name);
            flash('ok', "User $name created.");
        } elseif ($op === 'role') {
            if ($id === $me) throw new RuntimeException('You cannot change your own role (it would risk locking everyone out).');
            if (!in_array($role, ROLES, true)) throw new RuntimeException('Choose a role.');
            q('UPDATE users SET Role = ? WHERE User_ID = ?', [$role, $id]);
            audit('edit', 'users', "role of #$id");
            flash('ok', 'Role updated.');
        } elseif ($op === 'reset') {
            if (strlen($pw) < 8) throw new RuntimeException('Password must be at least 8 characters.');
            q('UPDATE users SET Password_Hash = ?, Failed_Logins = 0, Locked_Until = NULL WHERE User_ID = ?', [password_hash($pw, PASSWORD_DEFAULT), $id]);
            audit('edit', 'users', "password of #$id");
            flash('ok', 'Password reset.');
        } elseif ($op === 'delete') {
            if ($id === $me) throw new RuntimeException('You cannot delete your own account.');
            q('DELETE FROM users WHERE User_ID = ?', [$id]);
            audit('delete', 'users', "#$id");
            flash('ok', 'User deleted.');
        }
    } catch (mysqli_sql_exception $e) {
        flash('err', $e->getCode() === 1062 ? 'That user name already exists.' : 'Database error.');
        error_log('[DRMS] ' . $e->getMessage());
    } catch (RuntimeException $e) {
        flash('err', $e->getMessage());
    }
    header('Location: users.php');
    exit;
}

$users = rows('SELECT User_ID, Username, Role, Failed_Logins, (Locked_Until IS NOT NULL AND Locked_Until > NOW()) AS locked, Created_At FROM users ORDER BY User_ID');

function role_select(string $sel): string
{
    $o = '';
    foreach (ROLES as $r) {
        $o .= '<option value="' . $r . '"' . ($r === $sel ? ' selected' : '') . '>' . $r . '</option>';
    }
    return $o;
}

page_start('Users', 'users');
?>
<section class="panel">
  <h2>Roles</h2>
  <p class="muted small"><b>admin</b> - everything, including this page. <b>editor</b> - add / edit / delete records. <b>viewer</b> - read-only.</p>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>ID</th><th>User name</th><th>Role</th><th>Status</th><th>Created</th><th class="r">Actions</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): $self = (int) $u['User_ID'] === $me; ?>
        <tr>
          <td><?= h($u['User_ID']) ?></td>
          <td><b><?= h($u['Username']) ?></b><?= $self ? ' <span class="muted">(you)</span>' : '' ?></td>
          <td>
            <?php if ($self): ?><?= h($u['Role']) ?><?php else: ?>
            <form class="inline" method="post">
              <input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="op" value="role"><input type="hidden" name="id" value="<?= h($u['User_ID']) ?>">
              <select name="role" data-autosubmit><?= role_select((string) $u['Role']) ?></select>
            </form>
            <?php endif; ?>
          </td>
          <td><?= $u['locked'] ? '<span class="muted">locked (too many wrong passwords)</span>' : 'active' ?></td>
          <td><?= h($u['Created_At']) ?></td>
          <td class="r nowrap">
            <form class="inline" method="post">
              <input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="op" value="reset"><input type="hidden" name="id" value="<?= h($u['User_ID']) ?>">
              <input type="password" name="password" placeholder="new password" minlength="8" required autocomplete="new-password" class="mini">
              <button class="btn btn-sm btn-ghost" type="submit">Reset password</button>
            </form>
            <?php if (!$self): ?>
            <form class="inline" method="post" data-confirm="Delete this user?">
              <input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="op" value="delete"><input type="hidden" name="id" value="<?= h($u['User_ID']) ?>">
              <button class="btn btn-sm btn-danger" type="submit">Delete</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="panel narrow">
  <h2>Add a user</h2>
  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="op" value="add">
    <label class="fld"><span>User name</span><input type="text" name="username" required maxlength="50"></label>
    <label class="fld"><span>Password (min 8 characters)</span><input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
    <label class="fld"><span>Role</span><select name="role"><?= role_select('viewer') ?></select></label>
    <div class="actions"><button class="btn btn-primary" type="submit">Add user</button></div>
  </form>
</section>
<?php page_end(); ?>
