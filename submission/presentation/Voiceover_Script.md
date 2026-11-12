# Voiceover Script - Disaster Mapping & Relief Management System (Group 3)

Video length 11:20.6. Timecodes are **measured from the final video**. Speak at a calm pace (about 132 words per minute). Every line fits inside its time slot with a short pause; if you finish early, simply wait - the screen keeps showing the step.

**Voice only (no webcam).** Each person records only their own lines: quiet room, phone voice recorder or headset mic about 20 cm from the mouth, same place and device for every line. **One file per line**, named with the line number shown below, e.g. `07_U.m4a` (any of m4a / mp3 / wav). Leave about 1 second of silence before and after you speak. If you stumble, record the whole line again. Send the files back; they are placed at their start times and levelled automatically, and if a line runs longer than its slot the picture is held a little longer, so nothing is cut.

**Pronunciation:** phpMyAdmin = "P-H-P my admin"; BCNF = "B-C-N-F" (Boyce-Codd normal form); haversine = "HAV-er-sine"; SQL = "S-Q-L" or "sequel" (choose one and keep it); Paikgachha = "PIKE-gah-chah"; Dacope = "DAH-cope".


## Part 1 - Introduction and system scope

### Line 01 | 0:00.0 - 0:17.2 | **FIROZ** (file `01_F`)  
*On screen:* Sign-in page of the application; the cursor rests near the logo.

> Assalamu alaikum. I am Taohiduzzaman Firoz, student ID 210232, and my teammate is Archy Mondal Urmy, student ID 240203. We are group 3, and this is our database project: the Disaster Mapping and Relief Management System.

### Line 02 | 0:17.2 - 0:36.6 | **FIROZ** (file `02_F`)  
*On screen:* The cursor types the user name and password and signs in.

> Khulna is hit by cyclones and floods almost every year. When that happens, coordinators need answers fast: which areas are under a danger signal, which shelters still have space, which families have not received relief, and where the storm is moving.

### Line 03 | 0:36.6 - 1:00.1 | **FIROZ** (file `03_F`)  
*On screen:* Dashboard: the cursor moves across the summary cards, then scrolls to the live status panels.

> Our system keeps all of this in one relational database: disasters, areas, families, shelters, warnings, relief stock and distribution, response tasks and the cyclone path, with three roles: admin, editor and viewer. I worked on the requirements, the reporting queries and this interface; Urmy built the database and the backend.

### Line 04 | 1:00.5 - 1:18.6 | **URMY** (file `04_U`)  
*On screen:* phpMyAdmin home page; the cursor points at the Database server and Web server boxes.

> I am Archy Mondal Urmy. Our database engine is MySQL 8.2 with InnoDB, so we have real foreign keys, transactions, CHECK constraints and triggers. The backend is PHP 8.2 running on Apache, and every query uses prepared statements.

### Line 05 | 1:19.0 - 1:37.1 | **FIROZ** (file `05_F`)  
*On screen:* The Map page of the application opens; the cursor pans over the Leaflet map.

> Firoz again. The frontend I built is HTML, CSS and plain JavaScript, with Leaflet and OpenStreetMap for the maps. In the live demo, phpMyAdmin will run beside the application, so you can see every change reach the database.


## Part 2 - ER diagram and database design

### Line 06 | 1:37.5 - 1:55.9 | **FIROZ** (file `06_F`)  
*On screen:* The complete ER diagram; the cursor visits the core entities one by one.

> This is our entity relationship diagram with 25 tables. The core entities are disaster, area, shelter, affected person, which is one row per family, rescue organisation, relief item and user.

### Line 07 | 1:55.9 - 2:11.8 | **FIROZ** (file `07_F`)  
*On screen:* Zoom on disaster and its child tables; the cursor follows one relationship line.

> Each line is a one-to-many relationship. One disaster has many warnings, track points, damage records and tasks. The crow's foot marks the many side; a small circle means the link is optional.

### Line 08 | 2:11.8 - 2:29.9 | **FIROZ** (file `08_F`)  
*On screen:* Zoom on disaster_area; the cursor points at its two key columns and Relief_Shortage.

