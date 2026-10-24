<?php
/**
 * Upgrades a database made by the 20-table version to the current one (warning signals, disaster status, home areas,
 * relief recipients, shortage flags, disaster path tracking) without changing or deleting existing rows.
 * Every step checks first, so an interrupted run can simply run again. Called by ensure_installed() in db.php.
 * A fresh install gets exactly the same result from disaster_and_relief_management.sql.
 */
declare(strict_types=1);

/** Person_ID => Area_ID (home area) for the sample families. */
const SAMPLE_HOME_AREAS = [
    1 => 3, 2 => 1, 3 => 3, 4 => 6, 5 => 6, 6 => 6, 7 => 1, 8 => 6, 9 => 16, 10 => 4,
    11 => 2, 12 => 12, 13 => 6, 14 => 4, 15 => 18, 16 => 3, 17 => 12, 18 => 18, 19 => 2, 20 => 3,
    21 => 12, 22 => 12, 23 => 1, 24 => 1, 25 => 1, 26 => 3, 27 => 3, 28 => 6, 29 => 2, 30 => 15,
    31 => 17, 32 => 7, 33 => 9, 34 => 3, 35 => 12, 36 => 1, 37 => 18, 38 => 17, 39 => 20, 40 => 3,
    41 => 2, 42 => 7, 43 => 3, 44 => 1, 45 => 1, 46 => 2, 47 => 3, 48 => 6, 49 => 15, 50 => 7,
    51 => 11, 52 => 2, 53 => 1, 54 => 1, 55 => 2, 56 => 1, 57 => 10, 58 => 16, 59 => 9, 60 => 11,
];

/** Alert_ID => Signal_Level for the sample warnings (erosion warnings carry no signal number). */
const SAMPLE_SIGNALS = [
    1 => 6, 2 => 7, 4 => 10, 5 => 6, 6 => 8, 7 => 4, 8 => 7, 9 => 10, 10 => 3, 11 => 6, 12 => 2,
    13 => 7, 16 => 4, 17 => 7, 18 => 9, 19 => 3, 20 => 6, 21 => 3, 22 => 4, 23 => 5, 24 => 2,
];

