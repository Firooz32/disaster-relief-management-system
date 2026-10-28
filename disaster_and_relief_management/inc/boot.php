<?php
/** Loaded by every page: error handling, session, roles, escaping, CSRF, audit trail, flash messages and the page layout. */
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/meta.php';

ini_set('display_errors', '0');          // details go to the PHP error log, never to the visitor
ini_set('log_errors', '1');
set_exception_handler(function (Throwable $e): void {
    error_log('[DRMS] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    $json = false;
    foreach (headers_list() as $hd) {
        $json = $json || stripos($hd, 'application/json') !== false;
    }
    echo $json ? '{"error":"server"}' : '<h1>Something went wrong</h1><p>The error was written to the server log. Check that MySQL is running and config.php is correct.</p>';
});

/** Hours without a click (page visit or form) before a signed-in user must sign in again. The Live Report's automatic refresh does not count. */
const SESSION_IDLE_HOURS = 4;

if (!headers_sent() && PHP_SAPI !== 'cli') {      // browser protections: no framing, no MIME sniffing, scripts only from this site and Leaflet's CDN
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');   // OpenStreetMap blocks map tiles when no Referer is sent
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://unpkg.com; style-src 'self' 'unsafe-inline' https://unpkg.com; "
        . "img-src 'self' data: https://tile.openstreetmap.org https://unpkg.com; connect-src 'self'; frame-src 'self' https://www.ventusky.com; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_name('drms_sid');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_start();
    $background = defined('DRMS_BACKGROUND_REQUEST');   // set by report_data.php / api.php before including this file
    if (isset($_SESSION['seen']) && time() - (int) $_SESSION['seen'] > SESSION_IDLE_HOURS * 3600) {
        $_SESSION = [];                                  // idle too long: forget the sign-in
    }
    if (!$background && !empty($_SESSION['uid'])) {
        $_SESSION['seen'] = time();
    }
}

function h(mixed $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** True when the session belongs to an existing user. Role and name are re-read from the database once per request. */
function logged_in(): bool
{
    static $ok = null;
    if ($ok === null) {
        $ok = false;
        if (!empty($_SESSION['uid'])) {
            if (empty($_SESSION['schema_ok'])) {
                ensure_installed();        // a database of the previous version is upgraded even for users who are already signed in
                $_SESSION['schema_ok'] = true;
            }
            $u = row1('SELECT Username, Role FROM users WHERE User_ID = ?', [$_SESSION['uid']]);
            if ($u) {
                $_SESSION['user'] = $u['Username'];
                $_SESSION['role'] = $u['Role'];
                $ok = true;
            } else {
                $_SESSION = [];            // account was deleted while signed in
            }
        }
    }
    return $ok;
}

function role(): string
{
    return (string) ($_SESSION['role'] ?? 'viewer');
}

function can_edit(): bool
{
    return in_array(role(), ['admin', 'editor'], true);
}

function is_admin(): bool
{
    return role() === 'admin';
}

function guard(): void
{
    if (!logged_in()) {
        header('Location: index.php');
        exit;
    }
}

function guard_admin(): void
{
    guard();
    if (!is_admin()) {
        flash('err', 'Only an administrator can open that page.');
        header('Location: dashboard.php');
        exit;
    }
}

function csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return (string) $_SESSION['csrf'];
}

function csrf_ok(): bool
{
    $s = (string) ($_SESSION['csrf'] ?? '');
    return $s !== '' && hash_equals($s, (string) ($_POST['csrf'] ?? ''));   // an empty token never matches
}

/** Record who changed what (shown in the read-only Audit Log table). */
function audit(string $action, string $table, string $key = '', string $details = ''): void
{
    q('INSERT INTO audit_log (User_Name, Action, Table_Name, Record_Key, Details) VALUES (?, ?, ?, ?, ?)',
      [(string) ($_SESSION['user'] ?? '?'), $action, $table, $key, $details === '' ? null : mb_substr($details, 0, 1500)]);
}

function pretty(string $t): string
{
    return ucwords(str_replace('_', ' ', $t));
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'] = [$type, $msg];
}

function page_start(string $title, string $active = ''): void
{
    $main = [];
    $rel = [];
    foreach (all_tables() as $t) {
        if (is_junction($t)) {
            $rel[] = $t;
        } else {
            $main[] = $t;
        }
    }
    $fl = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?> - Disaster &amp; Relief Management</title>
<link rel="icon" type="image/png" href="assets/logo.png">
<link rel="stylesheet" href="assets/style.css?v=<?= (int) filemtime(__DIR__ . '/../assets/style.css') ?>">
</head>
<body class="app">
<aside class="side" id="side">
  <a class="brand" href="dashboard.php"><img class="logo-img" src="assets/logo.png" width="40" height="40" alt=""><span>Disaster &amp; Relief<br><small>Management System</small></span></a>
  <nav>
    <a class="nav<?= $active === 'dashboard' ? ' on' : '' ?>" href="dashboard.php">Dashboard</a>
    <a class="nav<?= $active === 'disaster_report' ? ' on' : '' ?>" href="disaster.php">Disaster Report</a>
    <a class="nav<?= $active === 'map' ? ' on' : '' ?>" href="map.php">Map</a>
    <a class="nav<?= $active === 'report' ? ' on' : '' ?>" href="report.php">Live Report</a>
    <a class="nav<?= $active === 'weather' ? ' on' : '' ?>" href="weather.php">Live Weather</a>
    <a class="nav<?= $active === 'health' ? ' on' : '' ?>" href="health.php">Data Health</a>
    <?php if (is_admin()): ?><a class="nav<?= $active === 'users' ? ' on' : '' ?>" href="users.php">Users</a><?php endif; ?>
    <div class="nav-h">Main tables</div>
    <?php foreach ($main as $t): ?>
      <a class="nav<?= $active === $t ? ' on' : '' ?>" href="table.php?t=<?= h(urlencode($t)) ?>"><?= h(pretty($t)) ?></a>
    <?php endforeach; ?>
    <div class="nav-h">Relationship tables</div>
    <?php foreach ($rel as $t): ?>
      <a class="nav<?= $active === $t ? ' on' : '' ?>" href="table.php?t=<?= h(urlencode($t)) ?>"><?= h(pretty($t)) ?></a>
    <?php endforeach; ?>
  </nav>
</aside>
<div class="main">
  <header class="top">
    <button class="menu" id="menuBtn" type="button" aria-label="Menu">&#9776;</button>
    <h1><?= h($title) ?></h1>
    <form class="gsearch" method="get" action="search.php"><input type="search" name="q" placeholder="Search everything..." aria-label="Search all tables"></form>
    <div class="who">
      <a href="account.php" title="Change my password"><?= h($_SESSION['user'] ?? '') ?></a><span class="badge"><?= h(role()) ?></span>
      <form class="inline" method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><button class="btn btn-sm btn-ghost" type="submit">Sign out</button></form>
    </div>
  </header>
  <main class="content">
    <?php if ($fl): ?><div class="alert alert-<?= h($fl[0]) ?>"><?= h($fl[1]) ?></div><?php endif; ?>
<?php
}

function page_end(): void
{
    ?>
  </main>
</div>
<script src="assets/app.js"></script>
</body>
</html>
<?php
}