> Many-to-many relationships use junction tables. A disaster affects many areas and an area is hit by many disasters, so disaster_area has a composite primary key of both foreign keys. Its extra column, Relief_Shortage, depends on that whole key.

### Line 09 | 2:29.9 - 2:51.9 | **FIROZ** (file `09_F`)  
*On screen:* Zoom on shelter_assignment, then along relief_item, relief_batch, relief_distribution, relief_recipient.

> Events have their own tables. A shelter assignment links one disaster, one family and one shelter. A relief item has stock batches, a batch is given out in distributions, and relief_recipient records which family received it.

### Line 10 | 2:51.9 - 3:16.8 | **FIROZ** (file `10_F`)  
*On screen:* Zoom out; the cursor points at PK, FK and UK markers in a few tables.

> For normalization: every value is atomic, so first normal form. Only junction tables have composite keys, and their extra column depends on the full key, so second normal form. In every table the only determinants are keys, like shelter name or batch code, so the design is in third normal form and BCNF.

### Line 11 | 3:16.8 - 3:34.9 | **FIROZ** (file `11_F`)  
*On screen:* The cursor points at relief_item.Quantity, then at Relief_ID and Batch_ID in relief_distribution.

> We documented two controlled exceptions: the stored stock total in relief_item, guarded by a discrepancy check, and Relief_ID in a distribution, which the batch already decides. Values like free shelter places are never stored; they are always calculated.

### Line 12 | 3:35.3 - 3:59.1 | **URMY** (file `12_U`)  
*On screen:* Database drms_demo: the list of all 25 tables; then the Structure page of shelter_assignment, scrolled to the Indexes section.

> I built this design in MySQL. Here is the relational schema: all 25 tables. Now shelter_assignment. Entities use an auto-increment primary key; junction tables use the pair of foreign keys. Every foreign key has an index, and this one is special: a functional unique index.

### Line 13 | 3:59.1 - 4:12.2 | **URMY** (file `13_U`)  
*On screen:* The cursor points at uq_assignment_open_stay and its expression.

> It indexes the person only while Check_Out is empty. So a family can have many finished stays but only one open stay, and MySQL itself enforces it.

### Line 14 | 4:12.2 - 4:31.2 | **URMY** (file `14_U`)  
*On screen:* Relation view: the foreign keys with their ON DELETE and ON UPDATE rules.

> Here are the foreign keys with their delete rules. Across the schema we have 40: 24 cascade for records that belong to a disaster, 8 restrict where deleting would lose history or stock, and 8 set null for optional links.

### Line 15 | 4:31.2 - 4:58.4 | **URMY** (file `15_U`)  
*On screen:* Database Triggers page listing the 8 triggers; then trg_assignment_ins is opened and its SQL body is shown.

> 18 CHECK constraints protect single values, like family size above zero or signal level one to ten. Rules that span tables are triggers. This is one I wrote: before a stay is inserted, it checks that the shelter is registered for the disaster and that the family is recorded as affected by it; otherwise it raises an error.

### Line 16 | 4:58.4 - 5:19.9 | **URMY** (file `16_U`)  
*On screen:* SQL tab: EXPLAIN of the overdue-task query is typed and run; the result shows the index.

> For indexing, this EXPLAIN shows that the overdue task query is answered from our composite index on status and due date alone.


## Part 3 - Live system demonstration and database operations

### Line 17 | 5:20.3 - 5:38.0 | **URMY** (file `17_U`)  
*On screen:* App: Affected Person > Add record; types Shirin Akter, family size 4, area Dacope; saves. phpMyAdmin: browse affected_person, the new row 6 appears.

> Now the live demo, with phpMyAdmin on the left. I create a new family: Shirin Akter, four members, from Dacope. After saving, I refresh the table on the left, and the new row is already in MySQL.

### Line 18 | 5:38.0 - 5:45.2 | **URMY** (file `18_U`)  
*On screen:* phpMyAdmin: browse audit_log; the newest row shows add, affected_person, demo_editor and the values.