function has_column(string $t, string $c): bool
{
    return scalar('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$t, $c]) > 0;
}

/** True only for an untouched copy of the sample data, the only database that gets the new sample rows. */
function is_sample_data(): bool
{
    return scalar("SELECT (SELECT COUNT(*) FROM disaster WHERE Disaster_ID = 12 AND Disaster_Type = 'Flood' AND Disaster_Date = '2026-10-02')
                        + (SELECT COUNT(*) FROM affected_person WHERE Person_ID = 1 AND Name = 'Rahim Uddin')
                        + (SELECT COUNT(*) FROM affected_person WHERE Person_ID = 60 AND Name = 'Halima Sultana')
                        + (SELECT COUNT(*) FROM shelter WHERE Shelter_ID = 20 AND Shelter_Name = 'Koyra Embankment Shelter')
                        + (SELECT COUNT(*) FROM relief_distribution WHERE Distribution_ID BETWEEN 57 AND 60 AND Disaster_ID = 12)") === 8.0
        && scalar("SELECT COUNT(*) FROM disaster WHERE Disaster_Type = 'Cyclone' AND Disaster_Date = '2026-10-24'") === 0.0;
}

function upgrade_database(): void
{
    $sample = is_sample_data();          // decided before anything changes
    $c = db();
    if (!has_column('disaster', 'Status')) {
        $c->query("ALTER TABLE disaster ADD COLUMN `Status` enum('Active','Ended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active' AFTER `Description`");
        // disasters recorded before this version are history; in the sample data the October flood is still being handled
        $c->query("UPDATE disaster SET Status = 'Ended' WHERE NOT (Disaster_ID = 12 AND Disaster_Type = 'Flood' AND Disaster_Date = '2026-10-02')");
    }
    if (!has_column('early_warning', 'Signal_Level')) {
        $c->query('ALTER TABLE early_warning ADD COLUMN `Signal_Level` tinyint DEFAULT NULL AFTER `Alert_Type`,
                   ADD CONSTRAINT `chk_warning_signal` CHECK (`Signal_Level` BETWEEN 1 AND 10)');
    }
    if (!has_column('affected_person', 'Area_ID')) {
        $c->query('ALTER TABLE affected_person ADD COLUMN `Area_ID` int DEFAULT NULL AFTER `Contact`, ADD KEY `fk_person_area` (`Area_ID`),
                   ADD CONSTRAINT `fk_person_area` FOREIGN KEY (`Area_ID`) REFERENCES `area` (`Area_ID`) ON DELETE SET NULL ON UPDATE CASCADE');
    }
    if (!has_column('disaster_area', 'Relief_Shortage')) {
        $c->query("ALTER TABLE disaster_area ADD COLUMN `Relief_Shortage` enum('No','Yes') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No'");
    }
    $c->query('CREATE TABLE IF NOT EXISTS `relief_recipient` (
        `Distribution_ID` int NOT NULL,
        `Person_ID` int NOT NULL,
        PRIMARY KEY (`Distribution_ID`,`Person_ID`),
        KEY `fk_recipient_person` (`Person_ID`),
        CONSTRAINT `fk_recipient_distribution` FOREIGN KEY (`Distribution_ID`) REFERENCES `relief_distribution` (`Distribution_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_recipient_person` FOREIGN KEY (`Person_ID`) REFERENCES `affected_person` (`Person_ID`) ON DELETE CASCADE ON UPDATE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $c->query("CREATE TABLE IF NOT EXISTS `disaster_track` (
        `Track_ID` int NOT NULL AUTO_INCREMENT,
        `Disaster_ID` int NOT NULL,
        `Point_Time` datetime NOT NULL,
        `Point_Type` enum('Actual','Forecast') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Actual',
        `Latitude` decimal(9,6) NOT NULL,
        `Longitude` decimal(9,6) NOT NULL,
        `Radius_Km` int NOT NULL DEFAULT '25',
        `Wind_Speed_Kmh` int DEFAULT NULL,
        PRIMARY KEY (`Track_ID`),
        UNIQUE KEY `uq_track_disaster_time` (`Disaster_ID`,`Point_Time`),
        CONSTRAINT `fk_track_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `chk_track_lat` CHECK (`Latitude` BETWEEN -90 AND 90),
        CONSTRAINT `chk_track_lng` CHECK (`Longitude` BETWEEN -180 AND 180),
        CONSTRAINT `chk_track_radius` CHECK (`Radius_Km` > 0),
        CONSTRAINT `chk_track_wind` CHECK (`Wind_Speed_Kmh` >= 0)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    if (!has_column('audit_log', 'Details')) {
        $c->query('ALTER TABLE audit_log ADD COLUMN `Details` text COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `Record_Key`');
    }
    if ($sample) {
        $c->begin_transaction();         // all sample rows or none
        try {
            add_sample_rows();
            $c->commit();
        } catch (Throwable $e) {
            $c->rollback();
            throw $e;
        }
    }
    install_triggers();                  // last: the sample rows above are already consistent, and later edits are checked
}

/** Creates the integrity triggers of inc/triggers.sql (safe to run again: each one is dropped first). */
function install_triggers(): void
{
    run_sql_script(db(), (string) file_get_contents(__DIR__ . '/triggers.sql'));
}

/** The new sample rows: home areas, signal numbers, relief recipients of the October flood and a live cyclone (#13). */
function add_sample_rows(): void
{
    foreach (SAMPLE_HOME_AREAS as $p => $a) {
        q('UPDATE affected_person SET Area_ID = ? WHERE Person_ID = ?', [$a, $p]);
    }
    foreach (SAMPLE_SIGNALS as $w => $s) {
        q('UPDATE early_warning SET Signal_Level = ? WHERE Alert_ID = ?', [$s, $w]);
    }
    q("INSERT INTO early_warning (Disaster_ID, Alert_Type, Signal_Level, Alert_DateTime, Message) VALUES
       (12, 'Flood Receding', 2, '2026-10-10 08:00:00', 'Rivers have fallen below the danger mark. Families may return home in daylight but should stay alert.')");
    foreach ([57 => [30, 49, 58], 58 => [12, 17, 21, 22], 59 => [33, 57, 60]] as $dist => $people) {
        foreach ($people as $p) {
            q('INSERT INTO relief_recipient (Distribution_ID, Person_ID) VALUES (?, ?)', [$dist, $p]);
        }
    }

    q("INSERT INTO disaster (Disaster_Type, Disaster_Date, Description, Status) VALUES ('Cyclone', '2026-10-24',
       'A severe cyclonic storm formed over the Bay of Bengal, crossed the coast near Koyra and is moving north over Dacope and Paikgachha towards Khulna city.', 'Active')");
    $d = db()->insert_id;
    q("INSERT INTO shelter (Shelter_Name, Location, Capacity, Area_ID, Latitude, Longitude) VALUES ('Dacope Union Primary School Shelter', 'Dacope, Khulna', 16, 2, 22.565200, 89.508900)");
    $school = db()->insert_id;

    foreach ([1, 2, 3, 6, 7, 9, 11, 18] as $a) {
        q('INSERT INTO disaster_area (Disaster_ID, Area_ID, Relief_Shortage) VALUES (?, ?, ?)', [$d, $a, $a === 6 ? 'Yes' : 'No']);   // Paikgachha: saline running out
    }
    foreach ([1, 2, 3, 5, 6, 8, 10, 16, 20, $school] as $s) {
        q('INSERT INTO disaster_shelter (Disaster_ID, Shelter_ID) VALUES (?, ?)', [$d, $s]);
    }
    foreach ([1, 2, 7, 8, 10] as $o) {
        q('INSERT INTO disaster_rescue_org (Disaster_ID, Org_ID) VALUES (?, ?)', [$d, $o]);
    }
    foreach ([1, 3, 20, 26, 27, 41, 19, 29, 52, 5, 6, 13, 32, 42, 50, 15, 37] as $p) {
        q('INSERT INTO disaster_affected_person (Disaster_ID, Person_ID) VALUES (?, ?)', [$d, $p]);
    }
    foreach ([
        ['Cyclone Alert', 2, '2026-10-21 09:00:00', 'A depression over the south Bay of Bengal may become a cyclone. Distant cautionary signal 2 for the ports.'],
        ['Cyclone Warning', 4, '2026-10-22 12:00:00', 'The cyclone is moving north. Local warning signal 4 for Mongla port. Fishing boats must return to shore.'],
        ['Danger Signal', 7, '2026-10-23 10:00:00', 'The cyclone may cross the Khulna coast within 24 hours. Danger signal 7. Move children, the elderly and livestock to cyclone shelters.'],
        ['Great Danger Signal', 10, '2026-10-24 03:00:00', 'A severe cyclone is crossing the coast near Koyra. Great danger signal 10. Everyone in the coastal unions must be in a shelter now.'],
    ] as [$type, $sig, $at, $msg]) {
        q('INSERT INTO early_warning (Disaster_ID, Alert_Type, Signal_Level, Alert_DateTime, Message) VALUES (?, ?, ?, ?, ?)', [$d, $type, $sig, $at, $msg]);
    }
    q("INSERT INTO early_warning (Disaster_ID, Alert_Type, Signal_Level, Alert_DateTime, Message) VALUES
       (12, 'Flood Warning', 5, '2026-10-24 06:30:00', 'Fresh upstream rain: the rivers are rising again. Families in low-lying unions should keep to shelters and avoid flooded roads.')");
    foreach ([
        ['House Damage', 3, 'Preliminary report: kutcha houses were flattened by wind and the storm surge.', 140, '6200000.00'],
        ['Embankment Breach', 2, 'The embankment was breached at two points and saline water entered villages (quantity in sections).', 2, '8500000.00'],
    ] as [$type, $a, $desc, $qty, $loss]) {
        q('INSERT INTO damage (Disaster_ID, Damage_Type, Area_ID, Description, Quantity, Estimated_Loss) VALUES (?, ?, ?, ?, ?, ?)', [$d, $type, $a, $desc, $qty, $loss]);
    }
    // Person_ID, Shelter_ID, Check_In - the school shelter ends up exactly full (8 + 4 + 4 = 16 places)
    foreach ([[1, 3, '2026-10-23'], [3, 20, '2026-10-23'], [20, 20, '2026-10-23'], [27, 3, '2026-10-24'], [41, $school, '2026-10-23'],
              [19, $school, '2026-10-23'], [29, $school, '2026-10-24'], [52, 2, '2026-10-24'], [5, 5, '2026-10-23'], [13, 5, '2026-10-24'],
              [32, 6, '2026-10-23'], [50, 6, '2026-10-24'], [37, 16, '2026-10-23']] as [$p, $s, $in]) {
        q('INSERT INTO shelter_assignment (Disaster_ID, Person_ID, Shelter_ID, Check_In) VALUES (?, ?, ?, ?)', [$d, $p, $s, $in]);
    }
    // Relief_ID, Shelter_ID, Org_ID, Quantity, families who received it
    foreach ([[1, 3, 8, 120, [1, 27, 26]], [3, 20, 1, 400, [3, 20]], [9, 16, 7, 10, [15, 37]],
              [10, $school, 2, 60, [41]], [3, 6, 7, 150, [32, 50]], [6, 5, 1, 30, [5, 13, 6]]] as [$r, $s, $o, $qty, $people]) {
        q("INSERT INTO relief_distribution (Disaster_ID, Relief_ID, Shelter_ID, Org_ID, Quantity, Distribution_Date) VALUES (?, ?, ?, ?, ?, '2026-10-24')", [$d, $r, $s, $o, $qty]);
        $dist = db()->insert_id;
        foreach ($people as $p) {
            q('INSERT INTO relief_recipient (Distribution_ID, Person_ID) VALUES (?, ?)', [$dist, $p]);
        }
    }
    // the cyclone's path: 7 observed positions, then 4 forecast positions
    foreach ([
        ['2026-10-22 06:00:00', 'Actual', '20.600000', '89.950000', 60, 75],
        ['2026-10-22 18:00:00', 'Actual', '21.050000', '89.850000', 55, 90],
        ['2026-10-23 06:00:00', 'Actual', '21.500000', '89.720000', 50, 110],
        ['2026-10-23 18:00:00', 'Actual', '21.950000', '89.580000', 40, 130],
        ['2026-10-24 00:00:00', 'Actual', '22.200000', '89.450000', 30, 140],
        ['2026-10-24 06:00:00', 'Actual', '22.380000', '89.360000', 25, 135],
        ['2026-10-24 12:00:00', 'Actual', '22.520000', '89.430000', 20, 115],
        ['2026-10-24 18:00:00', 'Forecast', '22.680000', '89.520000', 20, 90],
        ['2026-10-25 00:00:00', 'Forecast', '22.840000', '89.560000', 20, 70],
        ['2026-10-25 06:00:00', 'Forecast', '23.050000', '89.620000', 20, 55],
        ['2026-10-25 12:00:00', 'Forecast', '23.300000', '89.700000', 20, 40],
    ] as [$at, $type, $lat, $lng, $radius, $wind]) {
        q('INSERT INTO disaster_track (Disaster_ID, Point_Time, Point_Type, Latitude, Longitude, Radius_Km, Wind_Speed_Kmh) VALUES (?, ?, ?, ?, ?, ?, ?)',
          [$d, $at, $type, $lat, $lng, $radius, $wind]);
    }
}
