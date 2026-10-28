<?php
/** Summary queries shared by the dashboard, the live report and the map, so the numbers can never disagree. */
declare(strict_types=1);

function overall_stats(): array
{
    return [
        'disasters'   => (int) scalar('SELECT COUNT(*) FROM disaster'),
        'people'      => (int) scalar('SELECT COUNT(*) FROM affected_person'),
        'family'      => (int) scalar('SELECT COALESCE(SUM(Family_Size),0) FROM affected_person'),
        'areas'       => (int) scalar('SELECT COUNT(*) FROM area'),
        'shelters'    => (int) scalar('SELECT COUNT(*) FROM shelter'),
        'capacity'    => (int) scalar('SELECT COALESCE(SUM(Capacity),0) FROM shelter'),
        'occupied'    => (int) scalar('SELECT COALESCE(SUM(p.Family_Size),0) FROM shelter_assignment sa JOIN affected_person p ON p.Person_ID = sa.Person_ID WHERE sa.Check_Out IS NULL'),
        'orgs'        => (int) scalar('SELECT COUNT(*) FROM rescue_org'),
        'warnings'    => (int) scalar('SELECT COUNT(*) FROM early_warning'),
        'relief'      => (int) scalar('SELECT COALESCE(SUM(Quantity),0) FROM relief_item'),
        'distributed' => (int) scalar('SELECT COALESCE(SUM(Quantity),0) FROM relief_distribution'),
        'loss'        => scalar('SELECT COALESCE(SUM(Estimated_Loss),0) FROM damage'),
    ];
}

function disaster_summary(): array
{
    return rows(
        'SELECT d.Disaster_ID AS id, d.Disaster_Type AS type, d.Disaster_Date AS date,
                (SELECT GROUP_CONCAT(a.Area_Name ORDER BY a.Area_Name SEPARATOR ", ")
                   FROM disaster_area da JOIN area a ON a.Area_ID = da.Area_ID WHERE da.Disaster_ID = d.Disaster_ID) AS areas,
                (SELECT COUNT(*) FROM disaster_affected_person x WHERE x.Disaster_ID = d.Disaster_ID) AS people,
                (SELECT COUNT(*) FROM disaster_shelter s WHERE s.Disaster_ID = d.Disaster_ID) AS shelters,
                (SELECT COUNT(*) FROM disaster_rescue_org o WHERE o.Disaster_ID = d.Disaster_ID) AS orgs,
                (SELECT COALESCE(SUM(Estimated_Loss),0) FROM damage m WHERE m.Disaster_ID = d.Disaster_ID) AS loss
           FROM disaster d ORDER BY d.Disaster_ID ASC'
    );
}