> Every change also writes the audit log, with the user and the exact values.

### Line 19 | 5:45.2 - 5:57.6 | **URMY** (file `19_U`)  
*On screen:* App: Disaster Affected Person > Add: disaster 1, person 6; saves.

> Next I link her family to the active cyclone. This is a row in the junction table disaster_affected_person.

### Line 20 | 5:57.6 - 6:22.7 | **URMY** (file `20_U`)  
*On screen:* App: Shelter Assignment > Add: disaster 1, person 6, Dacope Union Primary School Shelter. Error: over capacity 11 + 4 > 11. phpMyAdmin: SQL shows 11 people staying there.

> I try to place them in the Dacope school shelter. It is refused: eleven people are already staying and it has eleven places. This rule runs inside a transaction with SELECT FOR UPDATE, so two users can never take the last places together.

### Line 21 | 6:22.7 - 6:30.3 | **URMY** (file `21_U`)  
*On screen:* App: same form with Koyra Government Shelter; saves. phpMyAdmin: browse shelter_assignment, the new open stay.

> So I choose Koyra Government Shelter instead, and the new open stay appears in MySQL.

### Line 22 | 6:30.3 - 6:39.2 | **URMY** (file `22_U`)  
*On screen:* App: Shelter Assignment list; sorts by Check In, searches 2026-10-24 (3 records match).

> For reading, every table can be sorted, searched and exported to CSV.

### Line 23 | 6:39.2 - 6:56.8 | **URMY** (file `23_U`)  
*On screen:* App: Shelter > Edit the Dacope school: capacity 11 to 20; updates. phpMyAdmin: shelter shows 20; audit_log shows Capacity: 11 -> 20.

> Now an update: the school opens a second floor, so I raise its capacity from eleven to twenty. The table on the left shows twenty, and the audit log keeps the old and new value.

### Line 24 | 6:56.8 - 7:21.4 | **URMY** (file `24_U`)  
*On screen:* App: Add family with size -2 is refused. phpMyAdmin SQL: UPDATE shelter SET Capacity = 0 fails with the CHECK constraint.

> Constraints are enforced too. A negative family size is refused, and on the left the same CHECK constraint stops a direct SQL update, so the data is protected even outside our application.

### Line 25 | 7:21.4 - 7:34.3 | **URMY** (file `25_U`)  
*On screen:* phpMyAdmin SQL: INSERT a stay for the flood in Koyra shelter; trigger error: That shelter is not used for this disaster.

> And here a trigger inside MySQL rejects a stay in a shelter that is not registered for that disaster.

### Line 26 | 7:34.3 - 7:51.9 | **URMY** (file `26_U`)  
*On screen:* App: sign out, sign in as admin; Shelter > Delete the Dacope school: the page lists the 2 stays and 2 distributions that block the deletion.

> Finally, delete. Only an admin may do this, so I sign in as the admin. A shelter that still has stays cannot be deleted: the page lists the records that block it. That is RESTRICT.

### Line 27 | 7:51.9 - 8:21.8 | **URMY** (file `27_U`)  
*On screen:* phpMyAdmin SQL: warnings of Cyclone #3 = 1. App: Disaster > Delete the closed Cyclone #3 lists 4 dependent rows; Yes, delete. phpMyAdmin SQL again: 0.

> A closed cyclone is different. In MySQL, Cyclone number three has one warning. The page lists the records that will be deleted with it, and after I confirm, the same query on the left returns zero: that is CASCADE.

### Line 28 | 8:22.2 - 8:39.8 | **FIROZ** (file `28_F`)  
*On screen:* Live Report: warning signal panel; Cyclone #1 red, signal 10, with its areas.

> Now the reports. The Live Report reads many tables at once. A disaster's signal is its latest warning; an area takes the highest signal of the disasters affecting it. Our cyclone is at great danger signal ten.

### Line 29 | 8:39.8 - 8:52.4 | **FIROZ** (file `29_F`)  
*On screen:* Shelter panel: the Dacope school now green with 9 places left.

