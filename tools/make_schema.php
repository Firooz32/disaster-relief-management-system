<?php
/**
 * Writes submission/database/schema.sql from a freshly installed scratch database (never the real one):
 *     DRMS_DB_NAME=drms_submit_test php tools/make_schema.php
 * Tables are ordered parents-first so the script runs with FOREIGN_KEY_CHECKS on; the triggers come from inc/triggers.sql.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    exit(1);
}
$app = __DIR__ . '/../disaster_and_relief_management';
require $app . '/db.php';
if (cfg('name') === 'disaster_and_relief_management') {
    fwrite(STDERR, "Refusing to read the real database; set DRMS_DB_NAME to a scratch copy.\n");
    exit(1);
}

$deps = [];
foreach (tables_of(db()) as $t) {
    $deps[$t] = [];
}
foreach (rows('SELECT TABLE_NAME AS t, REFERENCED_TABLE_NAME AS r FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL') as $fk) {
    if ($fk['t'] !== $fk['r']) {
        $deps[strtolower($fk['t'])][] = strtolower($fk['r']);
    }
}
$order = [];
while (count($order) < count($deps)) {                       // Kahn-style: emit every table whose parents are already out
    $before = count($order);
    foreach ($deps as $t => $parents) {
        if (!in_array($t, $order, true) && !array_diff($parents, $order)) {
            $order[] = $t;
        }
    }
    if (count($order) === $before) {
        throw new RuntimeException('Foreign key cycle among: ' . implode(', ', array_diff(array_keys($deps), $order)));
    }
}

$out = "-- Disaster Mapping & Relief Management System - schema (DDL only)\n"
     . "-- 25 tables with primary keys, foreign keys (ON DELETE / ON UPDATE rules), UNIQUE keys, indexes and CHECK constraints,\n"
     . "-- followed by 8 integrity triggers. Tables are created parents-first, so foreign key checks stay ON.\n"
     . "-- Requires MySQL 8.0.16+ (CHECK constraints, functional index). Load sample_data.sql afterwards.\n\n"
     . "CREATE DATABASE IF NOT EXISTS `disaster_and_relief_management` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n"
     . "USE `disaster_and_relief_management`;\n\n";
foreach ($order as $i => $t) {
    $ddl = (string) db()->query("SHOW CREATE TABLE `$t`")->fetch_row()[1];
    $ddl = preg_replace('/ AUTO_INCREMENT=\d+/', '', $ddl);
    $ddl = preg_replace('/^CREATE TABLE/', 'CREATE TABLE IF NOT EXISTS', $ddl);
    $out .= '-- ' . ($i + 1) . '. ' . $t . ($deps[$t] ? ' (references: ' . implode(', ', array_unique($deps[$t])) . ')' : '') . "\n$ddl;\n\n";
}
$out .= "-- ---------------------------------------------------------------- triggers\n"
      . (string) file_get_contents($app . '/inc/triggers.sql');

$dest = __DIR__ . '/../submission/database/schema.sql';
@mkdir(dirname($dest), 0777, true);
file_put_contents($dest, str_replace("\r\n", "\n", $out));
echo 'Wrote ' . realpath($dest) . ' (' . count($order) . " tables)\n" . implode(' -> ', $order) . "\n";