function risk_summary(): array
{
    return rows("SELECT COALESCE(Risk_Level, 'Unknown') AS lvl, COUNT(*) AS n FROM area GROUP BY lvl
                  ORDER BY FIELD(lvl, 'Very High', 'High', 'Medium', 'Low', 'Unknown')");
}

/** One row per shelter; occupied = family members of people currently checked in (Check_Out empty). */
function shelter_summary(): array
{
    return rows(
        'SELECT s.Shelter_ID AS id, s.Shelter_Name AS name, s.Area_ID AS area_id, a.Area_Name AS area, s.Capacity AS capacity,
                CAST(COALESCE((SELECT SUM(p.Family_Size) FROM shelter_assignment sa JOIN affected_person p ON p.Person_ID = sa.Person_ID
                                WHERE sa.Shelter_ID = s.Shelter_ID AND sa.Check_Out IS NULL), 0) AS SIGNED) AS occupied,
                s.Latitude AS lat, s.Longitude AS lng
           FROM shelter s LEFT JOIN area a ON a.Area_ID = s.Area_ID ORDER BY s.Shelter_ID ASC'
    );
}

/**
 * Free places of a shelter. Green with the exact number while any place is left; red "No space available" at 0
 * (an over-full shelter counts as 0 left too).
 */
function space_info(int $capacity, int $occupied): array
{
    $left = max(0, $capacity - $occupied);
    return $left > 0
        ? ['left' => $left, 'cls' => 'ok', 'label' => $left . ($left === 1 ? ' place left' : ' places left')]
        : ['left' => 0, 'cls' => 'err', 'label' => 'No space available'];
}

/** Every shelter with its live occupancy and free places (shelter_summary() + space_info()), for the Live Report. */
function shelter_capacity(): array
{
    return array_map(fn($s) => ['lat' => $s['lat'] === null ? null : (float) $s['lat'], 'lng' => $s['lng'] === null ? null : (float) $s['lng']]
        + $s + ['space' => space_info((int) $s['capacity'], (int) $s['occupied'])], shelter_summary());
}

/** Areas with coordinates (id, name, lat, lng): the starting points of the "nearest shelter" finder. */
function area_points(): array
{
    return array_map(fn($a) => ['id' => (int) $a['id'], 'name' => $a['name'], 'lat' => (float) $a['lat'], 'lng' => (float) $a['lng']],
        rows('SELECT Area_ID AS id, Area_Name AS name, Latitude AS lat, Longitude AS lng FROM area WHERE Latitude IS NOT NULL AND Longitude IS NOT NULL ORDER BY Area_Name'));
}

/** received = relief_item.Quantity, distributed = sum of relief_distribution, remaining = received - distributed. */
function relief_summary(): array
{
    $rs = rows(
        'SELECT r.Relief_ID AS id, r.Item_Name AS name, r.Category AS cat, r.Quantity AS received,
                CAST(COALESCE((SELECT SUM(d.Quantity) FROM relief_distribution d WHERE d.Relief_ID = r.Relief_ID), 0) AS SIGNED) AS distributed
           FROM relief_item r ORDER BY r.Relief_ID ASC'
    );
    foreach ($rs as &$r) {
        $r['remaining'] = $r['received'] - $r['distributed'];
    }
    return $rs;
}

/** Great-circle distance in km between two latitude / longitude points. */
function km_between(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $r = 6371.0;
    $a = sin(deg2rad($lat2 - $lat1) / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($lng2 - $lng1) / 2) ** 2;
    return 2 * $r * asin(min(1.0, sqrt($a)));
}

/**
 * Path of every Active disaster that has track points (disaster_track).
 * current  = the latest Actual point; every area whose centre lies within that point's Radius_Km is "now" (being crossed).
 * Otherwise an area inside the radius of a Forecast point is "next" (in the forecast path), and one inside the radius of an
 * earlier Actual point is "passed". Areas without coordinates cannot be placed and are left out.
 * @return array<int,array<string,mixed>>
 */
function path_status(): array
{
    $out = [];
    $areas = rows('SELECT Area_ID AS id, Area_Name AS name, Location AS loc, Risk_Level AS risk, Latitude AS lat, Longitude AS lng FROM area
                    WHERE Latitude IS NOT NULL AND Longitude IS NOT NULL ORDER BY Area_Name');
    foreach (rows("SELECT Disaster_ID, Disaster_Type, Disaster_Date FROM disaster WHERE Status = 'Active' ORDER BY Disaster_Date DESC, Disaster_ID DESC") as $d) {
        $pts = rows('SELECT Point_Time AS time, Point_Type AS type, Latitude AS lat, Longitude AS lng, Radius_Km AS radius, Wind_Speed_Kmh AS wind
                       FROM disaster_track WHERE Disaster_ID = ? ORDER BY Point_Time, Track_ID', [$d['Disaster_ID']]);
        if (!$pts) {
            continue;
        }
        $pts = array_map(fn($p) => ['lat' => (float) $p['lat'], 'lng' => (float) $p['lng'], 'radius' => (int) $p['radius'], 'wind' => $p['wind'] === null ? null : (int) $p['wind']] + $p, $pts);
        $cur = null;                                       // index of the latest observed point
        foreach ($pts as $i => $p) {
            if ($p['type'] === 'Actual') {
                $cur = $i;
            }
        }
        $placed = [];
        foreach ($areas as $a) {
            $state = 'none';
            $dist  = null;
            foreach ($pts as $i => $p) {
                $km = km_between((float) $a['lat'], (float) $a['lng'], $p['lat'], $p['lng']);
                if ($km > $p['radius']) {
                    continue;
                }
                $rank = $i === $cur ? 3 : ($p['type'] === 'Forecast' ? 2 : 1);
                $st   = [3 => 'now', 2 => 'next', 1 => 'passed'][$rank];
                if ($state === 'none' || $rank > ['none' => 0, 'passed' => 1, 'next' => 2, 'now' => 3][$state]) {
                    $state = $st;
                }
            }
            if ($cur !== null) {
                $dist = round(km_between((float) $a['lat'], (float) $a['lng'], $pts[$cur]['lat'], $pts[$cur]['lng']), 1);
            }
            $placed[] = ['id' => (int) $a['id'], 'name' => $a['name'], 'loc' => $a['loc'], 'risk' => $a['risk'], 'lat' => (float) $a['lat'], 'lng' => (float) $a['lng'],
                         'state' => $state, 'km' => $dist];
        }
        $out[] = ['id' => (int) $d['Disaster_ID'], 'type' => $d['Disaster_Type'], 'date' => $d['Disaster_Date'], 'points' => $pts,
                  'current' => $cur, 'areas' => $placed];
    }
    return $out;
}

/** Relief is "short" when fewer than this share of the affected people have received any (or a coordinator flagged the area). */
const RELIEF_COVERAGE_MIN = 0.8;

/**
 * Relief status of a group of affected people. Red "Shortage" when coverage is under RELIEF_COVERAGE_MIN or the
 * coordinator flagged it (disaster_area.Relief_Shortage = 'Yes'); green "Relief on track" otherwise; grey when nobody is recorded.
 */
function relief_info(int $people, int $received, bool $flagged): array
{
    $pct   = $people > 0 ? (int) floor($received / $people * 100) : null;
    $auto  = $people > 0 && $received / $people < RELIEF_COVERAGE_MIN;
    $short = $auto || $flagged;
    $why   = $flagged ? 'Flagged as a shortage by a coordinator' . ($auto ? " and only $pct% have received relief" : '')
                      : ($auto ? "Only $pct% have received relief (under " . (int) (RELIEF_COVERAGE_MIN * 100) . '%)' : '');
    return [
        'cls'    => $short ? 'err' : ($people > 0 ? 'ok' : 'none'),
        'label'  => $short ? 'Shortage' : ($people > 0 ? 'Relief on track' : 'No families recorded'),
        'reason' => $why,
        'pct'    => $pct,
    ];
}

/**
 * Relief received / not received for every Active disaster, per home area of the affected families.
 * "People" = family members (Family_Size). A family has received relief when it is a recipient of any distribution of that disaster.
 */
function relief_status(): array
{
    return array_map('relief_for_disaster', rows("SELECT Disaster_ID, Disaster_Type, Disaster_Date FROM disaster WHERE Status = 'Active' ORDER BY Disaster_Date DESC, Disaster_ID DESC"));
}

/** Relief received / not received for ONE disaster (any status), per home area. $d = row with Disaster_ID, Disaster_Type, Disaster_Date. */
function relief_for_disaster(array $d): array
{
    $id    = (int) $d['Disaster_ID'];
    $flags = array_column(rows('SELECT Area_ID, Relief_Shortage FROM disaster_area WHERE Disaster_ID = ?', [$id]), 'Relief_Shortage', 'Area_ID');
    $grp   = [];                                        // Area_ID (0 = home area not recorded) => totals
    foreach ($flags as $a => $_) {
        $grp[$a] = ['people' => 0, 'received' => 0, 'families' => 0, 'fam_received' => 0];
    }
    $fams = rows('SELECT p.Family_Size, COALESCE(p.Area_ID, 0) AS area,
                         EXISTS (SELECT 1 FROM relief_recipient r JOIN relief_distribution x ON x.Distribution_ID = r.Distribution_ID
                                  WHERE r.Person_ID = p.Person_ID AND x.Disaster_ID = dp.Disaster_ID) AS got
                    FROM disaster_affected_person dp JOIN affected_person p ON p.Person_ID = dp.Person_ID WHERE dp.Disaster_ID = ?', [$id]);
    foreach ($fams as $f) {
        $g = &$grp[(int) $f['area']];
        $g ??= ['people' => 0, 'received' => 0, 'families' => 0, 'fam_received' => 0];
        $g['people']   += (int) $f['Family_Size'];
        $g['families'] += 1;
        if ($f['got']) {
            $g['received']     += (int) $f['Family_Size'];
            $g['fam_received'] += 1;
        }
        unset($g);
    }
    $names = array_column(rows('SELECT Area_ID, Area_Name FROM area'), 'Area_Name', 'Area_ID');
    $areas = [];
    $tot   = ['people' => 0, 'received' => 0, 'families' => 0, 'fam_received' => 0];
    $anyShort = false;
    foreach ($grp as $a => $g) {
        $flagged = ($flags[$a] ?? 'No') === 'Yes';
        if ($g['people'] === 0 && !$flagged) {
            continue;                                    // an affected area with no recorded families and no flag: nothing to show
        }
        $info = relief_info($g['people'], $g['received'], $flagged);
        $anyShort = $anyShort || $info['cls'] === 'err';
        foreach ($tot as $k => $_) {
            $tot[$k] += $g[$k];
        }
        $areas[] = ['area_id' => $a, 'name' => $a === 0 ? 'Home area not recorded' : (string) ($names[$a] ?? $a), 'flagged' => $flagged,
                    'not_received' => $g['people'] - $g['received']] + $g + ['rel' => $info];
    }
    usort($areas, fn($x, $y) => [$y['rel']['cls'] === 'err', $x['name']] <=> [$x['rel']['cls'] === 'err', $y['name']]);   // shortages first
    $all = relief_info($tot['people'], $tot['received'], false);
    if ($anyShort) {
        $all = ['cls' => 'err', 'label' => 'Shortage', 'reason' => 'At least one area is short of relief', 'pct' => $all['pct']];
    }
    return ['id' => $id, 'type' => $d['Disaster_Type'], 'date' => $d['Disaster_Date'], 'not_received' => $tot['people'] - $tot['received'],
            'rel' => $all, 'areas' => $areas] + $tot;
}

/**
 * Warning signal 1-10 -> what the screen shows. 1-3 Normal (green), 4-7 Danger (orange), 8-10 Severe Danger (red);
 * no signal number recorded -> grey "No signal". 'cls' is the CSS class suffix (sig-ok / sig-warn / sig-err / sig-none).
 */
function signal_info(?int $level): array
{
    if ($level === null || $level < 1 || $level > 10) {
        return ['level' => null, 'label' => 'No signal', 'cls' => 'none'];
    }
    if ($level <= 3) {
        return ['level' => $level, 'label' => 'Normal', 'cls' => 'ok'];
    }
    return $level <= 7 ? ['level' => $level, 'label' => 'Danger', 'cls' => 'warn'] : ['level' => $level, 'label' => 'Severe Danger', 'cls' => 'err'];
}

/**
 * Warning signals of the disasters that are Active right now.
 * A disaster's signal = its latest warning that has a signal number. An area's signal = the highest signal among the
 * active disasters affecting it (an area no active disaster touches has no signal).
 * @return array{disasters: array, areas: array}
 */
function signal_summary(): array
{
    $disasters = [];
    $areaSig   = [];                  // Area_ID => ['level' => highest signal or null, 'by' => ["Cyclone (signal 10)", ...]]
    foreach (rows("SELECT Disaster_ID, Disaster_Type, Disaster_Date FROM disaster WHERE Status = 'Active' ORDER BY Disaster_Date DESC, Disaster_ID DESC") as $d) {
        $w = row1('SELECT Alert_Type, Signal_Level, Alert_DateTime, Message FROM early_warning
                    WHERE Disaster_ID = ? AND Signal_Level IS NOT NULL ORDER BY Alert_DateTime DESC, Alert_ID DESC LIMIT 1', [$d['Disaster_ID']]);
        $lvl   = $w ? (int) $w['Signal_Level'] : null;
        $areas = rows('SELECT a.Area_ID, a.Area_Name FROM disaster_area da JOIN area a ON a.Area_ID = da.Area_ID WHERE da.Disaster_ID = ? ORDER BY a.Area_Name', [$d['Disaster_ID']]);
        foreach ($areas as $a) {
            $e = &$areaSig[$a['Area_ID']];
            $e ??= ['level' => null, 'by' => []];
            $e['level'] = $lvl === null ? $e['level'] : max((int) $e['level'], $lvl);
            $e['by'][]  = $d['Disaster_Type'] . ($lvl === null ? ' (no signal)' : " (signal $lvl)");
            unset($e);
        }
        $disasters[] = [
            'id'      => (int) $d['Disaster_ID'],
            'type'    => $d['Disaster_Type'],
            'date'    => $d['Disaster_Date'],
            'sig'     => signal_info($lvl),
            'alert'   => $w['Alert_Type'] ?? null,
            'time'    => $w['Alert_DateTime'] ?? null,
            'message' => $w['Message'] ?? null,
            'areas'   => array_column($areas, 'Area_Name'),
        ];
    }
    $out = [];
    foreach (rows('SELECT Area_ID, Area_Name, Location FROM area ORDER BY Area_Name') as $a) {
        $e     = $areaSig[$a['Area_ID']] ?? null;
        $out[] = ['id' => (int) $a['Area_ID'], 'name' => $a['Area_Name'], 'loc' => $a['Location'], 'hit' => $e !== null,
                  'sig' => signal_info($e['level'] ?? null), 'by' => $e['by'] ?? []];
    }
    usort($out, fn($x, $y) => [$y['hit'], $y['sig']['level'] ?? 0, $x['name']] <=> [$x['hit'], $x['sig']['level'] ?? 0, $y['name']]);
    return ['disasters' => $disasters, 'areas' => $out];
}

/**
 * Things that need attention, most urgent first: [level 'err'|'warn'|'info', text, link].
 * Thresholds: shelter 90% full, relief stock under 20% left, families staying over 30 days.
 */
function alerts(): array
{
    $a = [];
    foreach (shelter_summary() as $s) {
        if ($s['capacity'] > 0 && $s['occupied'] / $s['capacity'] >= 0.9) {
            $a[] = ['err', $s['name'] . ' is ' . round($s['occupied'] / $s['capacity'] * 100) . '% full (' . $s['occupied'] . ' of ' . $s['capacity'] . ' places).',
                'table.php?' . http_build_query(['t' => 'shelter_assignment', 'col' => 'Shelter_ID', 'q' => $s['name']])];
        }
    }
    foreach (relief_summary() as $r) {
        if ($r['remaining'] < 0) {
            $a[] = ['err', $r['name'] . ': more was given out than was received (' . $r['remaining'] . ').', 'table.php?t=relief_distribution'];
        } elseif ($r['received'] > 0 && $r['remaining'] / $r['received'] < 0.2) {
            $a[] = ['warn', 'Low relief stock - ' . $r['name'] . ': ' . $r['remaining'] . ' of ' . $r['received'] . ' left.', 'table.php?t=relief_item'];
        }
    }
    foreach (rows("SELECT Area_Name, Risk_Level FROM area a WHERE Risk_Level IN ('High', 'Very High')
                      AND NOT EXISTS (SELECT 1 FROM shelter s WHERE s.Area_ID = a.Area_ID) ORDER BY Area_Name") as $r) {
        $a[] = ['warn', $r['Area_Name'] . ' has ' . $r['Risk_Level'] . ' risk but no shelter.', 'table.php?t=shelter'];
    }
    foreach (rows('SELECT Disaster_ID, Disaster_Type, Disaster_Date FROM disaster d
                    WHERE NOT EXISTS (SELECT 1 FROM early_warning w WHERE w.Disaster_ID = d.Disaster_ID) ORDER BY Disaster_Date') as $r) {
        $a[] = ['warn', $r['Disaster_Type'] . ' of ' . $r['Disaster_Date'] . ' has no early warning recorded.', 'table.php?t=early_warning'];
    }
    $long = (int) scalar('SELECT COUNT(*) FROM shelter_assignment WHERE Check_Out IS NULL AND Check_In < DATE_SUB(CURDATE(), INTERVAL 30 DAY)');
    if ($long > 0) {
        $a[] = ['info', "$long " . ($long === 1 ? 'family has' : 'families have') . ' been in a shelter for more than 30 days.', 'table.php?t=shelter_assignment'];
    }
    return $a;
}
