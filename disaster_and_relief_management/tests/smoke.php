<?php
/**
 * Smoke test for the database and the security helpers. Run from the project folder (MySQL must be running):
 *     php tests/smoke.php
 * Writes nothing permanent: the one INSERT test runs inside a transaction that is rolled back.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {       // never runnable from the browser
    http_response_code(404);
    exit;
}
require __DIR__ . '/../inc/boot.php';
require __DIR__ . '/../inc/rules.php';
require __DIR__ . '/../inc/stats.php';
require __DIR__ . '/../inc/health.php';
require __DIR__ . '/../inc/upgrade.php';

$fail = 0;
function check(string $name, bool $ok): void
{
    global $fail;
    echo ($ok ? "  ok   " : "  FAIL ") . $name . "\n";
    $fail += $ok ? 0 : 1;
}

ensure_installed();

// installation
check('all ' . count(EXPECTED_TABLES) . ' tables exist', !array_diff(EXPECTED_TABLES, tables_of(db())));
check('incident response-task table is installed and searchable', in_array('incident_task', all_tables(), true)
    && (fk_map('incident_task')['Assigned_To'] ?? null) === ['users', 'User_ID']);
check('field updates are incident-linked, attributed, and read-only in generic forms',
    in_array('incident_update', all_tables(), true)
    && (fk_map('incident_update')['Disaster_ID'] ?? null) === ['disaster', 'Disaster_ID']
    && (fk_map('incident_update')['Area_ID'] ?? null) === ['area', 'Area_ID']
    && in_array('incident_update', READONLY_TABLES, true));
check('relief batches and distribution batch links are installed',
    in_array('relief_batch', all_tables(), true)
    && (fk_map('relief_batch')['Relief_ID'] ?? null) === ['relief_item', 'Relief_ID']
    && (fk_map('relief_distribution')['Batch_ID'] ?? null) === ['relief_batch', 'Batch_ID']);
check('batch dropdowns identify the relief item and remaining quantity',
    str_contains((string) (fk_options('relief_batch', 'Batch_ID')[(string) scalar('SELECT Batch_ID FROM relief_batch ORDER BY Batch_ID LIMIT 1')] ?? ''), 'available'));
check('shelter readiness details and verifier metadata are installed',
    in_array('Services', array_column(table_cols('shelter'), 'Field'), true)
    && in_array('Accessibility_Notes', array_column(table_cols('shelter'), 'Field'), true)
    && (fk_map('shelter')['Capacity_Verified_By'] ?? null) === ['users', 'User_ID']);
check('users table is hidden from the generic pages', !in_array('users', all_tables(), true));
check('audit_log is listed', in_array('audit_log', all_tables(), true));
check('foreign key map is read (shelter.Area_ID -> area)', (fk_map('shelter')['Area_ID'] ?? null) === ['area', 'Area_ID']);
check('incident schema has four statuses and optional user lead', str_contains(column_type('disaster', 'Status'), "'Reported'")
    && str_contains(column_type('disaster', 'Status'), "'Under control'")
    && !str_contains(column_type('disaster', 'Status'), "'Ended'")
    && (fk_map('disaster')['Incident_Lead_ID'] ?? null) === ['users', 'User_ID']);
check('junction tables are detected', is_junction('disaster_area') && !is_junction('shelter'));
check('relief_recipient is a junction, disaster_track is not', is_junction('relief_recipient') && !is_junction('disaster_track'));

// CSRF: an empty token must never match an empty session token
$_SESSION['csrf'] = '';
$_POST['csrf'] = '';
check('csrf_ok() rejects empty == empty', !csrf_ok());
$_SESSION['csrf'] = 'abc';
$_POST['csrf'] = 'abc';
check('csrf_ok() accepts the right token', csrf_ok());
$_POST['csrf'] = 'abd';
check('csrf_ok() rejects a wrong token', !csrf_ok());

// CHECK constraints (error 3819) - rolled back even if something unexpectedly succeeds
db()->begin_transaction();
db()->query('INSERT INTO disaster_affected_person (Disaster_ID, Person_ID) VALUES (5, 1)');       // so the trigger lets the date test through
foreach ([
    'negative shelter capacity' => "INSERT INTO shelter (Shelter_Name, Location, Capacity, Area_ID) VALUES ('t1', 'x', -5, 1)",
    'zero family size'          => "INSERT INTO affected_person (Name, Family_Size) VALUES ('t2', 0)",
    'negative relief stock'     => "INSERT INTO relief_item (Item_Name, Quantity) VALUES ('t3', -1)",
    'negative relief batch receipt' => "INSERT INTO relief_batch (Relief_ID, Batch_Code, Quantity_Received) VALUES (1, 'smoke-negative-batch', -1)",
    'batch expiry before receipt' => "INSERT INTO relief_batch (Relief_ID, Batch_Code, Quantity_Received, Received_At, Expiry_Date) VALUES (1, 'smoke-invalid-dates', 1, '2026-01-02', '2026-01-01')",
    'latitude out of range'     => "INSERT INTO area (Area_Name, Location, Latitude) VALUES ('t4', 'x', 123)",
    'check-out before check-in' => "INSERT INTO shelter_assignment (Disaster_ID, Person_ID, Shelter_ID, Check_In, Check_Out) VALUES (5, 1, 1, '2026-01-10', '2026-01-01')",
    'warning signal 11'         => "INSERT INTO early_warning (Disaster_ID, Alert_Type, Signal_Level, Alert_DateTime) VALUES (1, 't5', 11, '2026-01-01 00:00:00')",
    'warning signal 0'          => "INSERT INTO early_warning (Disaster_ID, Alert_Type, Signal_Level, Alert_DateTime) VALUES (1, 't6', 0, '2026-01-01 00:00:00')",
    'track latitude out of range' => "INSERT INTO disaster_track (Disaster_ID, Point_Time, Latitude, Longitude) VALUES (1, '2026-01-01 00:00:00', 95, 89)",
    'track radius of 0 km'      => "INSERT INTO disaster_track (Disaster_ID, Point_Time, Latitude, Longitude, Radius_Km) VALUES (1, '2026-01-01 00:00:00', 22, 89, 0)",
] as $label => $sql) {
    $code = 0;
    try {
        db()->query($sql);
    } catch (mysqli_sql_exception $e) {
        $code = $e->getCode();
    }
    check("rejected: $label", $code === 3819);
}
db()->rollback();

// integrity triggers (error 1644 = our own message), also rolled back
db()->begin_transaction();
foreach ([
    'assignment: shelter not used for the disaster' => "INSERT INTO shelter_assignment (Disaster_ID, Person_ID, Shelter_ID, Check_In) VALUES (1, 1, 11, '2026-01-01')",
    'assignment: person not affected by the disaster' => "INSERT INTO shelter_assignment (Disaster_ID, Person_ID, Shelter_ID, Check_In) VALUES (5, 3, 1, '2026-01-01')",
    'distribution: shelter not used for the disaster' => "INSERT INTO relief_distribution (Disaster_ID, Relief_ID, Shelter_ID, Quantity, Distribution_Date) VALUES (1, 1, 11, 1, '2026-01-01')",
    'distribution: organisation not part of the disaster' => "INSERT INTO relief_distribution (Disaster_ID, Relief_ID, Shelter_ID, Org_ID, Quantity, Distribution_Date) VALUES (1, 1, 1, 12, 1, '2026-01-01')",
    'recipient: person not affected by the disaster' => "INSERT INTO relief_recipient (Distribution_ID, Person_ID) VALUES (57, 1)",
    'damage: area not affected by the disaster' => "INSERT INTO damage (Disaster_ID, Damage_Type, Area_ID) VALUES (1, 'x', 20)",
    'assignment update: moved to a shelter outside the disaster' => "UPDATE shelter_assignment SET Shelter_ID = 11 WHERE Assignment_ID = 1",
] as $label => $sql) {
    $code = 0;
    try {
        db()->query($sql);
    } catch (mysqli_sql_exception $e) {
        $code = $e->getCode();
    }
    check("trigger refuses: $label", $code === 1644);
}
db()->rollback();
$tn = scalar('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()');
check('triggers are installed (8; a limited MySQL user cannot list them, the refusals above prove they fire)', $tn === 8.0 || $tn === 0.0);

// data health checks
$hc = health_checks();
check('health: the sample data has no errors or missing-information problems', health_problem_count($hc) === 0);
db()->begin_transaction();
db()->query('DELETE FROM disaster_shelter WHERE Disaster_ID = 13 AND Shelter_ID = 3');              // leaves stays that point at an unlinked shelter
$found = array_column(health_checks(), 'count', 'id');
db()->rollback();
check('health: a deleted disaster-shelter link is detected', ($found['assign_shelter'] ?? 0) > 0 && ($found['dist_shelter'] ?? 0) > 0);

// permissions and error texts
$_SESSION['role'] = 'editor';
check('editor may delete normal records but not disasters', can_delete('shelter') && !can_delete('disaster'));
$_SESSION['role'] = 'admin';
check('admin may delete disasters', can_delete('disaster'));
$_SESSION['role'] = 'viewer';
check('viewer may delete nothing', !can_delete('shelter'));
check('stock batches cannot be edited or deleted through generic table forms', in_array('relief_batch', READONLY_TABLES, true));
unset($_SESSION['role']);
db()->begin_transaction();
$_SESSION['user'] = 'smoke-test';
audit('verify', 'shelter', 'Shelter_ID=11', 'Reviewed and confirmed current.');
$verified = row1("SELECT Action, Details FROM audit_log WHERE Table_Name = 'shelter' AND Record_Key = 'Shelter_ID=11' ORDER BY Log_ID DESC LIMIT 1");
db()->rollback();
check('record verification is written to the audit log', ($verified['Action'] ?? '') === 'verify' && ($verified['Details'] ?? '') === 'Reviewed and confirmed current.');

// seed data consistency
check('every shelter assignment uses a shelter linked to that disaster',
    scalar('SELECT COUNT(*) FROM shelter_assignment sa LEFT JOIN disaster_shelter ds ON ds.Disaster_ID = sa.Disaster_ID AND ds.Shelter_ID = sa.Shelter_ID WHERE ds.Shelter_ID IS NULL') === 0.0);
check('every assigned person belongs to that disaster',
    scalar('SELECT COUNT(*) FROM shelter_assignment sa LEFT JOIN disaster_affected_person dp ON dp.Disaster_ID = sa.Disaster_ID AND dp.Person_ID = sa.Person_ID WHERE dp.Person_ID IS NULL') === 0.0);
check('no relief item is given out more than was received',
    scalar('SELECT COUNT(*) FROM relief_item r WHERE r.Quantity < (SELECT COALESCE(SUM(Quantity),0) FROM relief_distribution d WHERE d.Relief_ID = r.Relief_ID)') === 0.0);
check('no shelter is over capacity',
    scalar('SELECT COUNT(*) FROM shelter s WHERE s.Capacity < (SELECT COALESCE(SUM(p.Family_Size),0) FROM shelter_assignment sa JOIN affected_person p ON p.Person_ID = sa.Person_ID WHERE sa.Shelter_ID = s.Shelter_ID AND sa.Check_Out IS NULL)') === 0.0);

check('every distribution uses a shelter and an organisation of that disaster',
    scalar('SELECT COUNT(*) FROM relief_distribution d
             WHERE NOT EXISTS (SELECT 1 FROM disaster_shelter x WHERE x.Disaster_ID = d.Disaster_ID AND x.Shelter_ID = d.Shelter_ID)
                OR (d.Org_ID IS NOT NULL AND NOT EXISTS (SELECT 1 FROM disaster_rescue_org x WHERE x.Disaster_ID = d.Disaster_ID AND x.Org_ID = d.Org_ID))') === 0.0);
check('every organisation only gives out items it supplies',
    scalar('SELECT COUNT(*) FROM relief_distribution d WHERE d.Org_ID IS NOT NULL AND NOT EXISTS (SELECT 1 FROM rescue_org_relief_item x WHERE x.Org_ID = d.Org_ID AND x.Relief_ID = d.Relief_ID)') === 0.0);
check('nobody is checked in to two shelters at once',
    scalar('SELECT COUNT(*) FROM (SELECT Person_ID FROM shelter_assignment WHERE Check_Out IS NULL GROUP BY Person_ID HAVING COUNT(*) > 1) t') === 0.0);
check('every damage area belongs to its disaster',
    scalar('SELECT COUNT(*) FROM damage m WHERE m.Area_ID IS NOT NULL AND NOT EXISTS (SELECT 1 FROM disaster_area x WHERE x.Disaster_ID = m.Disaster_ID AND x.Area_ID = m.Area_ID)') === 0.0);

check('every relief recipient is affected by the disaster of that distribution',
    scalar('SELECT COUNT(*) FROM relief_recipient r JOIN relief_distribution d ON d.Distribution_ID = r.Distribution_ID
             WHERE NOT EXISTS (SELECT 1 FROM disaster_affected_person x WHERE x.Disaster_ID = d.Disaster_ID AND x.Person_ID = r.Person_ID)') === 0.0);
check('every dispatch is linked to a batch for the same relief item',
    scalar('SELECT COUNT(*) FROM relief_distribution d LEFT JOIN relief_batch b ON b.Batch_ID = d.Batch_ID
             WHERE b.Batch_ID IS NULL OR b.Relief_ID <> d.Relief_ID') === 0.0);
check('batch receipts and dispatches reconcile with item stock',
    scalar('SELECT COUNT(*) FROM relief_item i
             WHERE i.Quantity <> (SELECT COALESCE(SUM(b.Quantity_Received), 0) FROM relief_batch b WHERE b.Relief_ID = i.Relief_ID)
                OR i.Quantity - (SELECT COALESCE(SUM(d.Quantity), 0) FROM relief_distribution d WHERE d.Relief_ID = i.Relief_ID)
                   <> (SELECT COALESCE(SUM(b.Quantity_Received - (SELECT COALESCE(SUM(x.Quantity), 0) FROM relief_distribution x WHERE x.Batch_ID = b.Batch_ID)), 0)
                         FROM relief_batch b WHERE b.Relief_ID = i.Relief_ID)') === 0.0);
check('every family of an active disaster has a home area',
    scalar("SELECT COUNT(*) FROM disaster_affected_person x JOIN disaster d ON d.Disaster_ID = x.Disaster_ID JOIN affected_person p ON p.Person_ID = x.Person_ID
             WHERE d.Status IN ('Active','Under control') AND p.Area_ID IS NULL") === 0.0);
check('every active disaster has a warning with a signal level',
    scalar("SELECT COUNT(*) FROM disaster d WHERE d.Status IN ('Active','Under control')
             AND NOT EXISTS (SELECT 1 FROM early_warning w WHERE w.Disaster_ID = d.Disaster_ID AND w.Signal_Level IS NOT NULL)") === 0.0);
check('the live cyclone has observed and forecast path points',
    scalar("SELECT COUNT(DISTINCT t.Point_Type) FROM disaster_track t JOIN disaster d ON d.Disaster_ID = t.Disaster_ID WHERE d.Status IN ('Active','Under control')") === 2.0);
check('sample data has a shelter that is exactly full ("No space available")',
    scalar('SELECT COUNT(*) FROM shelter s WHERE s.Capacity = (SELECT COALESCE(SUM(p.Family_Size),0) FROM shelter_assignment sa JOIN affected_person p ON p.Person_ID = sa.Person_ID WHERE sa.Shelter_ID = s.Shelter_ID AND sa.Check_Out IS NULL)') >= 1.0);
check('sample data has an area flagged with a relief shortage', scalar("SELECT COUNT(*) FROM disaster_area WHERE Relief_Shortage = 'Yes'") >= 1.0);

// business rules (inc/rules.php)
$first = row1('SELECT Assignment_ID, Disaster_ID, Person_ID, Shelter_ID FROM shelter_assignment WHERE Check_Out IS NULL LIMIT 1');
check('rule: a person already staying in a shelter cannot be checked in again',
    str_contains((string) check_rules('shelter_assignment', ['Disaster_ID' => $first['Disaster_ID'], 'Person_ID' => $first['Person_ID'], 'Shelter_ID' => $first['Shelter_ID'], 'Check_Out' => ''], null), 'already staying'));
check('rule: editing that same record is allowed',
    check_rules('shelter_assignment', ['Disaster_ID' => $first['Disaster_ID'], 'Person_ID' => $first['Person_ID'], 'Shelter_ID' => $first['Shelter_ID'], 'Check_Out' => ''], ['Assignment_ID' => (string) $first['Assignment_ID']]) === null);
check('rule: a shelter that is not part of the disaster is refused',
    str_contains((string) check_rules('shelter_assignment', ['Disaster_ID' => 1, 'Person_ID' => 1, 'Shelter_ID' => 11, 'Check_Out' => '2026-01-01'], null), 'not used for this disaster'));
$dd = row1('SELECT d.Disaster_ID, d.Relief_ID, d.Batch_ID, d.Shelter_ID, d.Org_ID FROM relief_distribution d LIMIT 1');
check('rule: giving out more than the stock is refused',
    str_contains((string) check_rules('relief_distribution', $dd + ['Quantity' => 999999], null), 'Not enough stock'));
check('rule: a normal distribution passes', check_rules('relief_distribution', $dd + ['Quantity' => 1], null) === null);
$wrongBatch = row1('SELECT Batch_ID FROM relief_batch WHERE Relief_ID <> ? ORDER BY Batch_ID LIMIT 1', [$dd['Relief_ID']]);
check('rule: a dispatch cannot use a batch for another relief item',
    $wrongBatch !== null && str_contains((string) check_rules('relief_distribution', array_merge($dd, ['Batch_ID' => $wrongBatch['Batch_ID'], 'Quantity' => 1]), null), 'Choose a stock batch'));
check('incident workflow: new incidents start reported and can be activated',
    check_rules('disaster', ['Status' => 'Reported'], null) === null
    && check_rules('disaster', ['Status' => 'Active'], null) !== null
    && check_rules('disaster', ['Status' => 'Active'], ['Disaster_ID' => '13']) === null);
check('incident workflow: active incidents can go under control or close, not return to reported',
    check_rules('disaster', ['Status' => 'Under control'], ['Disaster_ID' => '13']) === null
    && check_rules('disaster', ['Status' => 'Closed'], ['Disaster_ID' => '13']) === null
    && check_rules('disaster', ['Status' => 'Reported'], ['Disaster_ID' => '13']) !== null);
$_SESSION['role'] = 'editor';
check('incident workflow: editors cannot reopen closed incidents',
    check_rules('disaster', ['Status' => 'Active'], ['Disaster_ID' => '1']) !== null);
$_SESSION['role'] = 'admin';
check('incident workflow: administrators can reopen closed incidents',
    check_rules('disaster', ['Status' => 'Active'], ['Disaster_ID' => '1']) === null);
unset($_SESSION['role']);
$_SESSION['role'] = 'editor';
$viewerId = (int) scalar("SELECT User_ID FROM users WHERE Role = 'viewer' ORDER BY User_ID LIMIT 1");
check('response tasks cannot be assigned to read-only viewers',
    $viewerId === 0 || check_rules('incident_task', ['Assigned_To' => (string) $viewerId], null) !== null);
unset($_SESSION['role']);
check('field update validation accepts an affected area and rejects unrelated areas',
    field_update_rules(13, ['Update_Type' => 'Situation', 'Summary' => 'Road reopened', 'Details' => 'Access confirmed by field team.',
        'Observed_At' => date('Y-m-d\TH:i'), 'Area_ID' => '3']) === null
    && field_update_rules(13, ['Update_Type' => 'Situation', 'Summary' => 'Road reopened', 'Details' => 'Access confirmed by field team.',
        'Observed_At' => date('Y-m-d\TH:i'), 'Area_ID' => '4']) !== null);
check('field update validation rejects empty summaries, invalid categories, and malformed times',
    field_update_rules(13, ['Update_Type' => 'Other', 'Summary' => '', 'Details' => 'Details', 'Observed_At' => 'bad', 'Area_ID' => '']) !== null);
$fieldReporterId = (int) scalar('SELECT User_ID FROM users ORDER BY User_ID LIMIT 1');
if ($fieldReporterId > 0) {
    db()->begin_transaction();
    q("INSERT INTO incident_update (Disaster_ID, Area_ID, Update_Type, Summary, Details, Observed_At, Reported_By)
       VALUES (13, 3, 'Situation', 'Smoke field update', 'Temporary test observation.', CURRENT_TIMESTAMP, ?)", [$fieldReporterId]);
    $fieldUpdateId = (int) db()->insert_id;
    audit('add', 'incident_update', 'Update_ID=' . $fieldUpdateId, 'Situation: Smoke field update');
    $fieldUpdate = row1('SELECT Summary, Area_ID, Reported_By FROM incident_update WHERE Update_ID = ?', [$fieldUpdateId]);
    $fieldAudit = row1("SELECT Action FROM audit_log WHERE Table_Name = 'incident_update' AND Record_Key = ? ORDER BY Log_ID DESC LIMIT 1",
        ['Update_ID=' . $fieldUpdateId]);
    db()->rollback();
    check('field observations are attributed and audit-logged in one transaction',
        ($fieldUpdate['Summary'] ?? '') === 'Smoke field update' && (int) ($fieldUpdate['Area_ID'] ?? 0) === 3
        && (int) ($fieldUpdate['Reported_By'] ?? 0) === $fieldReporterId && ($fieldAudit['Action'] ?? '') === 'add');
}
db()->begin_transaction();
q("UPDATE disaster SET Status = 'Under control' WHERE Disaster_ID = 13");
$controlledSignals = signal_summary()['disasters'];
db()->rollback();
check('live signal summary includes incidents under control', (bool) array_filter($controlledSignals, fn($d) => $d['id'] === 13));
// warning signal indicator
$exp = [1 => 'Normal/ok', 2 => 'Normal/ok', 3 => 'Normal/ok', 4 => 'Danger/warn', 5 => 'Danger/warn', 6 => 'Danger/warn', 7 => 'Danger/warn',
        8 => 'Severe Danger/err', 9 => 'Severe Danger/err', 10 => 'Severe Danger/err'];
$bad = [];
foreach ($exp as $lv => $want) {
    $i = signal_info($lv);
    if ($i['label'] . '/' . $i['cls'] !== $want || $i['level'] !== $lv) $bad[] = $lv;
}
check('signals 1-10 map to Normal green / Danger orange / Severe Danger red', !$bad);
check('no signal, 0 and 11 show "No signal"', signal_info(null)['cls'] === 'none' && signal_info(0)['label'] === 'No signal' && signal_info(11)['label'] === 'No signal');
$sg = signal_summary();
$cy = current(array_filter($sg['disasters'], fn($d) => $d['type'] === 'Cyclone' && $d['sig']['level'] === 10));
check('active cyclone shows its latest signal (10, Severe Danger)', $cy && $cy['sig']['cls'] === 'err');
$byName = array_column($sg['areas'], null, 'name');
check('an area takes the highest signal of the active disasters hitting it (Koyra = 10)', ($byName['Koyra']['sig']['level'] ?? 0) === 10);
check('an area no active disaster touches has no signal (Mollahat)', $byName['Mollahat']['sig']['cls'] === 'none' && !$byName['Mollahat']['hit']);
check('ended disasters are not in the signal list', !array_filter($sg['disasters'], fn($d) => $d['id'] === 1));
// shelter space indicator
check('space: places left > 0 is green with the exact number', space_info(10, 9) === ['left' => 1, 'cls' => 'ok', 'label' => '1 place left'] && space_info(500, 21)['label'] === '479 places left');
check('space: 0 left is red "No space available"', space_info(10, 10) === ['left' => 0, 'cls' => 'err', 'label' => 'No space available']);
check('space: an over-full shelter is red too, never a negative number', space_info(10, 12)['left'] === 0 && space_info(10, 12)['cls'] === 'err');
$space = array_column(shelter_capacity(), null, 'name');
check('sample: the 16-place school shelter is full (red)', ($space['Dacope Union Primary School Shelter']['space']['cls'] ?? '') === 'err');
check('sample: Terokhada shelter has exactly 2 places left (green)', ($space['Terokhada Primary School Shelter']['space']['left'] ?? 0) === 2);
check('space: left + occupied = capacity whenever a shelter is not over-full',
    !array_filter(shelter_capacity(), fn($s) => $s['occupied'] <= $s['capacity'] && $s['space']['left'] + $s['occupied'] !== $s['capacity']));
[$shelterSearch, $shelterSearchParams] = table_search_condition('shelter_assignment', 'Terokhada Primary School Shelter', 'Shelter_ID');
$shelterSearchCount = (int) scalar("SELECT COUNT(*) FROM shelter_assignment WHERE $shelterSearch", $shelterSearchParams);
$allColumnSearch = table_search_condition('shelter_assignment', 'Terokhada Primary School Shelter');
$allColumnSearchCount = (int) scalar("SELECT COUNT(*) FROM shelter_assignment WHERE {$allColumnSearch[0]}", $allColumnSearch[1]);
$shelterAssignmentCount = (int) scalar("SELECT COUNT(*) FROM shelter_assignment WHERE Shelter_ID = (SELECT Shelter_ID FROM shelter WHERE Shelter_Name = ?)",
    ['Terokhada Primary School Shelter']);
check('table and global search find shelter assignments by their referenced shelter name',
    $shelterSearchCount === $shelterAssignmentCount && $allColumnSearchCount === $shelterAssignmentCount && $shelterAssignmentCount > 0);
// live disaster path
check('distance: 0.1 degree of longitude at 22.5N is about 10.3 km, same point is 0', abs(km_between(22.5, 89.4, 22.5, 89.5) - 10.27) < 0.1 && km_between(22.5, 89.4, 22.5, 89.4) < 0.001);
$ps = path_status();
check('path: only active disasters with track points are listed (the cyclone)', count($ps) === 1 && $ps[0]['type'] === 'Cyclone');
$pa = array_column($ps[0]['areas'] ?? [], 'state', 'name');
$cp = $ps[0]['points'][$ps[0]['current']] ?? null;
check('path: the current position is the latest Actual point, not a forecast', $cp !== null && $cp['type'] === 'Actual' && $cp['time'] === '2026-10-24 12:00:00');
check('path: areas inside the current radius are "now" (Dacope, Paikgachha, Mongla)', ($pa['Dacope'] ?? '') === 'now' && ($pa['Paikgachha'] ?? '') === 'now' && ($pa['Mongla'] ?? '') === 'now');
check('path: an area reached only by the forecast is "next" (Dumuria)', ($pa['Dumuria'] ?? '') === 'next');
check('path: an area only an earlier observed point covered is "passed" (Koyra)', ($pa['Koyra'] ?? '') === 'passed');
check('path: an area outside every radius is "none" (Mollahat)', ($pa['Mollahat'] ?? '') === 'none');
check('path: no area is "now" unless it is within the current radius',
    !array_filter($ps[0]['areas'] ?? [], fn($a) => $a['state'] === 'now' && $a['km'] > $cp['radius']));

// relief tracker
check('relief: 80% covered is on track (green), 79% is a shortage (red)', relief_info(100, 80, false)['cls'] === 'ok' && relief_info(100, 79, false)['cls'] === 'err');
check('relief: a coordinator flag forces a shortage even at 100%', relief_info(10, 10, true)['cls'] === 'err' && relief_info(10, 10, true)['label'] === 'Shortage');
check('relief: nobody recorded and no flag is grey, not green', relief_info(0, 0, false)['cls'] === 'none');
$rs = relief_status();
$cy = current(array_filter($rs, fn($x) => $x['type'] === 'Cyclone'));
$ar = array_column($cy['areas'] ?? [], null, 'name');
check('sample: Koyra fully covered is green', ($ar['Koyra']['rel']['cls'] ?? '') === 'ok' && $ar['Koyra']['not_received'] === 0);
check('sample: Dacope (automatic) is a shortage with received and not-received counts', ($ar['Dacope']['rel']['cls'] ?? '') === 'err' && $ar['Dacope']['received'] === 8 && $ar['Dacope']['not_received'] === 11);
check('sample: Paikgachha is a shortage only because of the manual flag', ($ar['Paikgachha']['rel']['cls'] ?? '') === 'err' && $ar['Paikgachha']['not_received'] === 0 && $ar['Paikgachha']['flagged']);
check('sample: ended disasters are not in the relief tracker', !array_filter($rs, fn($x) => $x['id'] === 1));
check('relief: area totals add up to the disaster total', !array_filter($rs, fn($x) => array_sum(array_column($x['areas'], 'people')) !== $x['people'] || $x['received'] + $x['not_received'] !== $x['people']));
check('rule: a relief recipient must be affected by the distribution\'s disaster',
    str_contains((string) check_rules('relief_recipient', ['Distribution_ID' => 57, 'Person_ID' => 1], null), 'not recorded as affected') && check_rules('relief_recipient', ['Distribution_ID' => 57, 'Person_ID' => 30], null) === null);
check('rule: a distribution cannot move to a disaster its recipients are not part of',
    str_contains((string) check_rules('relief_distribution', ['Disaster_ID' => 1, 'Relief_ID' => 1, 'Shelter_ID' => 1, 'Org_ID' => '', 'Quantity' => 1], ['Distribution_ID' => '57']), 'not affected by that disaster'));
check('alerts() runs and reports the almost-full shelter', count(array_filter(alerts(), fn($a) => $a[0] === 'err')) >= 1);
$severeAlert = current(array_filter(alerts(), fn($a) => ($a[3] ?? '') === 'severe_signal:13'));
check('severe warning alerts target the active cyclone and link to its warnings',
    $severeAlert && str_contains($severeAlert[1], 'signal 10')
    && str_contains($severeAlert[2], 't=early_warning'));
$latestSignalId = (int) scalar('SELECT Alert_ID FROM early_warning WHERE Disaster_ID = 13 AND Signal_Level IS NOT NULL ORDER BY Alert_DateTime DESC, Alert_ID DESC LIMIT 1');
if ($latestSignalId > 0) {
    db()->begin_transaction();
    q('UPDATE early_warning SET Signal_Level = 7 WHERE Alert_ID = ?', [$latestSignalId]);
    $severeAlertCleared = !(bool) array_filter(alerts(), fn($a) => ($a[3] ?? '') === 'severe_signal:13');
    db()->rollback();
    check('severe warning alert clears when the latest signal drops below 8', $severeAlertCleared);
}
$terokhadaAlert = current(array_filter(alerts(), fn($a) => str_starts_with($a[1], 'Terokhada Primary School Shelter')));
$alertParams = [];
parse_str((string) parse_url($terokhadaAlert[2] ?? '', PHP_URL_QUERY), $alertParams);
check('Terokhada shelter alert opens its filtered assignment records',
    ($alertParams['t'] ?? '') === 'shelter_assignment' && ($alertParams['col'] ?? '') === 'Shelter_ID'
    && ($alertParams['q'] ?? '') === 'Terokhada Primary School Shelter');
check('generated alert keys are present and unique', count(array_filter(alerts(), fn($a) => isset($a[3]) && is_string($a[3]))) === count(alerts())
    && count(array_unique(array_column(alerts(), 3))) === count(alerts()));
$batchRows = stock_batches();
$movementRows = stock_movements();
check('stock batch and movement summaries include legacy stock and its dispatches',
    count($batchRows) > 0 && count($movementRows) >= count(rows('SELECT Distribution_ID FROM relief_distribution')));
$legacyItem = (int) scalar('SELECT Relief_ID FROM relief_item ORDER BY Relief_ID LIMIT 1');
$receiptItem = row1('SELECT Relief_ID, Quantity FROM relief_item ORDER BY Relief_ID LIMIT 1');
if ($receiptItem !== null) {
    $receiptCode = 'smoke-receipt-' . bin2hex(random_bytes(8));
    db()->begin_transaction();
    q("INSERT INTO relief_batch (Relief_ID, Batch_Code, Quantity_Received, Received_At, Source)
       VALUES (?, ?, 3, CURRENT_DATE, 'Smoke test')", [(int) $receiptItem['Relief_ID'], $receiptCode]);
    $receiptBatchId = (int) db()->insert_id;
    q('UPDATE relief_item SET Quantity = Quantity + 3 WHERE Relief_ID = ?', [(int) $receiptItem['Relief_ID']]);
    audit('add', 'relief_batch', 'Batch_ID=' . $receiptBatchId, 'Smoke test receipt');
    $receipt = row1('SELECT Batch_Code, Quantity_Received FROM relief_batch WHERE Batch_ID = ?', [$receiptBatchId]);
    $receiptTotal = (int) scalar('SELECT Quantity FROM relief_item WHERE Relief_ID = ?', [(int) $receiptItem['Relief_ID']]);
    $receiptAudit = row1("SELECT Action FROM audit_log WHERE Table_Name = 'relief_batch' AND Record_Key = ? ORDER BY Log_ID DESC LIMIT 1",
        ['Batch_ID=' . $receiptBatchId]);
    db()->rollback();
    check('stock receipts update batch, item total, and audit log atomically',
        ($receipt['Batch_Code'] ?? '') === $receiptCode && (int) ($receipt['Quantity_Received'] ?? 0) === 3
        && $receiptTotal === (int) $receiptItem['Quantity'] + 3 && ($receiptAudit['Action'] ?? '') === 'add');
}
db()->begin_transaction();
q('UPDATE relief_item SET Quantity = Quantity + 1 WHERE Relief_ID = ?', [$legacyItem]);
$hasDiscrepancyAlert = (bool) array_filter(alerts(), fn($a) => ($a[3] ?? '') === 'relief_batch_discrepancy:' . $legacyItem);
db()->rollback();
check('inventory total discrepancies generate a stable alert', $hasDiscrepancyAlert);
$expiryBatchId = (int) scalar('SELECT Batch_ID FROM relief_batch WHERE Relief_ID = ? AND Quantity_Received > (SELECT COALESCE(SUM(d.Quantity), 0) FROM relief_distribution d WHERE d.Batch_ID = relief_batch.Batch_ID) ORDER BY Batch_ID LIMIT 1', [$dd['Relief_ID']]);
if ($expiryBatchId > 0) {
    db()->begin_transaction();
    q("UPDATE relief_batch SET Expiry_Date = '2000-01-01' WHERE Batch_ID = ?", [$expiryBatchId]);
    $hasExpiryAlert = (bool) array_filter(alerts(), fn($a) => ($a[3] ?? '') === 'relief_batch_expiry:' . $expiryBatchId);
    $expiredDispatchBlocked = str_contains((string) check_rules('relief_distribution', array_merge($dd, ['Batch_ID' => $expiryBatchId, 'Quantity' => 1]), null), 'expired');
    db()->rollback();
    check('expired stock with remaining units alerts and cannot be dispatched', $hasExpiryAlert && $expiredDispatchBlocked);
}
$staleShelterId = (int) scalar("SELECT s.Shelter_ID FROM shelter s
    WHERE EXISTS (SELECT 1 FROM disaster_shelter ds JOIN disaster d ON d.Disaster_ID = ds.Disaster_ID
                   WHERE ds.Shelter_ID = s.Shelter_ID AND d.Status IN ('Active','Under control'))
      AND s.Capacity_Verified_At IS NULL ORDER BY s.Shelter_ID LIMIT 1");
if ($staleShelterId > 0) {
    $staleKey = 'shelter_capacity_stale:' . $staleShelterId;
    db()->begin_transaction();
    q('UPDATE shelter SET Capacity_Verified_At = CURRENT_TIMESTAMP WHERE Shelter_ID = ?', [$staleShelterId]);
    $verificationClearsAlert = !(bool) array_filter(alerts(), fn($a) => ($a[3] ?? '') === $staleKey);
    db()->rollback();
    check('capacity verification clears the stale-shelter alert', $verificationClearsAlert);
}
$taskCreator = (int) scalar('SELECT User_ID FROM users ORDER BY User_ID LIMIT 1');
if ($taskCreator > 0) {
    db()->begin_transaction();
    q("INSERT INTO incident_task (Disaster_ID, Task_Title, Due_At, Status, Created_By) VALUES (13, 'Smoke overdue task', '2000-01-01 00:00:00', 'Open', ?)", [$taskCreator]);
    $taskId = (int) db()->insert_id;
    $taskAlertKey = 'incident_task_overdue:' . $taskId;
    $hasTaskAlert = (bool) array_filter(alerts(), fn($a) => ($a[3] ?? '') === $taskAlertKey);
    q("UPDATE incident_task SET Status = 'Done' WHERE Task_ID = ?", [$taskId]);
    $taskAlertCleared = !(bool) array_filter(alerts(), fn($a) => ($a[3] ?? '') === $taskAlertKey);
    db()->rollback();
    check('overdue incident tasks appear in alerts and clear when completed', $hasTaskAlert && $taskAlertCleared);
}

echo $fail === 0 ? "\nAll checks passed.\n" : "\n$fail check(s) FAILED.\n";
exit($fail === 0 ? 0 : 1);
