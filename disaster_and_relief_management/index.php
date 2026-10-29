<?php
/** Sign-in page. On the very first visit (no users yet) it asks you to create the administrator account instead. */
declare(strict_types=1);
require __DIR__ . '/inc/boot.php';

const MAX_FAILS = 5;       // wrong passwords before the account is locked
const LOCK_MINUTES = 5;

$dbErr = '';
$setup = false;
try {
    ensure_installed();    // creates the database and loads the SQL file on the very first visit
    $setup = scalar('SELECT COUNT(*) FROM users') === 0.0;
} catch (Throwable $e) {
    error_log('[DRMS] ' . $e->getMessage());
    $dbErr = database_error_message($e);
}

if ($dbErr === '' && logged_in()) {
    header('Location: dashboard.php');
    exit;
}

function sign_in(int $uid): never
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $uid;
    header('Location: dashboard.php');
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dbErr === '') {
    $name = trim((string) ($_POST['username'] ?? ''));
    $pw   = (string) ($_POST['password'] ?? '');
    if (!csrf_ok()) {
        $err = 'The page expired - please try again.';
    } elseif ($setup) {
        if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $name)) {
            $err = 'User name: 3-50 letters, digits, dot, dash or underscore.';
        } elseif (strlen($pw) < 8) {
            $err = 'Password must be at least 8 characters.';
        } elseif ($pw !== (string) ($_POST['password2'] ?? '')) {
            $err = 'The two passwords do not match.';
        } else {
            // one atomic statement: only succeeds while the users table is still empty
            q("INSERT INTO users (Username, Password_Hash, Role) SELECT ?, ?, 'admin' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users)",
              [$name, password_hash($pw, PASSWORD_DEFAULT)]);
            $new = row1('SELECT User_ID, Password_Hash FROM users WHERE Username = ?', [$name]);
            if ($new && password_verify($pw, (string) $new['Password_Hash'])) {      // our insert won (not a simultaneous first visitor)
                sign_in((int) $new['User_ID']);
            }
            $err = 'An account already exists - please sign in.';
            $setup = false;
        }
    } else {
        $u = row1('SELECT User_ID, Password_Hash, (Locked_Until IS NOT NULL AND Locked_Until > NOW()) AS locked,
                          (Locked_Until IS NOT NULL AND Locked_Until <= NOW()) AS lock_over FROM users WHERE Username = ?', [$name]);
        if ($u && $u['lock_over']) {                       // the lock ran out: a fresh set of attempts, not one more wrong try = locked again
            q('UPDATE users SET Failed_Logins = 0, Locked_Until = NULL WHERE User_ID = ?', [$u['User_ID']]);
        }
        $ok = password_verify($pw, $u['Password_Hash'] ?? password_hash('x', PASSWORD_DEFAULT));   // same work for unknown names
        if ($u && $u['locked']) {
            $err = 'Too many wrong passwords. Try again in ' . LOCK_MINUTES . ' minutes.';
        } elseif ($u && $ok) {
            q('UPDATE users SET Failed_Logins = 0, Locked_Until = NULL WHERE User_ID = ?', [$u['User_ID']]);
            sign_in((int) $u['User_ID']);
        } else {
            if ($u) {
                q('UPDATE users SET Failed_Logins = Failed_Logins + 1, Locked_Until = IF(Failed_Logins >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), Locked_Until) WHERE User_ID = ?',
                  [MAX_FAILS, LOCK_MINUTES, $u['User_ID']]);
            }
            $err = 'Wrong user name or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in - Disaster &amp; Relief Management</title>
<link rel="icon" type="image/png" href="assets/logo.png">
<link rel="stylesheet" href="assets/style.css">
<script src="assets/i18n.js?v=<?= (int) filemtime(__DIR__ . '/assets/i18n.js') ?>" defer></script>
</head>
<body class="login-body">
<div class="login-card">
  <div class="login-language"><button class="btn btn-sm btn-ghost language-toggle" type="button" data-language-toggle aria-label="Switch to Bangla" title="Switch to Bangla">বাংলা</button></div>
  <img class="logo-img big" src="assets/logo.png" width="64" height="64" alt="Disaster Mapping &amp; Relief Management System">
  <h1>Disaster Mapping &amp; Relief Management System</h1>
  <p class="muted"><?= $setup ? 'First run: create the administrator account' : 'Sign in to continue' ?></p>
  <?php if ($dbErr !== ''): ?>
    <div class="alert alert-err"><b>Database problem:</b> <?= h($dbErr) ?></div>
  <?php endif; ?>
  <?php if ($err !== ''): ?><div class="alert alert-err"><?= h($err) ?></div><?php endif; ?>
  <?php if ($dbErr === ''): ?>
  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
    <label>User name
      <input type="text" name="username" value="<?= h($_POST['username'] ?? '') ?>" required autofocus maxlength="50">
    </label>
    <label>Password<?= $setup ? ' (min 8 characters)' : '' ?>
      <input type="password" name="password" required<?= $setup ? ' minlength="8"' : '' ?> autocomplete="<?= $setup ? 'new-password' : 'current-password' ?>">
    </label>
    <?php if ($setup): ?>
    <label>Repeat password
      <input type="password" name="password2" required minlength="8" autocomplete="new-password">
    </label>
    <?php endif; ?>
    <button class="btn btn-primary w100" type="submit"><?= $setup ? 'Create administrator' : 'Sign in' ?></button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>
