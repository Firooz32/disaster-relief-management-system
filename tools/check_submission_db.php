<?php
/**
 * Loads submission/database/schema.sql + sample_data.sql into a SCRATCH database (FOREIGN_KEY_CHECKS on) and checks them:
 *     DRMS_DB_NAME=drms_sample_check php tools/check_submission_db.php
 * The scratch database is dropped and recreated; the real database name is refused.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    exit(1);
}
$app = __DIR__ . '/../disaster_and_relief_management';
require $app . '/inc/boot.php';
require $app . '/inc/stats.php';
$name = (string) cfg('name');
if ($name === 'disaster_and_relief_management' || !preg_match('/^drms_[a-z0-9_]+$/', $name)) {
    fwrite(STDERR, "Set DRMS_DB_NAME to a scratch name starting with drms_ (never the real database).\n");
    exit(1);
}

$fail = 0;
function check(string $label, bool $ok): void
{
    global $fail;
    echo ($ok ? '  ok   ' : '  FAIL ') . $label . "\n";
    $fail += $ok ? 0 : 1;
}

$dir = __DIR__ . '/../submission/database/';
$c = connect(false);
$c->query("DROP DATABASE IF EXISTS `$name`");
$c->query('SET FOREIGN_KEY_CHECKS = 1');
foreach (['schema.sql', 'sample_data.sql'] as $f) {
    $sql = str_replace('`disaster_and_relief_management`', "`$name`", (string) file_get_contents($dir . $f));
    try {
        run_sql_script($c, $sql);
        check("$f loads with foreign key checks on", true);
    } catch (mysqli_sql_exception $e) {
        check("$f loads with foreign key checks on: " . $e->getMessage(), false);
        exit(1);
    }
}
$c->close();

ensure_installed();                 // what the first sign-in does: must change nothing on a complete, current database
ensure_installed();
$tables = tables_of(db());
check('all ' . count(EXPECTED_TABLES) . ' tables exist', !array_diff(EXPECTED_TABLES, $tables) && count($tables) === count(EXPECTED_TABLES));
check('8 triggers exist', scalar('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()') === 8.0);
foreach (EXPECTED_TABLES as $t) {
    $n = (int) scalar("SELECT COUNT(*) FROM `$t`");
    check(sprintf('%-32s %d rows (3-5)', $t, $n), $n >= 3 && $n <= 5);
}
check('relief item totals equal their batch totals', scalar('SELECT COUNT(*) FROM relief_item i WHERE i.Quantity <>
    (SELECT COALESCE(SUM(b.Quantity_Received), 0) FROM relief_batch b WHERE b.Relief_ID = i.Relief_ID)') === 0.0);
check('no batch gives out more than it received', scalar('SELECT COUNT(*) FROM relief_batch b WHERE b.Quantity_Received <
    (SELECT COALESCE(SUM(d.Quantity), 0) FROM relief_distribution d WHERE d.Batch_ID = b.Batch_ID)') === 0.0);
check('no shelter is over capacity', scalar('SELECT COUNT(*) FROM shelter s WHERE s.Capacity < (SELECT COALESCE(SUM(p.Family_Size), 0)
    FROM shelter_assignment a JOIN affected_person p ON p.Person_ID = a.Person_ID WHERE a.Shelter_ID = s.Shelter_ID AND a.Check_Out IS NULL)') === 0.0);
check('distributing organisations supply the item they gave', scalar('SELECT COUNT(*) FROM relief_distribution d WHERE d.Org_ID IS NOT NULL
    AND NOT EXISTS (SELECT 1 FROM rescue_org_relief_item o WHERE o.Org_ID = d.Org_ID AND o.Relief_ID = d.Relief_ID)') === 0.0);
check('assigned tasks go to admins or editors', scalar("SELECT COUNT(*) FROM incident_task t JOIN users u ON u.User_ID = t.Assigned_To
    WHERE u.Role NOT IN ('admin', 'editor')") === 0.0);

// what the live panels show (same functions as the web pages)
$space = array_column(array_map(fn($s) => [$s['name'], $s['space']['cls']], shelter_capacity()), 1, 0);
check('Live Report: the school shelter is full (red) and Koyra has space (green)',
    ($space['Dacope Union Primary School Shelter'] ?? '') === 'err' && ($space['Koyra Government Shelter'] ?? '') === 'ok');
$sig = signal_summary()['disasters'];
check('Live Report: the active cyclone shows great danger signal 10 (red)', ($sig[0]['sig']['level'] ?? 0) === 10 && $sig[0]['sig']['cls'] === 'err');
$rel = relief_status()[0] ?? [];
$short = array_column(array_map(fn($a) => [$a['name'], $a['rel']['cls']], $rel['areas'] ?? []), 1, 0);
check('Live Report: Paikgachha is a relief shortage, Koyra and Dacope are on track',
    ($short['Paikgachha'] ?? '') === 'err' && ($short['Koyra'] ?? '') === 'ok' && ($short['Dacope'] ?? '') === 'ok');
$path = path_status()[0]['areas'] ?? [];
$state = array_column($path, 'state', 'name');
check('Path map: Paikgachha + Dacope now, Koyra passed, Sonadanga forecast',
    ($state['Paikgachha'] ?? '') === 'now' && ($state['Dacope'] ?? '') === 'now' && ($state['Koyra'] ?? '') === 'passed' && ($state['Sonadanga'] ?? '') === 'next');
check('one family has two stays in one disaster (moved shelters)', scalar('SELECT COUNT(*) FROM (SELECT Person_ID FROM shelter_assignment
    WHERE Disaster_ID = 1 GROUP BY Person_ID HAVING COUNT(*) = 2) t') === 1.0);

echo $fail ? "\n$fail check(s) failed.\n" : "\nAll submission database checks passed.\n";
exit($fail ? 1 : 0);