> Places left are capacity minus the family members staying now. After Urmy's update, the school shows nine places, calculated live; the page refreshes every five seconds.

### Line 30 | 8:52.4 - 9:09.1 | **FIROZ** (file `30_F`)  
*On screen:* Relief tracker: Paikgachha and Dacope red Shortage; Koyra green.

> In the relief tracker, Paikgachha is short: no one reached and flagged by a coordinator. Dacope has just turned red, because the new family has not received relief yet, so only 73 percent are covered.

### Line 31 | 9:09.1 - 9:24.4 | **FIROZ** (file `31_F`)  
*On screen:* Path map: observed track, forecast line, impact circle; area lists: now, passed, forecast.

> The path map compares every area with every track point using the haversine distance: Dacope and Paikgachha are being crossed now, Koyra has been passed, and Sonadanga is in the forecast path.

### Line 32 | 9:24.8 - 9:45.0 | **FIROZ** (file `32_F`)  
*On screen:* phpMyAdmin SQL tab: the six-table relief coverage query runs; the numbers match the panel on the right.

> Here is the query behind the relief tracker in phpMyAdmin. It joins six tables, and its numbers match the screen: fifteen people in Dacope, eleven reached.

### Line 33 | 9:45.4 - 10:00.0 | **FIROZ** (file `33_F`)  
*On screen:* Disaster report for Cyclone #1, then the Data Health page with all checks passed.

> The disaster report brings together areas, shelters, damage and relief for one incident, and Data Health rechecks every rule across the whole database. All seventeen checks pass.


## Part 4 - Challenges and future scope

### Line 34 | 10:00.4 - 10:27.1 | **URMY** (file `34_U`)  
*On screen:* shelter_assignment Structure > Indexes, then Browse: the two rows of Rahim Uddin (person 1), one finished stay and one open stay.

> One hurdle came from our own testing. At first, shelter_assignment had a unique key on disaster and person. It looked right, but a family that left one shelter could never be placed in another during the same cyclone, which is exactly what happens when an embankment breaks. Here, one family has two stays: one finished, one open.

### Line 35 | 10:27.1 - 10:57.1 | **URMY** (file `35_U`)  
*On screen:* SQL tab: inserting a second open stay for the same family fails with a duplicate-key error on uq_assignment_open_stay.

> We replaced it with the real rule, one open stay per family, using the functional index, and wrote an upgrade that changes existing databases without losing data. Here, MySQL refuses a second open stay for the same family. Concurrency was the second challenge: two coordinators saving the last places at once. We solved it with transactions and row locks, and a test proves it.

### Line 36 | 10:57.5 - 11:20.6 | **FIROZ** (file `36_F`)  
*On screen:* Dashboard, the cursor resting; then the map with the cyclone path.

> In the future we want SMS alerts when a signal reaches eight, cyclone tracks imported from the meteorological department, and matching each family's needs with the available relief. A small mobile app for field volunteers would also let them send updates from the shelters directly. Thank you for watching.


---

## Only Taohiduzzaman Firoz (210232)'s lines (17 lines, about 5.0 min of speaking)

- **Line 01** (file `01_F`, starts 0:00.0, 36 words) - Assalamu alaikum. I am Taohiduzzaman Firoz, student ID 210232, and my teammate is Archy Mondal Urmy, student ID 240203. We are group 3, and this is our database project: the Disaster Mapping and Relief Management System.

- **Line 02** (file `02_F`, starts 0:17.2, 41 words) - Khulna is hit by cyclones and floods almost every year. When that happens, coordinators need answers fast: which areas are under a danger signal, which shelters still have space, which families have not received relief, and where the storm is moving.

- **Line 03** (file `03_F`, starts 0:36.6, 50 words) - Our system keeps all of this in one relational database: disasters, areas, families, shelters, warnings, relief stock and distribution, response tasks and the cyclone path, with three roles: admin, editor and viewer. I worked on the requirements, the reporting queries and this interface; Urmy built the database and the backend.

