<?php
/**
 * Router for PHP's built-in web server (start_server.bat). That server ignores .htaccess, so this file keeps the same
 * things private that .htaccess hides under Apache / WampServer: source folders, the SQL dump, notes and the settings file.
 * Usage: php -S 127.0.0.1:8099 -t <project folder> router.php
 */
declare(strict_types=1);

$path = rawurldecode((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH));
if (preg_match('#(^|/)(inc|tests)(/|$)#i', $path)
    || preg_match('#\.(sql|txt|md|log|bak|ini)$#i', $path)
    || preg_match('#(^|/)(config|config\.sample|router|db)\.php$#i', $path)) {
    http_response_code(404);
    echo 'Not found';
    return true;
}
return false;          // everything else is served normally
