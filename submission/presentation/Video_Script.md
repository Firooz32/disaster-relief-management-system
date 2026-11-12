# Video Script and Live Run Sheet (target 11:30, limit 10:00-12:00)

Speakers: **Firoz** (Taohiduzzaman Firoz, 210232) and **Urmy** (Archy Mondal Urmy, 240203). Each presents the work they built.
Speaking time: Firoz 5:45, Urmy 5:45. Speaking pace is about 140 words per minute; the spoken text below is written to fit.
Say the words in your own way; keep the actions and the order.

## Before every take (5 minutes)

1. `powershell -ExecutionPolicy Bypass -File tools\reset_demo.ps1` (recreates `drms_demo` and starts the app on http://127.0.0.1:8099/).
2. Screen layout, 1920x1080: **left half** phpMyAdmin (or MySQL Workbench) on database `drms_demo`; **right half** the browser with the app.
   Browser zoom 110%, phpMyAdmin zoom 110%. Close chat apps, turn on Windows "Do not disturb".
3. Open these tabs in advance:
   app: sign-in page; phpMyAdmin: `drms_demo` > `area`, `affected_person`, `shelter_assignment`, `audit_log`, and the SQL tab.
4. Slides / images ready: title slide, `submission/diagrams/ERD_Overview.png`, `ERD_Full.png` (zoomed), `Normalization_and_Design.md` table.
5. OBS: canvas and output 1920x1080, 30 fps, webcam overlay bottom-right for whoever is speaking, microphone test for both voices.

---

## Part 1 - Introduction and scope (2:00)

**[0:00-1:15] Firoz** - Slide: title, course, members, logo. Webcam on.

> Assalamu alaikum. I am Taohiduzzaman Firoz, ID 210232, and with me is Archy Mondal Urmy, ID 240203. This is our Database Systems
> project: the Disaster Mapping and Relief Management System.
> Our district, Khulna, is hit by cyclones and floods almost every year. When that happens, coordinators need answers fast:
> which areas are under danger signal, which shelters still have space, which families have not received relief yet, and where
> the cyclone is heading. Today these answers are spread over phone calls and paper lists.
> Our system keeps all of it in one relational database: disasters, areas, families, shelters, warnings, relief stock and its
> distribution, response tasks and the cyclone path, with three user roles: admin, editor and viewer.
> I worked on the requirements, the reporting queries and the user interface. Urmy built the database and the backend.

**[1:15-2:00] Urmy** - Slide: technology stack diagram (browser - PHP - MySQL).

> I am Archy Mondal Urmy. Technically, the frontend is HTML, CSS and plain JavaScript with Leaflet for maps. The backend is PHP 8
> using prepared statements only. The database engine is MySQL 8 with InnoDB, so we have real foreign keys, transactions,
> CHECK constraints and triggers. It runs locally on WampServer, and phpMyAdmin on the left of the screen shows the same database live.

## Part 2 - ER diagram and schema (3:45)

**[2:00-4:00] Firoz** - Show `ERD_Overview.png`, then zoom into `ERD_Full.png` around `disaster`, `disaster_area`, `shelter_assignment`.

> This is our ER diagram: 25 tables. The core entities are disaster, area, shelter, affected person - one row per family -,
> rescue organisation, relief item and user.
> Many-to-many relationships are resolved with junction tables. A disaster affects many areas and an area is hit by many
> disasters, so we have disaster_area, whose key is both foreign keys. It also carries one attribute, Relief_Shortage, which
> depends on the whole key: a shortage belongs to this disaster in this area.
> Events are separate tables: a shelter assignment links one disaster, one family and one shelter, with check-in and check-out
> dates. A relief item has stock batches, a batch is given out in distributions, and relief_recipient records which family got it.
> For normalization: every column is atomic, so 1NF. The only composite keys are the junction tables, and their only extra column
> depends on the full key, so 2NF. Every other determinant is a key - for example shelter name or batch code are declared UNIQUE -
> so the design is in 3NF and BCNF.
> We kept two controlled exceptions and we document them: relief_item.Quantity is a stored total of its batches, protected by the
> application and by a discrepancy alert; and a distribution stores Relief_ID although the batch already decides it - a transitive
> dependency we kept for simpler reports, checked on every save.
> Values like free shelter places or relief coverage are never stored. They are always calculated, so they can never be wrong.

**[4:00-5:45] Urmy** - Show `Relational_Schema.md` / phpMyAdmin Structure of `shelter_assignment`, then the Triggers tab, then the SQL tab with EXPLAIN.

> I turned this design into the MySQL schema. Every table has a primary key; entities use auto-increment keys, junction tables use
> the pair of foreign keys. We have 40 foreign keys, and the delete rule was chosen per relationship: 24 CASCADE for records that
> only exist because of their disaster; 8 RESTRICT where deleting would lose history or stock - you cannot delete a shelter that
> still has stays -; and 8 SET NULL for optional links, like the home area of a family.
> 18 CHECK constraints guard values: family size above zero, latitude in range, signal level 1 to 10, check-out not before check-in.
> Some rules span several tables, so I wrote 8 triggers - for example a family can only be placed in a shelter that is registered
> for the same disaster.
> One rule I like: a family may have many past stays but only one open stay. MySQL enforces it with a functional unique index
> on IF(Check_Out IS NULL, Person_ID, NULL); finished stays become NULL and never collide.
> Every foreign key has an index, plus composite indexes for the alerts. (Run the EXPLAIN.) Here the overdue-task query is answered
> from the index alone.

```sql
EXPLAIN SELECT Task_ID FROM incident_task WHERE Status <> 'Done' AND Due_At < NOW();
```

## Part 3 - Live demonstration (4:15)

**[5:45-8:00] Urmy - CRUD with the database side by side.** Sign in as `demo_editor / DemoEditor2026`.

| Time | Action in the app (right) | Show in phpMyAdmin (left) | Say |
|---|---|---|---|
| 5:45 | **Create**: Main tables > Affected Person > + Add record: Name `Shirin Akter`, Family Size `4`, Contact `+880-1712-000206`, Area `2 - Dacope` > Save | Browse `affected_person`: the new row (ID 6) appears; then `audit_log`: the `add` entry with the values | "Saved through a prepared statement; the audit log recorded who did it." |
| 6:10 | Disaster Affected Person > + Add: Disaster `1 - Cyclone`, Person `6 - Shirin Akter` > Save | Browse `disaster_affected_person` | "She is now linked to the cyclone - a junction row." |
| 6:25 | Shelter Assignment > + Add: Disaster 1, Person 6, Shelter `5 - Dacope Union Primary School Shelter`, Check In `2026-10-24` > Save. **Refused:** "over capacity: 11 staying now + 4 new > 11 places" | Run: `SELECT SUM(p.Family_Size) FROM shelter_assignment a JOIN affected_person p USING (Person_ID) WHERE a.Shelter_ID = 5 AND a.Check_Out IS NULL;` -> 11 | "The rule runs inside a transaction with SELECT ... FOR UPDATE, so two users cannot both take the last places." |
| 6:55 | Same form, Shelter `1 - Koyra Government Shelter` > Save (accepted) | Browse `shelter_assignment`: new open stay | "Read: the list is sorted, searchable, and exported to CSV." Click a column title, type `Koyra` in search. |
| 7:15 | **Update**: Shelter > Edit `Dacope Union Primary School Shelter`: Capacity `11` -> `20` > Update | Browse `shelter` (capacity 20); `audit_log` Details: `Capacity: 11 -> 20` | "The audit log keeps the old and the new value." |
| 7:30 | **Constraint**: Affected Person > + Add: Family Size `-2` > Save. **Refused** | SQL tab: `UPDATE shelter SET Capacity = 0 WHERE Shelter_ID = 5;` -> `Check constraint 'chk_shelter_capacity' is violated` | "The same CHECK protects the data even outside our app." |
| 7:40 | (no app action) | SQL tab: `INSERT INTO shelter_assignment (Disaster_ID, Person_ID, Shelter_ID, Check_In) VALUES (2, 5, 1, '2026-10-05');` -> trigger error "That shelter is not used for this disaster" | "This is the trigger, inside MySQL." |
| 7:48 | **Delete**: sign out, sign in `demo_admin / DemoAdmin2026`. Shelter > Delete `Dacope Union Primary School Shelter` -> page lists "blocks the deletion" (RESTRICT), button disabled. Then Disaster > Delete `3 - Cyclone 2026-09-18` -> lists 4 child rows "will be deleted too" > **Yes, delete** | Before: `SELECT COUNT(*) FROM early_warning WHERE Disaster_ID = 3;` -> 1. After: run again -> 0 | "RESTRICT protects history; CASCADE removes everything that belonged to the closed disaster. Only an admin may do this." |

**[8:00-10:00] Firoz - Reports and multi-table queries.**

| Time | Action | Say |
|---|---|---|
| 8:00 | **Live Report**: warning signal panel - Cyclone #1 red, signal 10 "Severe Danger"; Paikgachha and Dacope listed under it | "A disaster's signal is its latest warning; an area takes the highest signal of the disasters affecting it." |
| 8:25 | Shelter panel: Dacope school now green "9 places left" after Urmy's update | "Places left = capacity minus family members staying now - calculated live, the page refreshes every 5 seconds." |
| 8:45 | Relief tracker: Paikgachha red **Shortage** (0 of 4 people, flagged by a coordinator); **Dacope just turned red**: 11 of 15 people (73%) because Shirin Akter's family of 4 was added and has no relief yet; Koyra green 5 of 5 | "Shortage means under 80% of people reached, or a coordinator flagged it. Dacope was green before Urmy added a family - the panel changed by itself." |
| 9:05 | Path map: observed path, forecast line, impact circle; lists "crossing now: Dacope, Paikgachha", "passed: Koyra", "in the forecast path: Sonadanga" | "Each area is compared with every track point using the haversine distance." |
| 9:25 | phpMyAdmin SQL tab: run **Q3** from `submission/report/Query_Log.md` (relief coverage, 6 tables) -> Dacope 15 / 11, Koyra 5 / 5, Paikgachha 4 / 0 | "This is the query behind the relief panel - six tables joined - and its numbers match the screen." |
| 9:45 | Disaster > Report for Cyclone #1 (loss, shelters, relief); Data Health page "No problems found - all 17 checks passed" | "Data Health re-checks every rule across the whole database." |

## Part 4 - Challenges and future scope (1:30)

**[10:00-11:00] Urmy** - Webcam; one slide with the old and the new index.

> One hurdle came from our own tests. At first, shelter_assignment had a UNIQUE key on disaster and person. It looked right, but
> it meant a family that left one shelter could never be placed in another shelter during the same cyclone - exactly what happens
> when an embankment breaks. We replaced it with the real rule, at most one open stay, using the functional unique index I showed,
> and wrote an upgrade so existing databases change in place without losing data.
> A second challenge was concurrency: two coordinators saving the last places at the same moment. We solved it with transactions
> and row locks, and a test runs two saves in parallel to prove only one succeeds.

**[11:00-11:30] Firoz** - Closing slide with future scope.

> In the future we want SMS alerts when a signal reaches 8, live cyclone tracks imported from the meteorological department, and
> matching each family's needs with the available relief. Thank you for watching.

---

## After recording - checklist

- [ ] Length 10:00-12:00 (target 11:30); both faces and voices present; speaking times about equal.
- [ ] Text in phpMyAdmin and the app readable at 1080p full screen.
- [ ] Upload to YouTube as **Unlisted** (or Public), "Allow embedding" on, not "made for kids"; wait for HD processing.
- [ ] Open the link in a private window while signed out; it plays at 1080p.
- [ ] Put the link in `submission/team.json` (`youtube_url`) for the report cover and QR code.
