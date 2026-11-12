<?php
/** Data-quality checks shown on health.php (and counted on the dashboard). Each check lists the records that break one rule. */
declare(strict_types=1);

/**
 * @return array<int,array{id:string,level:string,title:string,help:string,link:string,count:int,sample:string[]}>
 *         level: 'err' = the data contradicts itself, 'warn' = something is missing, 'info' = worth a look
 */
function health_checks(): array
{
    $defs = [
        ['assign_shelter', 'err', 'Shelter stays at a shelter that is not part of the disaster',
         'A person is checked in to a shelter that is not listed under "Disaster Shelter" for that disaster.', 'shelter_assignment',
         "SELECT CONCAT('Stay #', a.Assignment_ID, ': person ', a.Person_ID, ', shelter ', a.Shelter_ID, ', disaster ', a.Disaster_ID)
            FROM shelter_assignment a WHERE NOT EXISTS (SELECT 1 FROM disaster_shelter x WHERE x.Disaster_ID = a.Disaster_ID AND x.Shelter_ID = a.Shelter_ID)"],
        ['assign_person', 'err', 'Sheltered person is not affected by the disaster',
         'A shelter stay belongs to a person who is not listed under "Disaster Affected Person" for that disaster.', 'shelter_assignment',
         "SELECT CONCAT('Stay #', a.Assignment_ID, ': person ', a.Person_ID, ', disaster ', a.Disaster_ID)
            FROM shelter_assignment a WHERE NOT EXISTS (SELECT 1 FROM disaster_affected_person x WHERE x.Disaster_ID = a.Disaster_ID AND x.Person_ID = a.Person_ID)"],
        ['two_shelters', 'err', 'Person is staying in two shelters at once',
         'More than one open stay (empty Check Out) for the same person.', 'shelter_assignment',
         "SELECT CONCAT(p.Name, ' has ', COUNT(*), ' open stays') FROM shelter_assignment a JOIN affected_person p ON p.Person_ID = a.Person_ID
           WHERE a.Check_Out IS NULL GROUP BY a.Person_ID, p.Name HAVING COUNT(*) > 1"],
        ['over_capacity', 'err', 'Shelter holds more people than its capacity',
         'People staying now (sum of family sizes) is larger than the shelter capacity.', 'shelter',
         "SELECT CONCAT(s.Shelter_Name, ': ', u.n, ' staying, capacity ', s.Capacity) FROM shelter s
            JOIN (SELECT sa.Shelter_ID, SUM(p.Family_Size) AS n FROM shelter_assignment sa JOIN affected_person p ON p.Person_ID = sa.Person_ID
                   WHERE sa.Check_Out IS NULL GROUP BY sa.Shelter_ID) u ON u.Shelter_ID = s.Shelter_ID WHERE u.n > s.Capacity"],
        ['stock_negative', 'err', 'More relief given out than was received',
         'The distributed quantity of an item is larger than its received quantity (Relief Item).', 'relief_item',
         "SELECT CONCAT(r.Item_Name, ': received ', r.Quantity, ', given ', g.n) FROM relief_item r
            JOIN (SELECT Relief_ID, SUM(Quantity) AS n FROM relief_distribution GROUP BY Relief_ID) g ON g.Relief_ID = r.Relief_ID WHERE g.n > r.Quantity"],
        ['dist_shelter', 'err', 'Relief given to a shelter that is not part of the disaster',
         'A distribution points at a shelter that is not listed under "Disaster Shelter" for its disaster.', 'relief_distribution',
         "SELECT CONCAT('Distribution #', d.Distribution_ID, ': shelter ', d.Shelter_ID, ', disaster ', d.Disaster_ID)
            FROM relief_distribution d WHERE NOT EXISTS (SELECT 1 FROM disaster_shelter x WHERE x.Disaster_ID = d.Disaster_ID AND x.Shelter_ID = d.Shelter_ID)"],
        ['dist_org', 'err', 'Relief given by an organisation that was not involved or does not supply the item',
         'The organisation is not under "Disaster Rescue Org" for the disaster, or does not supply that item (Rescue Org Relief Item).', 'relief_distribution',
         "SELECT CONCAT('Distribution #', d.Distribution_ID, ': organisation ', d.Org_ID, ', item ', d.Relief_ID) FROM relief_distribution d
           WHERE d.Org_ID IS NOT NULL AND (NOT EXISTS (SELECT 1 FROM disaster_rescue_org x WHERE x.Disaster_ID = d.Disaster_ID AND x.Org_ID = d.Org_ID)
              OR NOT EXISTS (SELECT 1 FROM rescue_org_relief_item y WHERE y.Org_ID = d.Org_ID AND y.Relief_ID = d.Relief_ID))"],
        ['recipient', 'err', 'Relief recipient is not affected by the disaster of the distribution',
         'A family is recorded as a recipient of relief of a disaster it is not part of.', 'relief_recipient',
         "SELECT CONCAT('Distribution #', r.Distribution_ID, ': person ', r.Person_ID) FROM relief_recipient r JOIN relief_distribution d ON d.Distribution_ID = r.Distribution_ID
           WHERE NOT EXISTS (SELECT 1 FROM disaster_affected_person x WHERE x.Disaster_ID = d.Disaster_ID AND x.Person_ID = r.Person_ID)"],
        ['damage_area', 'err', 'Damage recorded in an area the disaster did not affect',
         'The damage area is not listed under "Disaster Area" for that disaster.', 'damage',
         "SELECT CONCAT('Damage #', m.Damage_ID, ': area ', m.Area_ID, ', disaster ', m.Disaster_ID) FROM damage m
           WHERE m.Area_ID IS NOT NULL AND NOT EXISTS (SELECT 1 FROM disaster_area x WHERE x.Disaster_ID = m.Disaster_ID AND x.Area_ID = m.Area_ID)"],
        ['active_no_signal', 'warn', 'Active disaster without a warning signal',
         'No early warning with a Signal Level has been recorded, so the Live Report shows "No signal".', 'early_warning',
         "SELECT CONCAT(d.Disaster_Type, ' of ', d.Disaster_Date, ' (#', d.Disaster_ID, ')') FROM disaster d
           WHERE d.Status IN ('Active','Under control') AND NOT EXISTS (SELECT 1 FROM early_warning w WHERE w.Disaster_ID = d.Disaster_ID AND w.Signal_Level IS NOT NULL)"],
        ['active_no_area', 'warn', 'Active disaster with no affected area',
         'The disaster has no row in "Disaster Area", so no area can get its signal or relief status.', 'disaster_area',
         "SELECT CONCAT(d.Disaster_Type, ' of ', d.Disaster_Date, ' (#', d.Disaster_ID, ')') FROM disaster d
           WHERE d.Status IN ('Active','Under control') AND NOT EXISTS (SELECT 1 FROM disaster_area x WHERE x.Disaster_ID = d.Disaster_ID)"],
        ['family_no_home', 'warn', 'Affected family of an active disaster without a home area',
         'Without a home area the family cannot be counted in the relief status of an area.', 'affected_person',
         "SELECT DISTINCT CONCAT(p.Name, ' (#', p.Person_ID, ')') FROM disaster_affected_person dp JOIN disaster d ON d.Disaster_ID = dp.Disaster_ID
            JOIN affected_person p ON p.Person_ID = dp.Person_ID WHERE d.Status IN ('Active','Under control') AND p.Area_ID IS NULL"],
        ['no_coords', 'warn', 'Area or shelter without map coordinates',
         'Latitude / Longitude is empty, so it is missing from the maps and the disaster path.', 'area',
         "SELECT CONCAT('Area: ', Area_Name) FROM area WHERE Latitude IS NULL OR Longitude IS NULL
          UNION ALL SELECT CONCAT('Shelter: ', Shelter_Name) FROM shelter WHERE Latitude IS NULL OR Longitude IS NULL"],
        ['track_no_actual', 'warn', 'Disaster path with only forecast points',
         'There is no observed (Actual) position, so "currently crossing" cannot be worked out.', 'disaster_track',
         "SELECT CONCAT(d.Disaster_Type, ' (#', d.Disaster_ID, ')') FROM disaster d WHERE EXISTS (SELECT 1 FROM disaster_track t WHERE t.Disaster_ID = d.Disaster_ID)
            AND NOT EXISTS (SELECT 1 FROM disaster_track t WHERE t.Disaster_ID = d.Disaster_ID AND t.Point_Type = 'Actual')"],
        ['ended_open_stays', 'info', 'Families still in a shelter although the disaster has ended',
         'Normal for displaced families, but make sure each one still needs the place (add a Check Out date when they leave).', 'shelter_assignment',
         "SELECT CONCAT(p.Name, ' in shelter ', a.Shelter_ID, ' (disaster #', a.Disaster_ID, ')') FROM shelter_assignment a JOIN disaster d ON d.Disaster_ID = a.Disaster_ID
            JOIN affected_person p ON p.Person_ID = a.Person_ID WHERE d.Status = 'Closed' AND a.Check_Out IS NULL"],
        ['long_stays', 'info', 'Families in a shelter for more than 30 days',
         'Long stays may need a longer-term solution.', 'shelter_assignment',
         "SELECT CONCAT(p.Name, ' since ', a.Check_In) FROM shelter_assignment a JOIN affected_person p ON p.Person_ID = a.Person_ID
           WHERE a.Check_Out IS NULL AND a.Check_In < DATE_SUB(CURDATE(), INTERVAL 30 DAY)"],
        ['low_stock', 'info', 'Relief items with less than 20% left',
         'Stock left = received - distributed.', 'relief_item',
         "SELECT CONCAT(r.Item_Name, ': ', r.Quantity - COALESCE(g.n, 0), ' of ', r.Quantity, ' left') FROM relief_item r
            LEFT JOIN (SELECT Relief_ID, SUM(Quantity) AS n FROM relief_distribution GROUP BY Relief_ID) g ON g.Relief_ID = r.Relief_ID
           WHERE r.Quantity > 0 AND (r.Quantity - COALESCE(g.n, 0)) / r.Quantity < 0.2"],
    ];
    $out = [];
    foreach ($defs as [$id, $level, $title, $help, $link, $sql]) {
        $labels = array_map(fn($r) => (string) array_values($r)[0], rows($sql . ' LIMIT 500'));
        $out[] = ['id' => $id, 'level' => $level, 'title' => $title, 'help' => $help, 'link' => $link, 'count' => count($labels), 'sample' => array_slice($labels, 0, 6)];
    }
    return $out;
}

/** Number of checks that currently find something at 'err' or 'warn' level (info lines do not count as problems). */
function health_problem_count(array $checks): int
{
    return count(array_filter($checks, fn($c) => $c['count'] > 0 && $c['level'] !== 'info'));
}
