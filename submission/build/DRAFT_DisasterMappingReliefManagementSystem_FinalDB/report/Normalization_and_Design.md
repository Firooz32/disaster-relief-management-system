# Normalization, Keys, Cascades and Indexes

Disaster Mapping & Relief Management System - 25 tables, 40 foreign keys, 18 CHECK constraints, 8 triggers.
The full column list of every table is in `Relational_Schema.md`; the diagrams are `ERD_Full.pdf` and `ERD_Overview.pdf`.

## 1. Entity groups

| Group | Tables |
|---|---|
| Core entities | `disaster`, `area`, `shelter`, `affected_person`, `rescue_org`, `relief_item`, `emergency_item`, `users` |
| Many-to-many (junction) tables | `disaster_area` (+ `Relief_Shortage`), `disaster_shelter`, `disaster_affected_person`, `disaster_rescue_org`, `rescue_org_relief_item`, `affected_person_emergency_item`, `shelter_emergency_item`, `relief_recipient` |
| Events / transactions | `shelter_assignment`, `relief_distribution`, `relief_batch`, `damage`, `early_warning`, `disaster_track`, `incident_task`, `incident_update` |
| Audit | `audit_log` |

Cardinalities (all are one-to-many at the table level; many-to-many is resolved by a junction table):

- A disaster affects many areas and an area can be hit by many disasters -> `disaster_area` (M:N).
- A disaster uses many shelters; a shelter serves many disasters -> `disaster_shelter` (M:N).
- A shelter assignment links one disaster, one family and one shelter (1:N from each side); a family can have several stays (moved shelters) but at most one open stay.
- A relief item has many stock batches (1:N); a batch is dispatched in many distributions (1:N); a distribution reaches many families and a family receives many distributions -> `relief_recipient` (M:N).
- A disaster has many warnings, track points, damage records, tasks and field updates (1:N each).
- A user can lead many disasters, be assigned many tasks, verify many shelters (1:N, optional on the child side).

## 2. Normal forms

**1NF.** Every table has a primary key; every column holds one atomic value. Multi-valued facts ("the areas of a disaster", "the needs of a family") are never stored as lists; each is a row of a junction table.

**2NF.** Partial dependencies can only appear when a primary key is composite. The composite-key tables are the 8 junction tables, and their only non-key column is `disaster_area.Relief_Shortage`, which depends on the whole key (a shortage flag belongs to *this disaster in this area*, not to the disaster or the area alone). All other tables use a single-column surrogate key (`AUTO_INCREMENT`), so they are in 2NF automatically.

**3NF / BCNF.** For each table the determinants are the primary key and the declared candidate keys, so every non-key column depends on "the key, the whole key and nothing but the key":

| Table | Candidate keys (determinants) |
|---|---|
| `area` | `Area_ID`; (`Area_Name`, `Location`) |
| `shelter` | `Shelter_ID`; `Shelter_Name` |
| `relief_item`, `emergency_item` | `Relief_ID` / `Item_ID`; `Item_Name` |
| `rescue_org` | `Org_ID`; `Org_Name` |
| `users` | `User_ID`; `Username` |
| `relief_batch` | `Batch_ID`; `Batch_Code` |
| `disaster_track` | `Track_ID`; (`Disaster_ID`, `Point_Time`) |
| other tables | their primary key |

Values that could be stored but are always **calculated** (so they can never disagree with the records): shelter occupancy and places left, relief coverage per area, stock left per batch / item, current warning signal of an area, whether an area is being crossed by a cyclone.

### Deliberate exceptions (controlled redundancy), and how they are kept correct

| Where | Dependency | Why it is kept | What keeps it correct |
|---|---|---|---|
| `relief_distribution.Relief_ID` | `Batch_ID -> Relief_ID` (a batch belongs to one item), so `Distribution_ID -> Batch_ID -> Relief_ID` is transitive: **not strictly 3NF** | Batches were added after distributions; the column keeps old reports and the "organisation supplies this item" rule simple | `check_rules()` refuses a batch of another item; the strict-3NF fix would drop the column and join through `relief_batch` |
| `relief_item.Quantity` | equals `SUM(relief_batch.Quantity_Received)` (derived value) | Total kept for the earlier version and quick lists | Not editable in the forms (system-managed); written only together with a batch receipt in one transaction; an "Inventory discrepancy" alert fires if it ever differs |
| `shelter_assignment.Disaster_ID` | not functionally determined by the other columns (a family can be affected by several disasters), so 3NF holds; but there are **inclusion dependencies**: (Disaster, Shelter) must be in `disaster_shelter`, (Disaster, Person) in `disaster_affected_person` | The stay must say which incident it belongs to | Triggers `trg_assignment_ins/upd` |
| `audit_log.User_Name` | a copy of the user name, not a foreign key | The log must survive deleting a user and show the name at the time | Read-only table, written only by the application |

