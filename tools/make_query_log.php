<?php
/**
 * Runs the report's multi-table queries against a SCRATCH database loaded from submission/database/*.sql and writes
 * submission/report/Query_Log.md with each query, its purpose and its real output:
 *     DRMS_DB_NAME=drms_sample_check php tools/make_query_log.php
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require __DIR__ . '/../disaster_and_relief_management/db.php';
if (cfg('name') === 'disaster_and_relief_management') {
    fwrite(STDERR, "Use a scratch copy (DRMS_DB_NAME).\n");
    exit(1);
}

$queries = [
    ['Shelter occupancy and places left (Live Report - shelter panel)', 'shelter, area, shelter_assignment, affected_person',
     "SELECT s.Shelter_Name, a.Area_Name, s.Capacity,
       COALESCE(SUM(CASE WHEN sa.Check_Out IS NULL THEN p.Family_Size END), 0) AS Staying_Now,
       s.Capacity - COALESCE(SUM(CASE WHEN sa.Check_Out IS NULL THEN p.Family_Size END), 0) AS Places_Left
  FROM shelter s
  JOIN area a ON a.Area_ID = s.Area_ID
  LEFT JOIN shelter_assignment sa ON sa.Shelter_ID = s.Shelter_ID
  LEFT JOIN affected_person p ON p.Person_ID = sa.Person_ID
 GROUP BY s.Shelter_ID, s.Shelter_Name, a.Area_Name, s.Capacity
 ORDER BY Places_Left"],
    ['Current warning signal of each active disaster (latest warning with a signal)', 'disaster, early_warning (correlated subquery)',
     "SELECT d.Disaster_ID, d.Disaster_Type, d.Status, w.Alert_Type, w.Signal_Level, w.Alert_DateTime
  FROM disaster d
  JOIN early_warning w ON w.Disaster_ID = d.Disaster_ID
 WHERE d.Status IN ('Active', 'Under control')
   AND w.Alert_ID = (SELECT x.Alert_ID FROM early_warning x
                      WHERE x.Disaster_ID = d.Disaster_ID AND x.Signal_Level IS NOT NULL
                      ORDER BY x.Alert_DateTime DESC, x.Alert_ID DESC LIMIT 1)"],
    ['Relief coverage per affected area of the active cyclone (relief tracker)', 'disaster_area, area, affected_person, disaster_affected_person, relief_recipient, relief_distribution',
     "SELECT a.Area_Name, da.Relief_Shortage AS Flagged,
       COALESCE(SUM(p.Family_Size), 0) AS People,
       COALESCE(SUM(CASE WHEN EXISTS (SELECT 1 FROM relief_recipient r JOIN relief_distribution x ON x.Distribution_ID = r.Distribution_ID
                                       WHERE r.Person_ID = p.Person_ID AND x.Disaster_ID = da.Disaster_ID)
                         THEN p.Family_Size END), 0) AS Received
  FROM disaster_area da
  JOIN area a ON a.Area_ID = da.Area_ID
  LEFT JOIN disaster_affected_person dp ON dp.Disaster_ID = da.Disaster_ID
  LEFT JOIN affected_person p ON p.Person_ID = dp.Person_ID AND p.Area_ID = da.Area_ID
 WHERE da.Disaster_ID = 1
 GROUP BY a.Area_Name, da.Relief_Shortage
 ORDER BY a.Area_Name"],
    ['Stock per batch: received, dispatched, remaining (inventory page)', 'relief_batch, relief_item, relief_distribution',
     "SELECT i.Item_Name, b.Batch_Code, b.Quantity_Received,
       COALESCE(SUM(d.Quantity), 0) AS Dispatched,
       b.Quantity_Received - COALESCE(SUM(d.Quantity), 0) AS Remaining, b.Expiry_Date
  FROM relief_batch b
  JOIN relief_item i ON i.Relief_ID = b.Relief_ID
  LEFT JOIN relief_distribution d ON d.Batch_ID = b.Batch_ID
 GROUP BY b.Batch_ID, i.Item_Name, b.Batch_Code, b.Quantity_Received, b.Expiry_Date
 ORDER BY i.Item_Name"],
    ['Who received what, where and from whom (relief distribution report)', 'relief_recipient, affected_person, relief_distribution, relief_item, shelter, rescue_org, disaster',
     "SELECT p.Name AS Family, p.Family_Size, i.Item_Name, x.Quantity, s.Shelter_Name, o.Org_Name, CONCAT(d.Disaster_Type, ' #', d.Disaster_ID) AS Disaster, x.Distribution_Date
  FROM relief_recipient r
  JOIN affected_person p ON p.Person_ID = r.Person_ID
  JOIN relief_distribution x ON x.Distribution_ID = r.Distribution_ID
  JOIN relief_item i ON i.Relief_ID = x.Relief_ID
  JOIN shelter s ON s.Shelter_ID = x.Shelter_ID
  JOIN disaster d ON d.Disaster_ID = x.Disaster_ID
  LEFT JOIN rescue_org o ON o.Org_ID = x.Org_ID
 ORDER BY x.Distribution_Date, p.Name"],
    ['Damage and estimated loss per disaster and area (disaster report)', 'damage, disaster, area',
     "SELECT IF(GROUPING(x.Disaster), 'ALL DISASTERS', x.Disaster) AS Disaster,
       IF(GROUPING(x.Area_Name), '(subtotal)', x.Area_Name) AS Area,
       COUNT(*) AS Records, SUM(x.Estimated_Loss) AS Loss_BDT
  FROM (SELECT CONCAT(d.Disaster_Type, ' #', d.Disaster_ID) AS Disaster, a.Area_Name, m.Estimated_Loss
          FROM damage m
          JOIN disaster d ON d.Disaster_ID = m.Disaster_ID
          LEFT JOIN area a ON a.Area_ID = m.Area_ID) x
 GROUP BY x.Disaster, x.Area_Name WITH ROLLUP"],
    ['Open response tasks with owner and incident (dashboard)', 'incident_task, users, disaster',
     "SELECT t.Task_Title, t.Status, t.Due_At, u.Username AS Assigned_To, c.Username AS Created_By, CONCAT(d.Disaster_Type, ' #', d.Disaster_ID) AS Disaster
  FROM incident_task t
  JOIN disaster d ON d.Disaster_ID = t.Disaster_ID
  LEFT JOIN users u ON u.User_ID = t.Assigned_To
  JOIN users c ON c.User_ID = t.Created_By
 WHERE t.Status <> 'Done'
 ORDER BY t.Due_At"],
];

$md = "# Query Log - multi-table queries\n\nEach query below is what a screen of the application computes, run against the sample data in "
    . "`database/sample_data.sql`. Output is copied from MySQL " . db()->server_info . " by `tools/make_query_log.php`.\n\n";
foreach ($queries as $i => [$title, $tables, $sql]) {
    $rows = rows($sql);
    $md .= '## Q' . ($i + 1) . ". $title\n\nTables: $tables\n\n```sql\n$sql;\n```\n\n";
    if ($rows) {
        $md .= '| ' . implode(' | ', array_keys($rows[0])) . " |\n|" . str_repeat('---|', count($rows[0])) . "\n";
        foreach ($rows as $r) {
            $md .= '| ' . implode(' | ', array_map(fn($v) => $v === null ? 'NULL' : str_replace('|', '/', (string) $v), $r)) . " |\n";
        }
    }
    $md .= "\n" . count($rows) . " row(s).\n\n";
}
$dir = __DIR__ . '/../submission/report';
@mkdir($dir, 0777, true);
file_put_contents("$dir/Query_Log.md", $md);
echo 'Wrote ' . count($queries) . " queries to submission/report/Query_Log.md\n";
