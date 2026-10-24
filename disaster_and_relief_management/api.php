<?php
/** Search suggestions (dictionary style): api.php?t=table&col=column(optional)&q=typed text */
declare(strict_types=1);
define('DRMS_BACKGROUND_REQUEST', true);
require __DIR__ . '/inc/boot.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!logged_in()) {
    http_response_code(401);
    echo '[]';
    exit;
}
session_write_close();      // called on every keystroke: release the session lock so other requests are not queued behind it
$t  = (string) ($_GET['t'] ?? '');
$qs = trim((string) ($_GET['q'] ?? ''));
if ($qs === '' || !in_array($t, all_tables(), true)) {
    echo '[]';
    exit;
}
$names = array_column(table_cols($t), 'Field');
$col   = (string) ($_GET['col'] ?? '');
$use   = ($col !== '' && in_array($col, $names, true)) ? [$col] : $names;
$like  = addcslashes($qs, '%_\\');

$out  = [];
$seen = [];
foreach ([$like . '%', '%' . $like . '%'] as $pattern) {      // words that start with it first, then the rest
    foreach ($use as $c) {
        if (count($out) >= 8) {
            break 2;
        }
        $rs = rows("SELECT DISTINCT CAST(`$c` AS CHAR) AS v FROM `$t` WHERE CAST(`$c` AS CHAR) LIKE ? ORDER BY v LIMIT 8", [$pattern]);
        foreach ($rs as $r) {
            $v = mb_substr((string) $r['v'], 0, 80);
            $k = mb_strtolower($v);
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $out[] = ['v' => $v, 'c' => $c];
            if (count($out) >= 8) {
                break;
            }
        }
    }
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