- **Line 05** (file `05_F`, starts 1:19.0, 38 words) - Firoz again. The frontend I built is HTML, CSS and plain JavaScript, with Leaflet and OpenStreetMap for the maps. In the live demo, phpMyAdmin will run beside the application, so you can see every change reach the database.

- **Line 06** (file `06_F`, starts 1:37.5, 30 words) - This is our entity relationship diagram with 25 tables. The core entities are disaster, area, shelter, affected person, which is one row per family, rescue organisation, relief item and user.

- **Line 07** (file `07_F`, starts 1:55.9, 32 words) - Each line is a one-to-many relationship. One disaster has many warnings, track points, damage records and tasks. The crow's foot marks the many side; a small circle means the link is optional.

- **Line 08** (file `08_F`, starts 2:11.8, 38 words) - Many-to-many relationships use junction tables. A disaster affects many areas and an area is hit by many disasters, so disaster_area has a composite primary key of both foreign keys. Its extra column, Relief_Shortage, depends on that whole key.

- **Line 09** (file `09_F`, starts 2:29.9, 36 words) - Events have their own tables. A shelter assignment links one disaster, one family and one shelter. A relief item has stock batches, a batch is given out in distributions, and relief_recipient records which family received it.

- **Line 10** (file `10_F`, starts 2:51.9, 53 words) - For normalization: every value is atomic, so first normal form. Only junction tables have composite keys, and their extra column depends on the full key, so second normal form. In every table the only determinants are keys, like shelter name or batch code, so the design is in third normal form and BCNF.

- **Line 11** (file `11_F`, starts 3:16.8, 38 words) - We documented two controlled exceptions: the stored stock total in relief_item, guarded by a discrepancy check, and Relief_ID in a distribution, which the batch already decides. Values like free shelter places are never stored; they are always calculated.

- **Line 28** (file `28_F`, starts 8:22.2, 37 words) - Now the reports. The Live Report reads many tables at once. A disaster's signal is its latest warning; an area takes the highest signal of the disasters affecting it. Our cyclone is at great danger signal ten.

- **Line 29** (file `29_F`, starts 8:39.8, 26 words) - Places left are capacity minus the family members staying now. After Urmy's update, the school shows nine places, calculated live; the page refreshes every five seconds.

- **Line 30** (file `30_F`, starts 8:52.4, 35 words) - In the relief tracker, Paikgachha is short: no one reached and flagged by a coordinator. Dacope has just turned red, because the new family has not received relief yet, so only 73 percent are covered.

- **Line 31** (file `31_F`, starts 9:09.1, 32 words) - The path map compares every area with every track point using the haversine distance: Dacope and Paikgachha are being crossed now, Koyra has been passed, and Sonadanga is in the forecast path.

- **Line 32** (file `32_F`, starts 9:24.8, 26 words) - Here is the query behind the relief tracker in phpMyAdmin. It joins six tables, and its numbers match the screen: fifteen people in Dacope, eleven reached.

- **Line 33** (file `33_F`, starts 9:45.4, 27 words) - The disaster report brings together areas, shelters, damage and relief for one incident, and Data Health rechecks every rule across the whole database. All seventeen checks pass.

- **Line 36** (file `36_F`, starts 10:57.5, 49 words) - In the future we want SMS alerts when a signal reaches eight, cyclone tracks imported from the meteorological department, and matching each family's needs with the available relief. A small mobile app for field volunteers would also let them send updates from the shelters directly. Thank you for watching.


---

## Only Archy Mondal Urmy (240203)'s lines (19 lines, about 5.2 min of speaking)

- **Line 04** (file `04_U`, starts 1:00.5, 38 words) - I am Archy Mondal Urmy. Our database engine is MySQL 8.2 with InnoDB, so we have real foreign keys, transactions, CHECK constraints and triggers. The backend is PHP 8.2 running on Apache, and every query uses prepared statements.