## 3. Keys and integrity rules

- **Primary keys**: surrogate `INT AUTO_INCREMENT` for entities and events; composite (both foreign keys) for junction tables, which also stops duplicate links.
- **Natural keys** as `UNIQUE`: area name + location, shelter name, item names, organisation name, username, batch code, (disaster, point time) of a track.
- **CHECK constraints (18)**: family size > 0, capacity > 0, quantities >= 0 / > 0, latitude -90..90, longitude -180..180, signal level 1..10, check-out >= check-in, expiry >= received date, track radius > 0, wind >= 0.
- **Functional unique index** `uq_assignment_open_stay ((IF(Check_Out IS NULL, Person_ID, NULL)))`: at most one *open* shelter stay per family, enforced by MySQL itself (NULLs do not collide, so any number of finished stays is allowed).
- **Triggers (8)**: cross-table rules a foreign key cannot express - a stay or distribution must use a shelter registered for that disaster; a family must be affected by the disaster; a recipient must belong to the distribution's disaster; damage must be in an area of the disaster; a distributing organisation must take part in the disaster.
- **Application rules** (`inc/rules.php`): shelter capacity and stock limits with friendly messages, run inside a transaction after `SELECT ... FOR UPDATE` locks so two users cannot both take the last place.

## 4. ON DELETE rules (24 CASCADE, 8 RESTRICT, 8 SET NULL)

| Rule | Used for | Reason |
|---|---|---|
| CASCADE | everything that only exists because of its parent: junction rows, warnings, track points, damage, tasks, updates, stays and distributions of a disaster; a family's needs and recipient rows | Deleting a disaster (admin only, with a confirmation page that lists what will go) removes its records cleanly |
| RESTRICT | `shelter` referenced by stays / distributions; `relief_item` referenced by batches / distributions; `relief_batch` referenced by distributions; `area` referenced by shelters; `users` who created tasks or reported updates | These rows are history or physical stock; they must be moved or closed first, never silently lost |
| SET NULL | optional links: home area of a family, area of damage / update, incident lead, task assignee, shelter verifier, batch receiver, organisation of a distribution | The child record stays valid without the link |

All foreign keys use `ON UPDATE CASCADE`.

## 5. Indexes

| Index | Serves |
|---|---|
| one index per foreign key (InnoDB requires it) | every join in the reports and every delete check |
| `ix_assignment_disaster_person (Disaster_ID, Person_ID)` | "stays of this family in this disaster" |
| `uq_assignment_open_stay` (functional, unique) | the one-open-stay rule |
| `ix_task_due_status (Status, Due_At)` | overdue-task alert - answered from the index alone |
| `ix_update_disaster_time (Disaster_ID, Observed_At)` | field updates of a disaster in time order |
| `ix_batch_expiry (Expiry_Date)` | expiring-stock alert, first-expiry-first-out list |
| UNIQUE natural keys | duplicate prevention and name look-ups / search |

`EXPLAIN` evidence (from the test database):

```
-- occupancy of one shelter
EXPLAIN SELECT s.Shelter_Name, SUM(p.Family_Size) FROM shelter_assignment a
  JOIN affected_person p ON p.Person_ID = a.Person_ID JOIN shelter s ON s.Shelter_ID = a.Shelter_ID
 WHERE a.Check_Out IS NULL AND a.Shelter_ID = 5 GROUP BY s.Shelter_Name;
  s  const   PRIMARY
  a  ref     fk_assignment_shelter
  p  eq_ref  PRIMARY

-- overdue tasks
EXPLAIN SELECT Task_ID FROM incident_task WHERE Status <> 'Done' AND Due_At < NOW();
  incident_task  index  ix_task_due_status  Using where; Using index   (covering index)
```
