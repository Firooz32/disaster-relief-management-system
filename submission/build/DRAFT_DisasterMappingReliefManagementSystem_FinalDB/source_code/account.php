<?php
/** Every user can change their own password here. */
declare(strict_types=1);
require __DIR__ . '/inc/boot.php';
guard();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cur = (string) ($_POST['current'] ?? '');
    $new = (string) ($_POST['new'] ?? '');
    $row = row1('SELECT Password_Hash FROM users WHERE User_ID = ?', [$_SESSION['uid']]);
    if (!csrf_ok()) {
        $err = 'The page expired - please try again.';
    } elseif (!$row || !password_verify($cur, (string) $row['Password_Hash'])) {
        $err = 'Your current password is wrong.';
    } elseif (strlen($new) < 8) {
        $err = 'The new password must be at least 8 characters.';
    } elseif ($new !== (string) ($_POST['new2'] ?? '')) {
        $err = 'The two new passwords do not match.';
    } else {
        q('UPDATE users SET Password_Hash = ? WHERE User_ID = ?', [password_hash($new, PASSWORD_DEFAULT), $_SESSION['uid']]);
        session_regenerate_id(true);
        flash('ok', 'Password changed.');
        header('Location: account.php');
        exit;
    }
}

page_start('My account', 'account');
?>
<section class="panel narrow">
  <p class="muted">Signed in as <b><?= h($_SESSION['user']) ?></b> (<?= h(role()) ?>).</p>
  <?php if ($err !== ''): ?><div class="alert alert-err"><?= h($err) ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
    <label class="fld"><span>Current password</span><input type="password" name="current" required autocomplete="current-password"></label>
    <label class="fld"><span>New password (min 8 characters)</span><input type="password" name="new" required minlength="8" autocomplete="new-password"></label>
    <label class="fld"><span>Repeat new password</span><input type="password" name="new2" required minlength="8" autocomplete="new-password"></label>
    <div class="actions"><button class="btn btn-primary" type="submit">Change password</button></div>
  </form>
</section>
<?php page_end(); ?>