- **Line 12** (file `12_U`, starts 3:35.3, 45 words) - I built this design in MySQL. Here is the relational schema: all 25 tables. Now shelter_assignment. Entities use an auto-increment primary key; junction tables use the pair of foreign keys. Every foreign key has an index, and this one is special: a functional unique index.

- **Line 13** (file `13_U`, starts 3:59.1, 27 words) - It indexes the person only while Check_Out is empty. So a family can have many finished stays but only one open stay, and MySQL itself enforces it.

- **Line 14** (file `14_U`, starts 4:12.2, 40 words) - Here are the foreign keys with their delete rules. Across the schema we have 40: 24 cascade for records that belong to a disaster, 8 restrict where deleting would lose history or stock, and 8 set null for optional links.

- **Line 15** (file `15_U`, starts 4:31.2, 58 words) - 18 CHECK constraints protect single values, like family size above zero or signal level one to ten. Rules that span tables are triggers. This is one I wrote: before a stay is inserted, it checks that the shelter is registered for the disaster and that the family is recorded as affected by it; otherwise it raises an error.

- **Line 16** (file `16_U`, starts 4:58.4, 22 words) - For indexing, this EXPLAIN shows that the overdue task query is answered from our composite index on status and due date alone.

- **Line 17** (file `17_U`, starts 5:20.3, 37 words) - Now the live demo, with phpMyAdmin on the left. I create a new family: Shirin Akter, four members, from Dacope. After saving, I refresh the table on the left, and the new row is already in MySQL.

- **Line 18** (file `18_U`, starts 5:38.0, 14 words) - Every change also writes the audit log, with the user and the exact values.

- **Line 19** (file `19_U`, starts 5:45.2, 18 words) - Next I link her family to the active cyclone. This is a row in the junction table disaster_affected_person.

- **Line 20** (file `20_U`, starts 5:57.6, 43 words) - I try to place them in the Dacope school shelter. It is refused: eleven people are already staying and it has eleven places. This rule runs inside a transaction with SELECT FOR UPDATE, so two users can never take the last places together.

- **Line 21** (file `21_U`, starts 6:22.7, 15 words) - So I choose Koyra Government Shelter instead, and the new open stay appears in MySQL.

- **Line 22** (file `22_U`, starts 6:30.3, 12 words) - For reading, every table can be sorted, searched and exported to CSV.

- **Line 23** (file `23_U`, starts 6:39.2, 35 words) - Now an update: the school opens a second floor, so I raise its capacity from eleven to twenty. The table on the left shows twenty, and the audit log keeps the old and new value.

- **Line 24** (file `24_U`, starts 6:56.8, 32 words) - Constraints are enforced too. A negative family size is refused, and on the left the same CHECK constraint stops a direct SQL update, so the data is protected even outside our application.

- **Line 25** (file `25_U`, starts 7:21.4, 19 words) - And here a trigger inside MySQL rejects a stay in a shelter that is not registered for that disaster.

- **Line 26** (file `26_U`, starts 7:34.3, 35 words) - Finally, delete. Only an admin may do this, so I sign in as the admin. A shelter that still has stays cannot be deleted: the page lists the records that block it. That is RESTRICT.

- **Line 27** (file `27_U`, starts 7:51.9, 39 words) - A closed cyclone is different. In MySQL, Cyclone number three has one warning. The page lists the records that will be deleted with it, and after I confirm, the same query on the left returns zero: that is CASCADE.

- **Line 34** (file `34_U`, starts 10:00.4, 57 words) - One hurdle came from our own testing. At first, shelter_assignment had a unique key on disaster and person. It looked right, but a family that left one shelter could never be placed in another during the same cyclone, which is exactly what happens when an embankment breaks. Here, one family has two stays: one finished, one open.

- **Line 35** (file `35_U`, starts 10:27.1, 64 words) - We replaced it with the real rule, one open stay per family, using the functional index, and wrote an upgrade that changes existing databases without losing data. Here, MySQL refuses a second open stay for the same family. Concurrency was the second challenge: two coordinators saving the last places at once. We solved it with transactions and row locks, and a test proves it.
