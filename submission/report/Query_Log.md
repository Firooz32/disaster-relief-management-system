# Query Log - multi-table queries

Each query below is what a screen of the application computes, run against the sample data in `database/sample_data.sql`. Output is copied from MySQL 8.2.0 by `tools/make_query_log.php`.

## Q1. Shelter occupancy and places left (Live Report - shelter panel)

Tables: shelter, area, shelter_assignment, affected_person

```sql
SELECT s.Shelter_Name, a.Area_Name, s.Capacity,
       COALESCE(SUM(CASE WHEN sa.Check_Out IS NULL THEN p.Family_Size END), 0) AS Staying_Now,
       s.Capacity - COALESCE(SUM(CASE WHEN sa.Check_Out IS NULL THEN p.Family_Size END), 0) AS Places_Left
  FROM shelter s
  JOIN area a ON a.Area_ID = s.Area_ID
  LEFT JOIN shelter_assignment sa ON sa.Shelter_ID = s.Shelter_ID
  LEFT JOIN affected_person p ON p.Person_ID = sa.Person_ID
 GROUP BY s.Shelter_ID, s.Shelter_Name, a.Area_Name, s.Capacity
 ORDER BY Places_Left;
```

| Shelter_Name | Area_Name | Capacity | Staying_Now | Places_Left |
|---|---|---|---|---|
| Dacope Union Primary School Shelter | Dacope | 11 | 11 | 0 |
| Paikgachha Relief Shelter | Paikgachha | 450 | 5 | 445 |
| Sonadanga Community Shelter | Sonadanga | 500 | 0 | 500 |
| Koyra Government Shelter | Koyra | 700 | 0 | 700 |
| Dacope Cyclone Shelter | Dacope | 800 | 0 | 800 |

5 row(s).

## Q2. Current warning signal of each active disaster (latest warning with a signal)

Tables: disaster, early_warning (correlated subquery)

```sql
SELECT d.Disaster_ID, d.Disaster_Type, d.Status, w.Alert_Type, w.Signal_Level, w.Alert_DateTime
  FROM disaster d
  JOIN early_warning w ON w.Disaster_ID = d.Disaster_ID
 WHERE d.Status IN ('Active', 'Under control')
   AND w.Alert_ID = (SELECT x.Alert_ID FROM early_warning x
                      WHERE x.Disaster_ID = d.Disaster_ID AND x.Signal_Level IS NOT NULL
                      ORDER BY x.Alert_DateTime DESC, x.Alert_ID DESC LIMIT 1);
```

| Disaster_ID | Disaster_Type | Status | Alert_Type | Signal_Level | Alert_DateTime |
|---|---|---|---|---|---|
| 1 | Cyclone | Active | Great Danger Signal | 10 | 2026-10-24 03:00:00 |
| 2 | Flood | Under control | Flood Warning | 5 | 2026-10-02 07:30:00 |

2 row(s).

## Q3. Relief coverage per affected area of the active cyclone (relief tracker)

Tables: disaster_area, area, affected_person, disaster_affected_person, relief_recipient, relief_distribution

```sql
SELECT a.Area_Name, da.Relief_Shortage AS Flagged,
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
 ORDER BY a.Area_Name;
```

| Area_Name | Flagged | People | Received |
|---|---|---|---|
| Dacope | No | 11 | 11 |
| Koyra | No | 5 | 5 |
| Paikgachha | Yes | 4 | 0 |

3 row(s).

## Q4. Stock per batch: received, dispatched, remaining (inventory page)

Tables: relief_batch, relief_item, relief_distribution

```sql
SELECT i.Item_Name, b.Batch_Code, b.Quantity_Received,
       COALESCE(SUM(d.Quantity), 0) AS Dispatched,
       b.Quantity_Received - COALESCE(SUM(d.Quantity), 0) AS Remaining, b.Expiry_Date
  FROM relief_batch b
  JOIN relief_item i ON i.Relief_ID = b.Relief_ID
  LEFT JOIN relief_distribution d ON d.Batch_ID = b.Batch_ID
 GROUP BY b.Batch_ID, i.Item_Name, b.Batch_Code, b.Quantity_Received, b.Expiry_Date
 ORDER BY i.Item_Name;
```

| Item_Name | Batch_Code | Quantity_Received | Dispatched | Remaining | Expiry_Date |
|---|---|---|---|---|---|
| Drinking water (1 L bottle) | WATR-2026-10-A | 3000 | 600 | 2400 | 2027-10-01 |
| Oral rehydration saline | ORS-2026-10-A | 1500 | 200 | 1300 | 2028-03-31 |
| Rice (kg) | RICE-2026-10-A | 1200 | 450 | 750 | 2027-04-30 |
| Tarpaulin sheet | TARP-2026-10-A | 400 | 0 | 400 | NULL |

4 row(s).

