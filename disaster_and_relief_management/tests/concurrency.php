<?php
/**
 * Concurrency test: two people check a family into the last places of a shelter at the same moment.
 *     php tests/concurrency.php          (MySQL must be running)
 * It adds two temporary families to the sample shelter "Terokhada Primary School Shelter" (2 places left), starts two
 * separate PHP processes that save at the same time, and expects exactly ONE to succeed. A control run without the lock
 * shows that the race is real. All temporary rows are deleted again, also when something fails.
 * Use it on a test database (DRMS_DB_NAME=...) or on the sample data; it does nothing else.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../inc/boot.php';
require __DIR__ . '/../inc/rules.php';

/* -------------------------------------------------------------------- worker: one "person clicking Save" */
if (($argv[1] ?? '') === 'worker') {
    [$mode, $person, $disaster, $shelter] = [$argv[2], (int) $argv[3], (int) $argv[4], (int) $argv[5]];
    $f = ['Disaster_ID' => $disaster, 'Person_ID' => $person, 'Shelter_ID' => $shelter, 'Check_In' => '2026-10-24', 'Check_Out' => ''];
    db()->begin_transaction();                            // the same steps as table.php
    if ($mode === 'lock') {
        lock_for_rules('shelter_assignment', $f);
    }
    $viol = check_rules('shelter_assignment', $f, null);
    if ($viol !== null) {
        db()->rollback();
        echo 'refused: ' . $viol;
        exit(0);
    }
    usleep(1_500_000);                                    // widen the window between the check and the save
    q('INSERT INTO shelter_assignment (Disaster_ID, Person_ID, Shelter_ID, Check_In) VALUES (?, ?, ?, ?)', [$disaster, $person, $shelter, '2026-10-24']);
    db()->commit();
    echo 'saved';
    exit(0);
}

/* -------------------------------------------------------------------- parent */
$fail = 0;
function check(string $name, bool $ok): void
{
    global $fail;
    echo ($ok ? '  ok   ' : '  FAIL ') . $name . "\n";
    $fail += $ok ? 0 : 1;
}

function cleanup(): void
{
    q("DELETE FROM shelter_assignment WHERE Person_ID IN (SELECT Person_ID FROM affected_person WHERE Name LIKE 'Race Test %')");
    q("DELETE FROM disaster_affected_person WHERE Person_ID IN (SELECT Person_ID FROM affected_person WHERE Name LIKE 'Race Test %')");
    q("DELETE FROM affected_person WHERE Name LIKE 'Race Test %'");
}

/** Starts two workers at the same time and returns what each one printed. */
function race(string $mode, array $people, int $disaster, int $shelter): array
{
    $procs = [];
    foreach ($people as $i => $p) {
        $cmd = [PHP_BINARY, __FILE__, 'worker', $mode, (string) $p, (string) $disaster, (string) $shelter];
        $procs[$i] = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes[$i], null, null);
    }
    $out = [];
    foreach ($procs as $i => $pr) {
        $out[$i] = trim((string) stream_get_contents($pipes[$i][1]) . stream_get_contents($pipes[$i][2]));
        proc_close($pr);
    }
    return $out;
}

cleanup();
$shelter  = (int) scalar("SELECT Shelter_ID FROM shelter WHERE Shelter_Name = 'Terokhada Primary School Shelter'");
$disaster = (int) scalar("SELECT d.Disaster_ID FROM disaster d JOIN disaster_shelter ds ON ds.Disaster_ID = d.Disaster_ID WHERE ds.Shelter_ID = $shelter AND d.Status IN ('Active','Under control') LIMIT 1");
if ($shelter === 0 || $disaster === 0) {
    echo "The sample shelter or its active disaster was not found - run this test on the sample data.\n";
    exit(2);
}
$left = static fn(): int => (int) scalar("SELECT s.Capacity - COALESCE(SUM(p.Family_Size), 0) FROM shelter s
        LEFT JOIN shelter_assignment a ON a.Shelter_ID = s.Shelter_ID AND a.Check_Out IS NULL
        LEFT JOIN affected_person p ON p.Person_ID = a.Person_ID WHERE s.Shelter_ID = $shelter GROUP BY s.Shelter_ID, s.Capacity");

try {
    $start = $left();
    echo "Shelter #$shelter has $start place(s) left. Two families of $start person(s) each try to take them at the same moment.\n";
    $people = [];
    foreach (['A', 'B'] as $x) {
        q("INSERT INTO affected_person (Name, Family_Size) VALUES (?, ?)", ["Race Test $x", $start]);
        $people[] = db()->insert_id;
        q('INSERT INTO disaster_affected_person (Disaster_ID, Person_ID) VALUES (?, ?)', [$disaster, db()->insert_id]);
    }

    // control: no lock -> both pass the check, the shelter is overbooked (this is the bug the lock prevents)
    $out = race('nolock', $people, $disaster, $shelter);
    $savedNoLock = count(array_filter($out, fn($o) => $o === 'saved'));
    check("control without a lock: the race is real (both saved: the shelter is overbooked)", $savedNoLock === 2 && $left() < 0);
    q('DELETE FROM shelter_assignment WHERE Person_ID IN (' . implode(',', $people) . ')');
    check('control cleaned up: the shelter has its places back', $left() === $start);

    // the real code path: with the lock exactly one is accepted
    $out = race('lock', $people, $disaster, $shelter);
    $saved   = array_keys(array_filter($out, fn($o) => $o === 'saved'));
    $refused = array_filter($out, fn($o) => str_starts_with($o, 'refused: The shelter would be over capacity'));
    check('with the lock exactly one family is saved', count($saved) === 1);
    check('the other one is refused with the over-capacity message', count($refused) === 1);
    check('the shelter is full but never over capacity', $left() === 0);
    echo '  workers said: ' . json_encode($out) . "\n";
} finally {
    cleanup();
}
check('temporary rows removed', scalar("SELECT COUNT(*) FROM affected_person WHERE Name LIKE 'Race Test %'") === 0.0);
echo $fail === 0 ? "\nAll concurrency checks passed.\n" : "\n$fail check(s) FAILED.\n";
exit($fail === 0 ? 0 : 1);
