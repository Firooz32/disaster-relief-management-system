<?php
/** POST + CSRF token only, so another website cannot sign you out with a hidden image or link. */
declare(strict_types=1);
require __DIR__ . '/inc/boot.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $_SESSION = [];
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_destroy();
}
header('Location: index.php');
exit;
