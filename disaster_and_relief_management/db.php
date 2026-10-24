<?php
/**
 * Disaster Mapping & Relief Management System - database connection + helpers.
 * Paste this folder into WampServer's "www" folder and open http://localhost/disaster_and_relief_management/
 * On the first visit to index.php an empty database is filled from disaster_and_relief_management.sql.
 * Connection settings live in config.php.
 */
declare(strict_types=1);

const EXPECTED_TABLES = [
    'affected_person', 'affected_person_emergency_item', 'area', 'audit_log', 'damage', 'disaster',
    'disaster_affected_person', 'disaster_area', 'disaster_rescue_org', 'disaster_shelter', 'disaster_track',
    'early_warning', 'emergency_item', 'relief_distribution', 'relief_item', 'relief_recipient', 'rescue_org',
    'rescue_org_relief_item', 'shelter', 'shelter_assignment', 'shelter_emergency_item', 'users',
];
/** Tables added after the 20-table version: a database missing only these is upgraded in place (inc/upgrade.php). */
const UPGRADE_TABLES = ['disaster_track', 'relief_recipient'];

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function cfg(string $key): string|int
{
    static $c = null;
    $c ??= require __DIR__ . '/config.php';
    return $c[$key];
}

function connect(bool $withDb): mysqli
{
    $c = mysqli_init();
    $c->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
    $c->real_connect((string) cfg('host'), (string) cfg('user'), (string) cfg('pass'), $withDb ? (string) cfg('name') : null, (int) cfg('port'));
    $c->set_charset('utf8mb4');
    $c->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    return $c;
}

/** Shared connection to the application database. */
function db(): mysqli
{
    static $conn = null;
    return $conn ??= connect(true);
}

/** @return string[] lower-case table names of a connection's current database */
function tables_of(mysqli $c): array
{
    $have = [];
    $r = $c->query('SHOW TABLES');
    while ($row = $r->fetch_row()) {
        $have[] = strtolower((string) $row[0]);
    }
    return $have;
}

/**
 * Called by index.php and once per signed-in request. Creates the database and loads the SQL file when none of our tables exist,
 * and upgrades a database of the previous (20-table) version in place. Any other half-built database is never touched
 * (the SQL file starts with DROP TABLE), the user is told instead.
 */
function ensure_installed(): void
{
    try {
        $have = tables_of(db());
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() !== 1049) {          // 1049 = database does not exist yet
            throw $e;
        }
        $have = [];
    }
    $missing = array_diff(EXPECTED_TABLES, $have);
    if (!$missing) {
        return;
    }
    if (count($missing) < count(EXPECTED_TABLES) && !array_diff($missing, UPGRADE_TABLES)) {
        upgrade_previous_version();
        return;
    }
    if (count($missing) < count(EXPECTED_TABLES)) {
        throw new RuntimeException('The database is incomplete or from an older version (missing: ' . implode(', ', $missing)
            . '). To avoid overwriting your data nothing was changed. Back up what you need, drop the database in phpMyAdmin and reload this page.');
    }
    $file = __DIR__ . '/disaster_and_relief_management.sql';
    if (!is_file($file)) {
        throw new RuntimeException('disaster_and_relief_management.sql was not found next to db.php');
    }
    $c = connect(false);
    $c->query('CREATE DATABASE IF NOT EXISTS `' . cfg('name') . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $c->select_db((string) cfg('name'));
    $c->query("SELECT GET_LOCK('drms_install', 30)")->free();       // two first visitors must not import twice
    if (array_diff(EXPECTED_TABLES, tables_of($c))) {
        // the file names the default database; install into the configured one (config.php 'name') so another name never touches it
        run_sql_script($c, str_replace('`disaster_and_relief_management`', '`' . str_replace('`', '', (string) cfg('name')) . '`', (string) file_get_contents($file)));
    }
    $c->close();                                                    // closing releases the lock
}

/**
 * Runs a .sql file the way phpMyAdmin / the mysql client would: plain statements in one go, then everything after the first
 * DELIMITER line statement by statement with the delimiter that line sets (needed for CREATE TRIGGER bodies).
 */
function run_sql_script(mysqli $c, string $sql): void
{
    $sql = str_replace("\r\n", "\n", $sql);
    $cut = preg_match('/^DELIMITER[ \t]/m', $sql, $m, PREG_OFFSET_CAPTURE) ? $m[0][1] : strlen($sql);
    $plain = substr($sql, 0, $cut);
    if (trim($plain) !== '') {
        $c->multi_query($plain);
        do {
            if ($res = $c->store_result()) {
                $res->free();
            }
        } while ($c->more_results() && $c->next_result());
    }
    $delim = ';';
    $buf   = '';
    foreach (explode("\n", substr($sql, $cut)) as $line) {
        if (preg_match('/^DELIMITER[ \t]+(\S+)/', $line, $d)) {
            $delim = $d[1];
            continue;
        }
        if ($buf === '' && (trim($line) === '' || str_starts_with(ltrim($line), '--'))) {
            continue;                                               // blank line or comment between statements
        }
        $buf .= $line . "\n";
        if (str_ends_with(rtrim($buf), $delim)) {
            $c->query(substr(rtrim($buf), 0, -strlen($delim)));
            $buf = '';
        }
    }
}

/** Adds the new columns, tables and sample rows to a 20-table database. Needs ALTER / CREATE rights (e.g. root). */
function upgrade_previous_version(): void
{
    require_once __DIR__ . '/inc/upgrade.php';
    db()->query("SELECT GET_LOCK('drms_install', 30)")->free();     // two first visitors must not upgrade twice
    try {
        if (array_diff(EXPECTED_TABLES, tables_of(db()))) {
            upgrade_database();
        }
    } catch (mysqli_sql_exception $e) {
        if (in_array($e->getCode(), [1044, 1142], true)) {          // access denied for CREATE / ALTER
            throw new RuntimeException('The database needs a one-time upgrade, but the MySQL user in config.php may not change tables. '
                . 'Put root in config.php, open this page once, then switch back.');
        }
        throw $e;
    } finally {
        db()->query("SELECT RELEASE_LOCK('drms_install')")->free();
    }
}

/** Run a prepared statement. Returns mysqli_result for SELECTs, true otherwise. */
function q(string $sql, array $params = []): mysqli_result|bool
{
    $stmt = db()->prepare($sql);
    try {
        $params ? $stmt->execute(array_values($params)) : $stmt->execute();
        $res = $stmt->get_result();
    } finally {
        $stmt->close();
    }
    return $res === false ? true : $res;
}

function rows(string $sql, array $params = []): array
{
    $r = q($sql, $params);
    return $r instanceof mysqli_result ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

function row1(string $sql, array $params = []): ?array
{
    $r = q($sql, $params);
    if (!$r instanceof mysqli_result) {
        return null;
    }
    $row = $r->fetch_assoc();
    return $row ?: null;
}

/** First column of the first row as a number (0 when there is no row). */
function scalar(string $sql, array $params = []): float
{
    $r = row1($sql, $params);
    return $r ? (float) array_values($r)[0] : 0.0;
}