## Q5. Who received what, where and from whom (relief distribution report)

Tables: relief_recipient, affected_person, relief_distribution, relief_item, shelter, rescue_org, disaster

```sql
SELECT p.Name AS Family, p.Family_Size, i.Item_Name, x.Quantity, s.Shelter_Name, o.Org_Name, CONCAT(d.Disaster_Type, ' #', d.Disaster_ID) AS Disaster, x.Distribution_Date
  FROM relief_recipient r
  JOIN affected_person p ON p.Person_ID = r.Person_ID
  JOIN relief_distribution x ON x.Distribution_ID = r.Distribution_ID
  JOIN relief_item i ON i.Relief_ID = x.Relief_ID
  JOIN shelter s ON s.Shelter_ID = x.Shelter_ID
  JOIN disaster d ON d.Disaster_ID = x.Disaster_ID
  LEFT JOIN rescue_org o ON o.Org_ID = x.Org_ID
 ORDER BY x.Distribution_Date, p.Name;
```

| Family | Family_Size | Item_Name | Quantity | Shelter_Name | Org_Name | Disaster | Distribution_Date |
|---|---|---|---|---|---|---|---|
| Jamal Hossain | 3 | Rice (kg) | 150 | Sonadanga Community Shelter | Bangladesh Red Crescent Society - Khulna Unit | Flood #2 | 2026-10-04 |
| Rahim Uddin | 5 | Oral rehydration saline | 200 | Koyra Government Shelter | Bangladesh Red Crescent Society - Khulna Unit | Cyclone #1 | 2026-10-23 |
| Abdul Karim | 5 | Drinking water (1 L bottle) | 600 | Dacope Union Primary School Shelter | Bangladesh Red Crescent Society - Khulna Unit | Cyclone #1 | 2026-10-24 |
| Fatema Begum | 6 | Drinking water (1 L bottle) | 600 | Dacope Union Primary School Shelter | Bangladesh Red Crescent Society - Khulna Unit | Cyclone #1 | 2026-10-24 |

4 row(s).

## Q6. Damage and estimated loss per disaster and area (disaster report)

Tables: damage, disaster, area

```sql
SELECT IF(GROUPING(x.Disaster), 'ALL DISASTERS', x.Disaster) AS Disaster,
       IF(GROUPING(x.Area_Name), '(subtotal)', x.Area_Name) AS Area,
       COUNT(*) AS Records, SUM(x.Estimated_Loss) AS Loss_BDT
  FROM (SELECT CONCAT(d.Disaster_Type, ' #', d.Disaster_ID) AS Disaster, a.Area_Name, m.Estimated_Loss
          FROM damage m
          JOIN disaster d ON d.Disaster_ID = m.Disaster_ID
          LEFT JOIN area a ON a.Area_ID = m.Area_ID) x
 GROUP BY x.Disaster, x.Area_Name WITH ROLLUP;
```

| Disaster | Area | Records | Loss_BDT |
|---|---|---|---|
| Cyclone #1 | Dacope | 1 | 25000000.00 |
| Cyclone #1 | Koyra | 1 | 36000000.00 |
| Cyclone #1 | Paikgachha | 1 | 54000000.00 |
| Cyclone #1 | (subtotal) | 3 | 115000000.00 |
| Cyclone #3 | Dacope | 1 | 8000000.00 |
| Cyclone #3 | (subtotal) | 1 | 8000000.00 |
| Flood #2 | Sonadanga | 1 | 6500000.00 |
| Flood #2 | (subtotal) | 1 | 6500000.00 |
| ALL DISASTERS | (subtotal) | 5 | 129500000.00 |

9 row(s).

## Q7. Open response tasks with owner and incident (dashboard)

Tables: incident_task, users, disaster

```sql
SELECT t.Task_Title, t.Status, t.Due_At, u.Username AS Assigned_To, c.Username AS Created_By, CONCAT(d.Disaster_Type, ' #', d.Disaster_ID) AS Disaster
  FROM incident_task t
  JOIN disaster d ON d.Disaster_ID = t.Disaster_ID
  LEFT JOIN users u ON u.User_ID = t.Assigned_To
  JOIN users c ON c.User_ID = t.Created_By
 WHERE t.Status <> 'Done'
 ORDER BY t.Due_At;
```

| Task_Title | Status | Due_At | Assigned_To | Created_By | Disaster |
|---|---|---|---|---|---|
| Clear blocked drains on Sonadanga main road | Blocked | 2026-10-06 18:00:00 | demo_editor | demo_admin | Flood #2 |
| Re-check capacity of Dacope Union Primary School | Open | 2026-10-25 09:00:00 | demo_admin | demo_editor | Cyclone #1 |
| Deliver oral saline to Paikgachha Relief Shelter | In progress | 2026-10-25 12:00:00 | demo_editor | demo_admin | Cyclone #1 |

3 row(s).

